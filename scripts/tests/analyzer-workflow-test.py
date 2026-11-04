#!/usr/bin/env python3
"""Exercise change detection, job contracts and merge gates from the workflow."""

import os
from pathlib import Path
import re
import shlex
import subprocess
import tempfile
import textwrap
import unittest


WORKFLOW = Path(__file__).resolve().parents[2] / ".github/workflows/main.yml"
CONTENT = WORKFLOW.read_text()
METADATA_EDIT = (
    "github.event_name == 'pull_request' && github.event.action == 'edited' && "
    "github.event.changes.base == null && "
    "(github.event.changes.title != null || github.event.changes.body != null)"
)


def job(name):
    match = re.search(
        rf"^  {re.escape(name)}:\n(.*?)(?=^  [a-z][a-z-]*:\n|\Z)",
        CONTENT, re.MULTILINE | re.DOTALL,
    )
    if match is None:
        raise AssertionError(f"Missing workflow job: {name}")
    return match.group(1)


def dependencies(name):
    section = job(name)
    match = re.search(r"^    needs:([^\n]*)\n((?:      - [^\n]+\n)*)", section, re.MULTILINE)
    if match is None:
        return set()
    if match.group(1).strip():
        value = match.group(1).strip()
        if not re.fullmatch(r"[a-z][a-z-]*", value):
            raise AssertionError(f"Unsupported needs format in {name}: {value}")
        return {value}
    values = re.findall(r"^      - ([a-z][a-z-]*)$", match.group(2), re.MULTILINE)
    if not values or len(values) != len(match.group(2).splitlines()):
        raise AssertionError(f"Unsupported or empty needs list in {name}")
    return set(values)


class AnalyzerWorkflowTest(unittest.TestCase):
    def test_both_required_workflows_validate_merge_groups(self):
        review = (WORKFLOW.parent / "dependency-review.yml").read_text()
        self.assertEqual(
            review.split("on:\n", 1)[1].split("\npermissions:", 1)[0],
            "  pull_request:\n  merge_group:\n    types:\n      - checks_requested\n",
        )
        self.assertIn("  merge_group:\n    types:\n      - checks_requested\n", CONTENT)
        self.assertIn("\npermissions:\n  contents: read\n", review)
        self.assertIn("\n  dependency-review:\n", review)
        self.assertNotIn("    if:", review)
        self.assertNotIn("continue-on-error", review)
        self.assertNotIn("warn-only:", review)
        self.assertNotIn("pull_request_target:", review)
        self.assertIn("          persist-credentials: false\n", review)
        action = re.search(r"        uses: actions/dependency-review-action@\S+.*\n", review)
        self.assertIsNotNone(action)
        self.assertRegex(action.group(), r"@[a-f0-9]{40}")
        # Native action defaults select PR or merge-group base/head SHAs.
        self.assertEqual(review.split(action.group(), 1)[1].strip(), "")
        self.assertEqual(self.run_gate("ci-summary", "merge_group").returncode, 0)
        self.assertEqual(self.run_gate("ci-result", "merge_group").returncode, 0)

    def test_every_core_job_is_checked_by_the_required_gate(self):
        names = set(re.findall(r"^  ([a-z][a-z-]*):$", CONTENT.split("jobs:\n", 1)[1],
                               re.MULTILINE))
        graph = {name: dependencies(name) for name in names}
        covered = set()

        def visit(name, ancestors):
            self.assertIn(name, names, f"Unknown dependency: {name}")
            self.assertNotIn(name, ancestors, f"Dependency cycle at {name}")
            if name in covered:
                return
            for dependency in graph[name]:
                visit(dependency, ancestors | {name})
            covered.add(name)

        visit("ci-result", set())
        self.assertEqual(names - covered, {"report"})
        for name in covered:
            header = job(name).split("    steps:", 1)[0]
            self.assertNotRegex(header, r"^    continue-on-error:", name)
        self.assertNotIn("report", covered)

    def test_gate_dependencies_have_real_result_bindings(self):
        for name in ("ci-summary", "ci-result"):
            section = job(name)
            bindings = re.findall(
                r"^          ([A-Z_]+): \$\{\{ needs\.([a-z][a-z-]*)\.result \}\}$",
                section, re.MULTILINE,
            )
            self.assertEqual({dependency for _, dependency in bindings}, dependencies(name))
            self.assertEqual(len(bindings), len(dependencies(name)))

    def test_every_gate_result_rejects_unexpected_outcomes_on_validation_events(self):
        for name in ("ci-summary", "ci-result"):
            variables = re.findall(
                r"^          ([A-Z_]+): \$\{\{ needs\.[a-z][a-z-]*\.result \}\}$",
                job(name), re.MULTILINE,
            )
            for event in ("pull_request", "push", "merge_group", "workflow_dispatch"):
                self.assertEqual(self.run_gate(name, event).returncode, 0)
                for variable in variables:
                    for result in ("failure", "cancelled", "skipped", "", "unknown"):
                        with self.subTest(gate=name, event=event, variable=variable,
                                          result=result):
                            outcome = self.run_gate(name, event, **{variable: result})
                            self.assertNotEqual(outcome.returncode, 0, outcome.stdout)

    def test_schedule_accepts_exactly_the_declared_result_for_every_dependency(self):
        for name in ("ci-summary", "ci-result"):
            self.assertEqual(
                self.run_gate(name, "schedule", "skipped").returncode, 0,
            )
            variables = re.findall(
                r"^          ([A-Z_]+): \$\{\{ needs\.[a-z][a-z-]*\.result \}\}$",
                job(name), re.MULTILINE,
            )
            for variable in variables:
                expected = "success" if variable in (
                    "SECURITY_CHECK", "DOCKER_UPDATES", "CI_SUMMARY",
                ) else "skipped"
                for result in ("success", "failure", "cancelled", "skipped", "", "unknown"):
                    with self.subTest(gate=name, variable=variable, result=result):
                        outcome = self.run_gate(
                            name, "schedule", "skipped", **{variable: result},
                        )
                        self.assertEqual(outcome.returncode == 0, result == expected,
                                         outcome.stdout)

    def test_metadata_edit_guard_preserves_base_changes_and_unclear_payloads(self):
        condition = re.search(
            r"^    if: \$\{\{ github.event_name != 'schedule' && !\((.+)\) \}\}$",
            job("changes"), re.MULTILINE,
        )
        self.assertIsNotNone(condition)
        self.assertEqual(condition.group(1), METADATA_EDIT)
        for event in ("pull_request", "push", "merge_group", "schedule", "workflow_dispatch"):
            for action in ("edited", "opened", "synchronize", "reopened", ""):
                for fields in ((), ("title",), ("body",), ("title", "body"),
                               ("base",), ("base", "title"), ("base", "body")):
                    with self.subTest(event=event, action=action, fields=fields):
                        expression = condition.group(1)
                        values = {
                            "github.event_name": event,
                            "github.event.action": action,
                            **{f"github.event.changes.{field}":
                               "present" if field in fields else "null"
                               for field in ("base", "title", "body")},
                        }
                        for name, value in values.items():
                            expression = expression.replace(name, shlex.quote(value))
                        result = subprocess.run(
                            ["bash", "-c", f"[[ {expression} ]]"], check=False,
                            capture_output=True, text=True, timeout=10,
                        )
                        expected = (
                            event == "pull_request" and action == "edited"
                            and "base" not in fields
                            and ("title" in fields or "body" in fields)
                        )
                        self.assertIn(result.returncode, (0, 1), result.stderr)
                        self.assertEqual(result.returncode == 0, expected)

    def test_metadata_edit_skip_covers_roots_and_gates_without_relaxing_results(self):
        for name in ("changes", "lint-documentation", "lint-workflows", "gitleaks"):
            self.assertIn(
                "    if: ${{ github.event_name != 'schedule' && !("
                + METADATA_EDIT + ") }}\n", job(name),
            )
        for name in ("ci-summary", "ci-result"):
            self.assertIn(
                "    if: ${{ !cancelled() && !(" + METADATA_EDIT + ") }}\n", job(name),
            )
        for name in ("lint-configuration", "docker-image-verify-pins",
                     "lightweight-analyzers", "static-analyzers", "test", "docker-build"):
            self.assertRegex(job(name), r"    needs:(?: changes\n|\n      - changes\n)")
            self.assertNotIn("always()", job(name).split("    steps:", 1)[0])
        for name in ("security-check", "docker-image-check-updates"):
            self.assertIn("needs.changes.result == 'success'", job(name))
        self.assertIn("github.event_name == 'push'", job("report"))
        self.assertIn("      - edited\n", CONTENT.split("  merge_group:", 1)[0])
        self.assertIn(
            "    name: ${{ " + METADATA_EDIT
            + " && 'Metadata-only PR edit (no validation)' || 'Validate CI result' }}\n",
            job("ci-result"),
        )

    def test_only_image_update_availability_is_advisory(self):
        section = job("docker-image-check-updates")
        self.assertIn("run: bash scripts/docker-images.sh check-updates --advisory\n", section)
        self.assertIn("run: bash scripts/make.sh test-docker-images\n", section)
        self.assertNotIn("continue-on-error", section)
        self.assertIn("      - docker-image-check-updates\n", job("ci-summary"))
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch", "schedule"):
            for result in ("failure", "cancelled", "skipped", ""):
                with self.subTest(event=event, result=result):
                    self.assertNotEqual(
                        self.run_gate("ci-summary", event, DOCKER_UPDATES=result).returncode, 0,
                    )

    def test_ci_database_matches_pinned_development_baseline(self):
        dockerfile = WORKFLOW.parents[2] / "docker/mariadb/Dockerfile"
        image = re.search(r"^FROM (mariadb:\S+)$", dockerfile.read_text(), re.MULTILINE)
        self.assertIsNotNone(image)
        self.assertRegex(image.group(1), r"^mariadb:\d+\.\d+\.\d+@sha256:[a-f0-9]{64}$")
        section = job("test")
        self.assertIn(f"        image: {image.group(1)}\n", section)
        self.assertIn(
            '        options: --health-cmd="healthcheck.sh --connect --innodb_initialized" '
            "--health-start-period=30s --health-interval=10s --health-timeout=5s "
            "--health-retries=3\n", section,
        )
        self.assertNotIn("MYSQL_ALLOW_EMPTY_PASSWORD", section)
        self.assertIn("          - 3306/tcp\n", section)
        self.assertIn("${{ job.services.mariadb.ports['3306'] }}", section)

    def test_tests_and_builds_start_after_change_detection_only(self):
        for name in ("test", "docker-build"):
            section = job(name)
            needs = re.search(r"^    needs:\n((?:      - .+\n)+)", section, re.MULTILINE)
            self.assertIsNotNone(needs)
            self.assertEqual(needs.group(1), "      - changes\n")
            self.assertIn(
                "    if: ${{ github.event_name != 'schedule' && "
                "needs.changes.outputs.code == 'true' }}\n", section,
            )

    def test_reporting_still_requires_prerequisites_and_tests(self):
        section = job("report")
        self.assertEqual(dependencies("report"), {"ci-summary", "test"})
        condition = re.search(r"^    if: \$\{\{ (.+) \}\}$", section, re.MULTILINE)
        self.assertIsNotNone(condition)
        self.assertEqual(
            condition.group(1),
            "success() && (github.event_name == 'pull_request' || "
            "(github.event_name == 'push' && github.ref == 'refs/heads/master'))",
        )
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch", "schedule"):
            for ref in ("refs/heads/master", "refs/heads/feature", "refs/pull/42/merge"):
                for gate in ("success", "failure", "cancelled", "skipped"):
                    for tests in ("success", "failure", "cancelled", "skipped"):
                        with self.subTest(event=event, ref=ref, gate=gate, tests=tests):
                            successful = gate == tests == "success"
                            expression = condition.group(1).replace(
                                "success()", "true" if successful else "false",
                            ).replace("github.event_name", repr(event)).replace(
                                "github.ref", repr(ref),
                            ).replace("&&", "and").replace("||", "or")
                            # Only the asserted expression and fixed values reach eval.
                            result = eval(expression, {"__builtins__": {}},
                                          {"true": True, "false": False})
                            expected = successful and (
                                event == "pull_request"
                                or (event == "push" and ref == "refs/heads/master")
                            )
                            self.assertEqual(result, expected)
        self.assertNotIn("report", dependencies("ci-result"))
        self.assertIn("          ref: ${{ github.sha }}\n", section)
        self.assertIn("          persist-credentials: false\n", section)
        self.assertIn("    permissions:\n      contents: read\n", section)
        self.assertNotIn("pull_request_target:", CONTENT)
        for artifact in ("clover.xml", "junit.xml"):
            self.assertIn(
                f"          name: {artifact}\n          path: ./build/logs\n", section,
            )
            self.assertIn(
                f"          name: {artifact}\n          path: ./build/logs/{artifact}\n",
                job("test"),
            )
        uploads = (
            ("Report results to Coveralls", "COVERALLS_REPO_TOKEN"),
            ("SonarQube Scan", "SONAR_TOKEN"),
        )
        for name, token in uploads:
            step = re.search(
                rf"^      - name: {re.escape(name)}[^\n]*\n(.*?)(?=^      - |\Z)",
                section, re.MULTILINE | re.DOTALL,
            )
            self.assertIsNotNone(step)
            self.assertNotIn("continue-on-error", step.group(1))
            self.assertIn("        if: ${{ env." + token + " != '' }}\n", step.group(1))
            self.assertIn(token + ": ${{ secrets." + token + " }}\n", step.group(1))
        self.assertIn(
            "      - name: Prepare reports for host-side SonarQube scan\n"
            "        if: ${{ env.SONAR_TOKEN != '' }}\n", section,
        )
        self.assertNotIn("continue-on-error", section)
        self.assertNotIn("|| true", section)
        self.assertIn("      - *stop_php_step\n", section)
        # Markdown-only PRs skip tests, so success() prevents artifact downloads.
        self.assertIn(
            "    if: ${{ github.event_name != 'schedule' && "
            "needs.changes.outputs.code == 'true' }}\n", job("test"),
        )

    def test_early_test_or_build_success_cannot_bypass_failed_gate(self):
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch"):
            for variable in ("CI_SUMMARY", "TEST", "DOCKER_BUILD"):
                for result in ("failure", "cancelled", "skipped", ""):
                    with self.subTest(event=event, variable=variable, result=result):
                        outcome = self.run_gate("ci-result", event, **{variable: result})
                        self.assertNotEqual(outcome.returncode, 0, outcome.stdout)
        section = job("ci-result")
        for name in ("ci-summary", "test", "docker-build"):
            self.assertIn(f"      - {name}\n", section)

    def test_scheduled_concurrency_is_isolated_without_changing_other_events(self):
        concurrency = CONTENT.split("concurrency:\n", 1)[1].split("jobs:\n", 1)[0]
        self.assertEqual(
            concurrency,
            "  # Metadata-only edits must not cancel validation of the unchanged PR commit.\n"
            "  group: ${{ github.workflow }}-${{ github.ref }}"
            "${{ github.event_name == 'schedule' && '-schedule' || '' }}"
            "${{ " + METADATA_EDIT + " && '-metadata' || '' }}\n"
            "  cancel-in-progress: true\n",
        )

    def run_gate(self, name, event="pull_request", lightweight="success", **overrides):
        section = job(name)
        script = textwrap.dedent(section.split("        run: |\n")[-1])
        variables = re.findall(r"^          ([A-Z_]+):", section, re.MULTILINE)
        environment = dict(os.environ, **dict.fromkeys(variables, "success"))
        environment.update(EVENT_NAME=event, CODE_CHANGED="true")
        if event == "schedule":
            for variable in variables:
                if variable not in (
                    "EVENT_NAME", "SECURITY_CHECK", "DOCKER_UPDATES", "CI_SUMMARY",
                ):
                    environment[variable] = "skipped"
        environment["LIGHTWEIGHT_ANALYZERS"] = lightweight
        environment.update(overrides)
        return subprocess.run(
            ["bash", "-e", "-c", script], env=environment,
            capture_output=True, text=True, check=False, timeout=10,
        )

    def test_every_lightweight_tool_keeps_failure_independent_steps(self):
        section = job("lightweight-analyzers")
        for target in ("phpcs", "ecs", "phplint", "php-parallel-lint", "phploc"):
            with self.subTest(target=target):
                self.assertIn(
                    "        if: ${{ !cancelled() && steps.bootstrap.outcome == 'success' }}\n"
                    f"        run: bash scripts/make.sh {target}\n", section,
                )
                self.assertEqual(CONTENT.count(f"run: bash scripts/make.sh {target}\n"), 1)
        self.assertNotIn("continue-on-error", section)
        self.assertIn("        id: bootstrap\n", section)
        self.assertIn("      - *stop_php_step\n", section)
        self.assertIn("          name: phploc.json\n", section)
        self.assertIn("          path: ./build/phploc.json\n", section)
        self.assertIn("          if-no-files-found: error\n", section)
        self.assertIn("          retention-days: 14\n", section)
        self.assertIn("steps.phploc.outcome == 'failure'", section)

    def test_remaining_matrix_contains_only_heavy_analyzers(self):
        names = re.findall(r"^          - name: (.+)$", job("static-analyzers"), re.MULTILINE)
        self.assertEqual(names, ["Psalm", "PHPStan", "PHP Insights"])

    def test_gates_require_lightweight_job_and_reject_failures(self):
        for name in ("ci-summary", "ci-result"):
            self.assertIn("      - lightweight-analyzers\n", job(name))
            self.assertIn(
                "LIGHTWEIGHT_ANALYZERS: ${{ needs.lightweight-analyzers.result }}", job(name),
            )
            for event in ("pull_request", "push", "merge_group", "workflow_dispatch"):
                for result in ("success", "failure", "cancelled", "skipped", ""):
                    with self.subTest(gate=name, event=event, result=result):
                        outcome = self.run_gate(name, event, result)
                        self.assertEqual(outcome.returncode == 0, result == "success", outcome.stdout)

    def test_schedules_accept_only_skipped_analyzers(self):
        for name in ("ci-summary", "ci-result"):
            for result in ("success", "failure", "cancelled", "skipped", ""):
                with self.subTest(gate=name, result=result):
                    outcome = self.run_gate(name, "schedule", result)
                    self.assertEqual(outcome.returncode == 0, result == "skipped", outcome.stdout)

    def documentation_gate(self, name, **overrides):
        variables = (
            "SECURITY_CHECK", "DOCKER_UPDATES", "DOCKER_PINS", "LINT_CONFIGURATION",
            "STATIC_ANALYZERS", "LIGHTWEIGHT_ANALYZERS", "TEST", "DOCKER_BUILD",
        )
        environment = dict.fromkeys(variables, "skipped")
        environment.update(CODE_CHANGED="false")
        environment.update(overrides)
        return self.run_gate(name, **environment)

    def test_documentation_pr_accepts_only_expected_skips(self):
        for name in ("ci-summary", "ci-result"):
            self.assertEqual(self.documentation_gate(name).returncode, 0)
            variables = re.findall(r"^          ([A-Z_]+):", job(name), re.MULTILINE)
            for variable in variables:
                if variable in ("EVENT_NAME", "CODE_CHANGED"):
                    continue
                expected = "skipped" if variable in (
                    "SECURITY_CHECK", "DOCKER_UPDATES", "DOCKER_PINS", "LINT_CONFIGURATION",
                    "STATIC_ANALYZERS", "LIGHTWEIGHT_ANALYZERS", "TEST", "DOCKER_BUILD",
                ) else "success"
                for result in ("success", "failure", "cancelled", "skipped", "", "unknown"):
                    if result == expected:
                        continue
                    with self.subTest(gate=name, variable=variable, result=result):
                        outcome = self.documentation_gate(name, **{variable: result})
                        self.assertNotEqual(outcome.returncode, 0, outcome.stdout)
            self.assertNotEqual(self.documentation_gate(name, CHANGES="skipped").returncode, 0)

    def test_skip_requires_explicit_documentation_pr(self):
        for name in ("ci-summary", "ci-result"):
            for event in ("push", "merge_group", "workflow_dispatch"):
                with self.subTest(gate=name, event=event):
                    self.assertNotEqual(self.documentation_gate(name, event=event).returncode, 0)
            for code in ("true", "", "unknown"):
                with self.subTest(gate=name, code=code):
                    self.assertNotEqual(self.documentation_gate(name, CODE_CHANGED=code).returncode, 0)

    def test_retained_checks_cannot_be_skipped(self):
        for variable in ("LINT_DOCUMENTATION", "LINT_WORKFLOWS", "GITLEAKS"):
            with self.subTest(variable=variable):
                self.assertNotEqual(
                    self.documentation_gate("ci-summary", **{variable: "skipped"}).returncode, 0,
                )

    def test_job_conditions_keep_periodic_and_documentation_coverage(self):
        condition = (
            "    if: ${{ github.event_name != 'schedule' && "
            "!(github.event_name == 'pull_request' && needs.changes.outputs.code == 'false') }}"
        )
        for name in (
            "lint-configuration", "docker-image-verify-pins",
            "lightweight-analyzers", "static-analyzers",
        ):
            self.assertIn("    needs: changes\n", job(name))
            self.assertIn(condition, job(name))
        for name in ("security-check", "docker-image-check-updates"):
            self.assertIn("    needs: changes\n", job(name))
            self.assertIn(
                "    if: ${{ !cancelled() && (github.event_name == 'schedule' || "
                "(needs.changes.result == 'success' && !(github.event_name == 'pull_request' "
                "&& needs.changes.outputs.code == 'false'))) }}", job(name),
            )
        for name in ("lint-documentation", "lint-workflows", "gitleaks"):
            self.assertIn(
                "    if: ${{ github.event_name != 'schedule' && !("
                + METADATA_EDIT + ") }}\n", job(name),
            )
            self.assertNotIn("needs.changes", job(name))
        for name in ("ci-summary", "ci-result"):
            self.assertIn("      - changes\n", job(name))
            self.assertIn(
                "    if: ${{ !cancelled() && !(" + METADATA_EDIT + ") }}\n", job(name),
            )


class ChangeDetectionTest(unittest.TestCase):
    def git(self, *arguments):
        return subprocess.run(
            ["git", *arguments], cwd=self.root, text=True, capture_output=True,
            check=True, timeout=10,
        ).stdout.strip()

    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="ci-changes-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.git("init", "--quiet")
        self.git("config", "user.name", "CI fixture")
        self.git("config", "user.email", "fixture@example.invalid")
        (self.root / "README.md").write_text("Original documentation\n")
        (self.root / "source.php").write_text("<?php\n")
        self.commit()
        self.base = self.git("rev-parse", "HEAD")

    def commit(self):
        self.git("add", "--all")
        self.git("commit", "--quiet", "-m", "Fixture")

    def detect(self, event="pull_request", base=None, head=None):
        output = self.root / ".git/detection-output"
        script = textwrap.dedent(job("changes").split("        run: |\n")[-1])
        result = subprocess.run(
            ["bash", "-e", "-c", script], cwd=self.root, capture_output=True,
            text=True, check=False, timeout=10,
            env=dict(
                os.environ, EVENT_NAME=event, BASE_SHA=base or self.base,
                HEAD_SHA=head or self.git("rev-parse", "HEAD"), GITHUB_OUTPUT=str(output),
            ),
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        return output.read_text().splitlines()[-1], result

    def test_markdown_only_edit_skips_code(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        self.assertEqual(self.detect()[0], "code=false")

    def test_markdown_add_delete_and_rename_skip_code(self):
        (self.root / "README.md").rename(self.root / "GUIDE.md")
        (self.root / "NEW.md").write_text("New documentation\n")
        self.commit()
        self.assertEqual(self.detect()[0], "code=false")
        (self.root / "GUIDE.md").unlink()
        self.commit()
        self.assertEqual(self.detect()[0], "code=false")

    def test_code_renamed_to_markdown_still_runs_code(self):
        (self.root / "source.php").rename(self.root / "source.md")
        self.commit()
        self.assertEqual(self.detect()[0], "code=true")

    def test_mixed_changes_run_code(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        self.assertEqual(self.detect()[0], "code=true")

    def test_empty_diff_runs_code(self):
        self.assertEqual(self.detect()[0], "code=true")

    def test_code_deletion_runs_code(self):
        (self.root / "source.php").unlink()
        self.commit()
        self.assertEqual(self.detect()[0], "code=true")

    def test_quoted_filename_fails_safe(self):
        (self.root / "unusual\nname.md").write_text("Documentation\n")
        self.commit()
        self.assertEqual(self.detect()[0], "code=true")

    def test_failed_diff_runs_code_and_reports_error(self):
        code, result = self.detect(base="missing-commit")
        self.assertEqual(code, "code=true")
        self.assertIn("fatal:", result.stderr)

    def test_non_pr_events_run_code_even_for_markdown_changes(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        for event in ("push", "merge_group", "workflow_dispatch"):
            with self.subTest(event=event):
                self.assertEqual(self.detect(event)[0], "code=true")


if __name__ == "__main__":
    unittest.main()
