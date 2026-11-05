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
        self.assertEqual(names - covered, set())
        for name in covered:
            header = job(name).split("    steps:", 1)[0]
            self.assertNotRegex(header, r"^    continue-on-error:", name)
        self.assertIn("report", covered)

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
                    if variable == "REPORT" and event in ("merge_group", "workflow_dispatch"):
                        continue
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
        for name in ("changes", "gitleaks"):
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
        for name, output in (("test", "tests"), ("docker-build", "docker")):
            section = job(name)
            needs = re.search(r"^    needs:\n((?:      - .+\n)+)", section, re.MULTILINE)
            self.assertIsNotNone(needs)
            self.assertEqual(needs.group(1), "      - changes\n")
            self.assertIn(
                "    if: ${{ github.event_name != 'schedule' && "
                f"needs.changes.outputs.{output} == 'true' }}}}\n", section,
            )

    def test_reporting_still_requires_prerequisites_and_tests(self):
        section = job("report")
        self.assertEqual(dependencies("report"), {"ci-summary", "test"})
        condition = re.search(r"^    if: \$\{\{ (.+) \}\}$", section, re.MULTILINE)
        self.assertIsNotNone(condition)
        self.assertEqual(
            condition.group(1),
            "!cancelled() && needs.ci-summary.result == 'success' && "
            "needs.test.result == 'success' && (github.event_name == 'pull_request' || "
            "(github.event_name == 'push' && github.ref == 'refs/heads/master'))",
        )
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch", "schedule"):
            for ref in ("refs/heads/master", "refs/heads/feature", "refs/pull/42/merge"):
                for gate in ("success", "failure", "cancelled", "skipped"):
                    for tests in ("success", "failure", "cancelled", "skipped"):
                        for cancelled in (False, True):
                            for upstream_skipped in (False, True):
                                with self.subTest(
                                    event=event, ref=ref, gate=gate, tests=tests,
                                    cancelled=cancelled, upstream_skipped=upstream_skipped,
                                ):
                                    successful = gate == tests == "success"
                                    expression = condition.group(1).replace(
                                        "success()",
                                        repr(successful and not upstream_skipped),
                                    ).replace(
                                        "!cancelled()", repr(not cancelled),
                                    ).replace(
                                        "needs.ci-summary.result", repr(gate),
                                    ).replace(
                                        "needs.test.result", repr(tests),
                                    ).replace("github.event_name", repr(event)).replace(
                                        "github.ref", repr(ref),
                                    ).replace("&&", "and").replace("||", "or")
                                    # Only the asserted expression and fixed values reach eval.
                                    result = eval(expression, {"__builtins__": {}})
                                    expected = not cancelled and successful and (
                                        event == "pull_request"
                                        or (event == "push" and ref == "refs/heads/master")
                                    )
                                    self.assertEqual(result, expected)
        self.assertIn("report", dependencies("ci-result"))
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
            "needs.changes.outputs.tests == 'true' }}\n", job("test"),
        )

    def test_coveralls_install_and_upload_use_the_development_container(self):
        section = job("report")
        self.assertIn(
            "        run: docker compose -f compose.yaml -f docker/ci-compose.yaml "
            "exec -T php composer install --working-dir=tools/06_php-coveralls "
            "--no-progress --optimize-autoloader\n", section,
        )
        upload = re.search(
            r"^      - name: Report results to Coveralls[^\n]*\n"
            r"(.*?)(?=^      - |\Z)", section, re.MULTILINE | re.DOTALL,
        )
        self.assertIsNotNone(upload)
        script = textwrap.dedent(upload.group(1).split("        run: |\n", 1)[1]
                                 .split("        env:\n", 1)[0])
        command = shlex.split(script.replace("\\\n", ""))
        self.assertEqual(
            command[:9],
            ["docker", "compose", "-f", "compose.yaml", "-f",
             "docker/ci-compose.yaml", "exec", "-T", "--env"],
        )
        forwarded = command[8:-4]
        self.assertEqual(forwarded[::2], ["--env"] * 7)
        self.assertEqual(
            set(forwarded[1::2]),
            {"GITHUB_TOKEN", "COVERALLS_REPO_TOKEN", "COVERALLS_RUN_LOCALLY",
             "GITHUB_ACTIONS", "GITHUB_EVENT_NAME", "GITHUB_REF", "GITHUB_RUN_ID"},
        )
        self.assertEqual(
            command[-4:],
            ["php", "php", "./tools/06_php-coveralls/vendor/bin/php-coveralls", "-v"],
        )
        self.assertLess(
            section.index("Bootstrap PHP tools"), section.index("Install Coveralls reporter"),
        )
        self.assertLess(
            section.index("Install Coveralls reporter"), section.index("Report results to Coveralls"),
        )

    def test_reporting_gate_requires_success_only_on_eligible_events(self):
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch", "schedule"):
            for ref in ("refs/heads/master", "refs/heads/feature", "refs/pull/42/merge"):
                expected = "success" if (
                    event == "pull_request"
                    or (event == "push" and ref == "refs/heads/master")
                ) else "skipped"
                for result in ("success", "failure", "cancelled", "skipped", "", "unknown"):
                    with self.subTest(event=event, ref=ref, result=result):
                        outcome = self.run_gate(
                            "ci-result", event,
                            "skipped" if event == "schedule" else "success",
                            REF=ref, REPORT=result,
                        )
                        self.assertEqual(outcome.returncode == 0, result == expected,
                                         outcome.stdout)

    def test_early_test_or_build_success_cannot_bypass_failed_gate(self):
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch"):
            for variable in ("CI_SUMMARY", "TEST", "DOCKER_BUILD"):
                for result in ("failure", "cancelled", "skipped", ""):
                    with self.subTest(event=event, variable=variable, result=result):
                        outcome = self.run_gate("ci-result", event, **{variable: result})
                        self.assertNotEqual(outcome.returncode, 0, outcome.stdout)
        section = job("ci-result")
        for name in ("ci-summary", "test", "docker-build", "report"):
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
        environment.update(EVENT_NAME=event, CODE_CHANGED="true",
                           DOCUMENTATION_CHANGED="true", REF="refs/heads/master")
        flags = re.findall(
            r"^          (\w+): \$\{\{ needs\.changes\.outputs\.\w+ \}\}$",
            section, re.MULTILINE,
        )
        environment.update(dict.fromkeys(flags, "true"))
        if event in ("merge_group", "workflow_dispatch"):
            environment["REPORT"] = "skipped"
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
            "STATIC_ANALYZERS", "LIGHTWEIGHT_ANALYZERS", "TEST", "DOCKER_BUILD", "REPORT",
            "LINT_WORKFLOWS",
        )
        environment = dict.fromkeys(variables, "skipped")
        environment.update(CODE_CHANGED="false")
        environment.update(dict.fromkeys((
            "CONFIGURATION_CHANGED", "SECURITY_CHANGED", "IMAGES_CHANGED", "PHP_CHANGED",
            "TESTS_CHANGED", "DOCKER_CHANGED", "WORKFLOWS_CHANGED",
        ), "false"))
        environment.update(overrides)
        return self.run_gate(name, **environment)

    def test_documentation_pr_accepts_only_expected_skips(self):
        for name in ("ci-summary", "ci-result"):
            self.assertEqual(self.documentation_gate(name).returncode, 0)
            variables = re.findall(r"^          ([A-Z_]+):", job(name), re.MULTILINE)
            for variable in variables:
                if variable in ("EVENT_NAME", "CODE_CHANGED", "REF") or variable.endswith("_CHANGED"):
                    continue
                expected = "skipped" if variable in (
                    "SECURITY_CHECK", "DOCKER_UPDATES", "DOCKER_PINS", "LINT_CONFIGURATION",
                    "STATIC_ANALYZERS", "LIGHTWEIGHT_ANALYZERS", "TEST", "DOCKER_BUILD", "REPORT",
                    "LINT_WORKFLOWS",
                ) else "success"
                for result in ("success", "failure", "cancelled", "skipped", "", "unknown"):
                    if result == expected:
                        continue
                    with self.subTest(gate=name, variable=variable, result=result):
                        outcome = self.documentation_gate(name, **{variable: result})
                        self.assertNotEqual(outcome.returncode, 0, outcome.stdout)
            self.assertNotEqual(self.documentation_gate(name, CHANGES="skipped").returncode, 0)

    def test_scoped_skips_apply_to_pushes_and_merge_groups_too(self):
        for name in ("ci-summary", "ci-result"):
            for event in ("push", "merge_group", "workflow_dispatch"):
                with self.subTest(gate=name, event=event):
                    self.assertEqual(self.documentation_gate(name, event=event).returncode, 0)

    def test_retained_checks_cannot_be_skipped(self):
        for variable in ("LINT_DOCUMENTATION", "GITLEAKS"):
            with self.subTest(variable=variable):
                self.assertNotEqual(
                    self.documentation_gate("ci-summary", **{variable: "skipped"}).returncode, 0,
                )

    def test_job_conditions_keep_periodic_and_documentation_coverage(self):
        for name, output in (
            ("lint-configuration", "configuration"), ("docker-image-verify-pins", "images"),
            ("lightweight-analyzers", "php"), ("static-analyzers", "php"),
            ("lint-workflows", "workflows"),
        ):
            self.assertIn("    needs: changes\n", job(name))
            self.assertIn(
                "    if: ${{ github.event_name != 'schedule' && "
                f"needs.changes.outputs.{output} == 'true' }}}}", job(name),
            )
        for name, output in (("security-check", "security"),
                             ("docker-image-check-updates", "images")):
            self.assertIn("    needs: changes\n", job(name))
            self.assertIn(
                "    if: ${{ !cancelled() && (github.event_name == 'schedule' || "
                "(needs.changes.result == 'success' && "
                f"needs.changes.outputs.{output} == 'true')) }}}}", job(name),
            )
        for name in ("gitleaks",):
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

    def test_documentation_job_and_step_follow_detected_inputs(self):
        section = job("lint-documentation")
        self.assertEqual(dependencies("lint-documentation"), {"changes"})
        self.assertIn(
            "    if: ${{ github.event_name != 'schedule' && "
            "needs.changes.outputs.documentation == 'true' }}\n", section,
        )
        self.assertIn(
            "        if: ${{ needs.changes.outputs.markdown == 'true' }}\n"
            "        run: bash scripts/make.sh lint-markdown\n", section,
        )
        self.assertIn("run: bash scripts/make.sh check-skills\n", section)
        for output in (
            "markdown", "documentation", "configuration", "runtime", "composer", "yaml",
            "compose", "tooling", "workflows", "security", "images", "php", "tests", "docker",
        ):
            self.assertIn(f"      {output}: ${{{{ steps.detect.outputs.{output} }}}}", job("changes"))

    def test_documentation_skip_requires_successful_explicit_detection(self):
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch"):
            environment = dict(DOCUMENTATION_CHANGED="false", LINT_DOCUMENTATION="skipped")
            with self.subTest(event=event):
                self.assertEqual(self.run_gate("ci-summary", event, **environment).returncode, 0)
                for result in ("success", "failure", "cancelled", "", "unknown"):
                    self.assertNotEqual(
                        self.run_gate("ci-summary", event, **dict(
                            environment, LINT_DOCUMENTATION=result,
                        )).returncode, 0,
                    )
                for changes in ("failure", "cancelled", "skipped", ""):
                    self.assertNotEqual(
                        self.run_gate("ci-summary", event, **dict(
                            environment, CHANGES=changes,
                        )).returncode, 0,
                    )
                for output in ("", "unknown"):
                    self.assertNotEqual(
                        self.run_gate("ci-summary", event, **dict(
                            environment, DOCUMENTATION_CHANGED=output,
                        )).returncode, 0,
                    )

    def test_each_gate_flag_authorizes_only_its_exact_job_outcome(self):
        mappings = {
            "ci-summary": {
                "CONFIGURATION_CHANGED": ("LINT_CONFIGURATION",),
                "DOCUMENTATION_CHANGED": ("LINT_DOCUMENTATION",),
                "WORKFLOWS_CHANGED": ("LINT_WORKFLOWS",),
                "SECURITY_CHANGED": ("SECURITY_CHECK",),
                "IMAGES_CHANGED": ("DOCKER_PINS", "DOCKER_UPDATES"),
                "PHP_CHANGED": ("STATIC_ANALYZERS", "LIGHTWEIGHT_ANALYZERS"),
            },
            "ci-result": {
                "PHP_CHANGED": ("STATIC_ANALYZERS", "LIGHTWEIGHT_ANALYZERS"),
                "TESTS_CHANGED": ("TEST",),
                "DOCKER_CHANGED": ("DOCKER_BUILD",),
            },
        }
        for gate, mapping in mappings.items():
            for flag, results in mapping.items():
                for event in ("pull_request", "push", "merge_group", "workflow_dispatch"):
                    environment = dict.fromkeys(results, "skipped")
                    environment[flag] = "false"
                    if flag == "TESTS_CHANGED":
                        environment["REPORT"] = "skipped"
                    with self.subTest(gate=gate, flag=flag, event=event):
                        outcome = self.run_gate(gate, event, **environment)
                        self.assertEqual(outcome.returncode, 0, outcome.stdout)
                        for result in results:
                            for status in ("success", "failure", "cancelled", "", "unknown"):
                                self.assertNotEqual(self.run_gate(gate, event, **dict(
                                    environment, **{result: status},
                                )).returncode, 0)
                        for invalid in ("", "unknown", "skipped"):
                            self.assertNotEqual(self.run_gate(gate, event, **dict(
                                environment, **{flag: invalid},
                            )).returncode, 0)
                        for changes in ("failure", "skipped", "cancelled", ""):
                            self.assertNotEqual(self.run_gate(gate, event, **dict(
                                environment, CHANGES=changes,
                            )).returncode, 0)

    def test_configuration_steps_are_individually_scoped(self):
        section = job("lint-configuration")
        for target, output in (
            ("application-info", "runtime"), ("composer-validate", "composer"),
            ("check-requirements", "runtime"), ("lint-yaml", "yaml"),
        ):
            self.assertIn(
                f"        if: ${{{{ needs.changes.outputs.{output} == 'true' }}}}\n"
                f"        run: bash scripts/make.sh {target}\n", section,
            )
        self.assertIn(
            "        if: ${{ needs.changes.outputs.compose == 'true' }}\n"
            "        run: |\n          docker compose config --quiet\n", section,
        )
        self.assertIn(
            "        if: ${{ needs.changes.outputs.tooling == 'true' }}\n"
            "        run: |\n          bash scripts/make.sh test-command-output\n", section,
        )

    def test_devcontainer_validation_parses_compose_and_json(self):
        section = job("lint-configuration")
        step = re.search(
            r"^      - name: Validate Compose and Dev Container configurations\n"
            r"        if: \$\{\{ needs.changes.outputs.compose == 'true' \}\}\n"
            r"        run: \|\n((?:          .+\n)+)",
            section, re.MULTILINE,
        )
        self.assertIsNotNone(step)
        commands = [shlex.split(line.strip()) for line in step.group(1).splitlines()]
        self.assertIn([
            "docker", "compose", "-f", "compose.yaml", "-f",
            ".devcontainer/docker-compose.devcontainer.yml", "config", "--quiet",
        ], commands)
        json_command = next(command for command in commands if "json.tool" in command)
        self.assertEqual(json_command[:9], [
            "docker", "compose", "-f", "compose.yaml", "-f", "docker/ci-compose.yaml",
            "exec", "-T", "php",
        ])
        self.assertEqual(json_command[9:], [
            "python3", "-m", "json.tool", ".devcontainer/devcontainer.json", "/dev/null",
        ])
        with tempfile.TemporaryDirectory(prefix="ci-devcontainer-") as directory:
            root = Path(directory)
            target = root / ".devcontainer/devcontainer.json"
            target.parent.mkdir()
            for content, valid in (
                ('{"name": "Fixture", "service": "php"}', True),
                ('{"name": "Fixture" "service": "php"}', False),
                ('{"name": "Fixture",}', False),
            ):
                with self.subTest(content=content):
                    target.write_text(content)
                    result = subprocess.run(
                        json_command[9:], cwd=root, text=True, capture_output=True,
                        check=False, timeout=10,
                    )
                    self.assertEqual(result.returncode == 0, valid, result.stderr)
            target.unlink()
            result = subprocess.run(
                json_command[9:], cwd=root, text=True, capture_output=True,
                check=False, timeout=10,
            )
            self.assertNotEqual(result.returncode, 0)


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
            ["bash", "-eo", "pipefail", "-c", script], cwd=self.root, capture_output=True,
            text=True, check=False, timeout=10,
            env=dict(
                os.environ, EVENT_NAME=event, BASE_SHA=base or self.base,
                HEAD_SHA=head or self.git("rev-parse", "HEAD"), GITHUB_OUTPUT=str(output),
            ),
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.outputs = dict(line.split("=", 1) for line in output.read_text().splitlines())
        return "code=" + self.outputs["code"], result

    def assert_documentation(self, markdown, documentation):
        self.assertEqual(self.outputs["markdown"], str(markdown).lower())
        self.assertEqual(self.outputs["documentation"], str(documentation).lower())

    def test_path_selection_matrix(self):
        cases = {
            "src/Service/Example.php": {"runtime", "php", "tests", "docker"},
            "config/services.yaml": {"runtime", "yaml", "php", "tests", "docker"},
            "tests/Unit/ExampleTest.php": {"php", "tests"},
            "phpunit.xml.dist": {"php", "tests"},
            "ecs.php": {"php"},
            "phpstan.neon.dist": {"php"},
            ".phplint.yml": {"php"},
            ".phpinsights.php": {"php"},
            "psalm.xml": {"php"},
            "tools/02_phpstan/composer.lock": {"php", "security"},
            "tools/06_php-coveralls/composer.json": {"php", "tests", "security"},
            "templates/example.html.twig": {"runtime", "php", "tests", "docker"},
            "translations/messages.fi.yaml": {"runtime", "php", "tests", "docker"},
            "composer.lock": {"composer", "runtime", "yaml", "php", "tests", "docker", "security"},
            ".env.test": {"runtime", "php", "tests", "compose", "docker"},
            "docker/php/php.ini": {"runtime", "php", "tests", "compose", "docker"},
            "compose.prod.yaml": {"runtime", "php", "tests", "compose", "docker"},
            "docker/mariadb/Dockerfile": {"runtime", "php", "tests", "compose", "docker", "images"},
            "docker/nginx/Dockerfile": {"compose", "docker", "tooling", "images"},
            "docker/nginx/nginx.conf": {"compose", "docker", "tooling"},
            ".devcontainer/devcontainer.json": {"compose", "docker", "tooling"},
            ".devcontainer/docker-compose.devcontainer.yml": {"compose", "docker", "tooling"},
            ".github/workflows/scorecard.yml": {"workflows"},
            ".github/zizmor.yml": {"workflows"},
            "scripts/tests/ci-gates-test.py": {"workflows"},
            "scripts/lint-workflows.sh": {"workflows"},
            "scripts/docker-images.sh": {"images"},
            "scripts/tests/docker-images-test.sh": {"images"},
            "scripts/check-security.sh": {"security", "tooling"},
            "scripts/make-help.sh": {"tooling"},
            "scripts/tests/check-workflow-tool-updates-test.py": {"tooling"},
            "scripts/check-skills.py": {"documentation"},
            "README.md": {"markdown", "documentation"},
            ".github/agents/implementer.agent.md": {"markdown", "documentation"},
            ".markdownlint.yml": {"markdown", "documentation", "tooling"},
            "doc/images/new.png": {"documentation"},
        }
        for number, (path, expected) in enumerate(cases.items()):
            with self.subTest(path=path):
                self.git("checkout", "--quiet", "-b", f"path-{number}", self.base)
                target = self.root / path
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_text("Updated fixture\n")
                self.commit()
                if expected & {"runtime", "composer", "yaml", "compose", "tooling"}:
                    expected = expected | {"configuration"}
                for event in ("pull_request", "push", "merge_group"):
                    self.detect(event)
                    selected = {key for key, value in self.outputs.items()
                                if value == "true" and key != "code"}
                    self.assertEqual(selected, expected)

    def test_shared_and_unknown_inputs_keep_all_checks(self):
        for number, path in enumerate((
            "Makefile", "Dockerfile", ".dockerignore", ".github/actions/php-bootstrap/action.yml",
            ".github/workflows/main.yml", "scripts/make.sh", "scripts/command-output.sh",
            "scripts/native-output.sh", "unclassified.new-format",
        )):
            with self.subTest(path=path):
                self.git("checkout", "--quiet", "-b", f"shared-{number}", self.base)
                target = self.root / path
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_text("Fixture\n")
                self.commit()
                self.detect()
                self.assertTrue(all(value == "true" for value in self.outputs.values()))

    def test_mixed_changes_union_checks_and_renames_keep_old_path_impact(self):
        (self.root / "ecs.php").write_text("Fixture\n")
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        self.detect()
        self.assertEqual(self.outputs["php"], "true")
        self.assertEqual(self.outputs["tests"], "false")
        self.assert_documentation(True, True)
        (self.root / "source.php").rename(self.root / "archived.md")
        self.commit()
        self.detect()
        for key in ("php", "tests", "docker"):
            self.assertEqual(self.outputs[key], "true")
        self.assert_documentation(True, True)

    def test_markdown_only_edit_skips_code(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        self.assertEqual(self.detect()[0], "code=false")
        self.assert_documentation(True, True)

    def test_code_only_edit_skips_documentation_on_diff_events(self):
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        for event in ("pull_request", "push", "merge_group"):
            with self.subTest(event=event):
                self.assertEqual(self.detect(event)[0], "code=true")
                self.assert_documentation(False, False)

    def test_documentation_tool_inputs_and_deleted_link_targets(self):
        for path, markdown in (
            (".markdownlint.json", True),
            ("scripts/markdown-output.sh", True),
            ("Makefile", True),
            ("Dockerfile", True),
            ("scripts/check-skills.py", False),
        ):
            with self.subTest(path=path):
                self.git("checkout", "--quiet", "-b", "fixture-" + path.replace("/", "-"),
                         self.base)
                target = self.root / path
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_text("Fixture\n")
                self.commit()
                self.detect()
                self.assert_documentation(markdown, True)
        self.git("checkout", "--quiet", "-b", "deleted-target", self.base)
        (self.root / "source.php").unlink()
        self.commit()
        self.detect()
        self.assert_documentation(False, True)

    def test_manual_unknown_empty_and_quoted_diffs_keep_documentation(self):
        self.detect()
        self.assert_documentation(True, True)
        self.assertTrue(all(value == "true" for value in self.outputs.values()))
        self.detect(base="missing-commit")
        self.assert_documentation(True, True)
        self.assertTrue(all(value == "true" for value in self.outputs.values()))
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        self.detect(event="workflow_dispatch")
        self.assert_documentation(True, True)
        self.assertTrue(all(value == "true" for value in self.outputs.values()))
        (self.root / "unusual\nname.md").write_text("Documentation\n")
        self.commit()
        self.detect()
        self.assert_documentation(True, True)
        self.assertTrue(all(value == "true" for value in self.outputs.values()))

    def test_push_uses_two_dot_comparison_not_pr_merge_base(self):
        (self.root / "README.md").write_text("Previous documentation\n")
        self.commit()
        previous = self.git("rev-parse", "HEAD")
        self.git("checkout", "--quiet", "-b", "divergent", self.base)
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        self.detect(event="push", base=previous)
        self.assert_documentation(True, True)
        self.detect(event="pull_request", base=previous)
        self.assert_documentation(False, False)

    def test_large_mixed_diff_cannot_lose_markdown_under_pipefail(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        for number in range(1200):
            (self.root / f"source-{number:04d}-with-a-long-name.php").write_text("<?php\n")
        self.commit()
        self.assertEqual(self.detect()[0], "code=true")
        self.assert_documentation(True, True)

    def test_markdown_add_delete_and_rename_skip_code(self):
        (self.root / "README.md").rename(self.root / "GUIDE.md")
        (self.root / "NEW.md").write_text("New documentation\n")
        self.commit()
        self.assertEqual(self.detect()[0], "code=false")
        self.assert_documentation(True, True)
        (self.root / "GUIDE.md").unlink()
        self.commit()
        self.assertEqual(self.detect()[0], "code=false")
        self.assert_documentation(True, True)

    def test_code_renamed_to_markdown_still_runs_code(self):
        (self.root / "source.php").rename(self.root / "source.md")
        self.commit()
        self.assertEqual(self.detect()[0], "code=true")
        self.assert_documentation(True, True)

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
