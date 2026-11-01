#!/usr/bin/env python3
"""Exercise change detection and analyzer gates from the actual workflow."""

import os
from pathlib import Path
import re
import subprocess
import tempfile
import textwrap
import unittest


WORKFLOW = Path(__file__).resolve().parents[2] / ".github/workflows/main.yml"
CONTENT = WORKFLOW.read_text()


def job(name):
    match = re.search(
        rf"^  {re.escape(name)}:\n(.*?)(?=^  [a-z][a-z-]*:\n|\Z)",
        CONTENT, re.MULTILINE | re.DOTALL,
    )
    if match is None:
        raise AssertionError(f"Missing workflow job: {name}")
    return match.group(1)


class AnalyzerWorkflowTest(unittest.TestCase):
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
        self.assertIn("    needs:\n      - ci-summary\n      - test\n", section)
        self.assertIn(
            "    if: ${{ success() && github.event_name == 'push' && "
            "github.ref == 'refs/heads/master' }}\n", section,
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
            "  group: ${{ github.workflow }}-${{ github.ref }}"
            "${{ github.event_name == 'schedule' && '-schedule' || '' }}\n"
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
                for result in ("success", "failure", "cancelled", "skipped", ""):
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
            self.assertIn("    if: ${{ github.event_name != 'schedule' }}\n", job(name))
            self.assertNotIn("needs.changes", job(name))
        for name in ("ci-summary", "ci-result"):
            self.assertIn("      - changes\n", job(name))
            self.assertIn("    if: ${{ !cancelled() }}\n", job(name))


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
