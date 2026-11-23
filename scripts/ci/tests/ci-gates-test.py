#!/usr/bin/env python3
"""Test CI gate decisions, workflow wiring, and job summaries offline."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make lint-workflows
#   To run only this suite from the repository root inside the container:
#     python3 scripts/ci/tests/ci-gates-test.py
#
# Requirements:
#   Python 3, Bash, and standard Linux command-line tools.
#
# Details:
#   The suite reads the checked-out workflow definitions and runs local helper
#   scripts with simulated CI results. It does not run hosted jobs or build images.
#
# Callers:
#   Scripts:
#     - `scripts/checks/lint-workflows.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py` before preparing validator binaries.

import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import textwrap
import unittest


WORKFLOW = Path(__file__).resolve().parents[3] / ".github/workflows/main.yml"
WORKFLOWS = WORKFLOW.parent


class CiGatesTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        """Keep the original job text for assertions about workflow wiring."""
        cls.workflow = WORKFLOW.read_text()
        cls.jobs = dict(re.findall(
            r"^  ([\w-]+):\n(.*?)(?=^  [\w-]+:|\Z)",
            cls.workflow, re.MULTILINE | re.DOTALL,
        ))

    def test_statistics_summary_preserves_output_and_failure(self):
        helper = WORKFLOW.parents[2] / "scripts/ci/ci-statistics-summary.sh"
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            summary = root / "summary"
            environment = {**os.environ, "GITHUB_STEP_SUMMARY": str(summary),
                           "COLOR": "always", "NATIVE_COLOR": "always", "NO_COLOR": ""}
            command = (
                "printf 'color=%s native=%s no_color=%s\\n```\\n' \"$COLOR\" \"$NATIVE_COLOR\" \"$NO_COLOR\"; "
                "echo diagnostic >&2; exit \"$1\""
            )
            for status in (0, 7):
                summary.write_text("")
                result = subprocess.run(
                    ["bash", str(helper), "run", "Example", "bash", "-c", command, "_", str(status)],
                    env=environment, text=True, capture_output=True, check=False,
                )
                self.assertEqual(result.returncode, status)
                self.assertEqual(result.stdout, "color=never native=never no_color=1\n```\n")
                self.assertEqual(result.stderr, "diagnostic\n")
                self.assertIn("    color=never native=never no_color=1\n    ```\n", summary.read_text())
                self.assertEqual("Command failed" in summary.read_text(), status != 0)
            output = root / "output"
            output.write_text("first\nsecond\n")
            result = subprocess.run(
                ["bash", str(helper), "file", "Existing", str(output)],
                env=environment, text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("## Existing\n\n    first\n    second\n", summary.read_text())
            output.unlink()
            result = subprocess.run(
                ["bash", str(helper), "file", "Missing", str(output)],
                env=environment, text=True, capture_output=True, check=False,
            )
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("missing", result.stderr)
            result = subprocess.run(
                ["bash", str(helper), "run", "Example", "true"],
                env={**environment, "GITHUB_STEP_SUMMARY": str(root)},
                text=True, capture_output=True, check=False,
            )
            self.assertNotEqual(result.returncode, 0)

    def test_selected_statistics_require_complete_known_formats(self):
        helper = WORKFLOW.parents[2] / "scripts/ci/ci-statistics-summary.sh"
        fixtures = {
            "versions": (
                "  PHP 8.5.12 (cli) (built: test)\n  PHP 8.5.12 (fpm-fcgi)\n"
                "  Symfony: v8.1.11\n    Composer version 2.11.1\n"
                "      phpunit/phpunit: 13.4.6\n      other/package: 1.0\n",
                ("PHP 8.5.12 (cli)", "Symfony: v8.1.11",
                 "Composer version 2.11.1", "phpunit/phpunit: 13.4.6"),
                ("fpm-fcgi", "other/package"),
            ),
            "insights": (
                "[CODE] 97.7 pts within 10094 lines\n[COMPLEXITY] 95.5 pts\n"
                "[ARCHITECTURE] 100 pts\n[MISC] 100 pts on coding style\n"
                "• [Code] detailed findings\n",
                ("[CODE]", "[COMPLEXITY]", "[ARCHITECTURE]", "[MISC]"),
                ("detailed findings",),
            ),
        }
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            source = root / "input"
            summary = root / "summary"
            for mode, (fixture, expected, excluded) in fixtures.items():
                with self.subTest(mode=mode):
                    source.write_text(fixture)
                    summary.write_text("")
                    command = ["bash", str(helper), mode, "Summary", str(source)]
                    environment = {**os.environ, "GITHUB_STEP_SUMMARY": str(summary)}
                    result = subprocess.run(command, env=environment, text=True, capture_output=True)
                    self.assertEqual(result.returncode, 0, result.stderr)
                    if mode == "versions":
                        self.assertEqual(
                            summary.read_text(),
                            "## Summary\n\n"
                            "    PHP 8.5.12 (cli) (built: test)\n"
                            "    Symfony: v8.1.11\n"
                            "    Composer version 2.11.1\n"
                            "    phpunit/phpunit: 13.4.6\n\n\n",
                        )
                    for text in expected:
                        self.assertIn(text, summary.read_text())
                    for text in excluded:
                        self.assertNotIn(text, summary.read_text())
                    summary.write_text("")
                    source.write_text(fixture.replace(next(line for line in fixture.splitlines()
                                                          if expected[-1] in line), ""))
                    result = subprocess.run(command, env=environment, text=True, capture_output=True)
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn("Could not identify", result.stderr)
                    self.assertEqual(summary.read_text(), "")

    def test_core_job_overviews_report_actual_outcomes(self):
        jobs = {
            "lint-configuration": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "INFO": ("application-info", "Application information"),
                "COMPOSER": ("composer", "Composer validation"), "REQUIREMENTS": ("requirements", "Runtime requirements"),
                "YAML": ("yaml", "Symfony YAML"),
                "TOOLING": ("tooling", "Development tooling checks"),
                "PYRIGHT": ("pyright", "Pyright"),
                "COMPOSE": ("compose", "Compose and Dev Container"),
            },
            "lint-documentation": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "MARKDOWN": ("markdown", "Markdown lint"),
                "DOCUMENTATION": ("documentation", "Metadata and links"),
            },
            "lint-workflows": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "VALIDATION": ("validation", "Workflow and action validation"),
            },
            "security-check": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"),
                "SYMFONY_SECURITY": ("symfony-security", "Symfony security checker"),
                "DEPENDENCY_SECURITY": ("dependency-security", "Installed dependency audit"),
            },
            "gitleaks": {"SCAN": ("scan", "Gitleaks scan")},
            "docker-image-verify-pins": {"VERIFY": ("verify", "Multi-platform pin verification")},
            "docker-image-check-updates": {
                "AUTHENTICATION": ("authentication", "Docker Hub credential check"),
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "TESTS": ("tests", "Helper regression tests"),
                "UPDATES": ("updates", "Image version check"),
            },
            "statistics": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "VERSIONS": ("versions", "Tool versions"),
                "PROJECT": ("project", "Project files"),
                "PR_CHANGES": ("pr-changes", "PR changes"),
                "LICENSES": ("licenses", "Dependency licenses"),
                "PLATFORMS": ("platforms", "Docker builder platforms"),
            },
            "lightweight-analyzers": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "PHPCS": ("phpcs", "PHP CodeSniffer"),
                "ECS": ("ecs", "ECS"), "PHPLINT": ("phplint", "PHPLint"),
                "PARALLEL_LINT": ("parallel-lint", "PHP Parallel Lint"),
                "PHPLOC": ("phploc", "PHPLoc"), "ARTIFACT": ("artifact", "PHPLoc artifact"),
            },
            "static-analyzers": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "ANALYSIS": ("analyzer", "Analysis"),
                "ARTIFACT": ("artifact", "Psalm artifact"),
            },
            "test": {
                "BOOTSTRAP": ("bootstrap", "Bootstrap"), "CONFIGURE": ("configure", "CI configuration"),
                "INSTALL": ("install", "Dependency installation"), "PHPUNIT": ("phpunit", "PHPUnit"),
                "METRICS": ("metrics", "PhpMetrics report"), "VERIFY": ("verify-reports", "Report verification"),
                "SUMMARY": ("coverage-summary", "Test and coverage summary"),
                "CLOVER": ("clover", "Clover artifact"), "JUNIT": ("junit", "JUnit artifact"),
                "COVERAGE": ("coverage", "HTML coverage artifact"),
                "METRICS_ARTIFACT": ("metrics-artifact", "PhpMetrics artifact"),
            },
            "report": {
                "REPORTING": ("reporting", "Reporting selection"), "CLOVER": ("clover", "Clover download"),
                "JUNIT": ("junit", "JUnit download"), "BOOTSTRAP": ("bootstrap", "Bootstrap"),
                "INSTALL": ("install-coveralls", "Coveralls installation"),
                "COVERALLS": ("coveralls", "Coveralls upload"),
                "PREPARE": ("prepare-sonar", "Sonar report preparation"), "SONAR": ("sonar", "SonarQube scan"),
            },
            "docker-build": {
                "BUILDX": ("buildx", "Buildx setup"), "BUILD": ("build", "Image build"),
                "ARTIFACT": ("artifact", "Image artifact upload"),
            },
            "docker-scan": {
                "IMAGE": ("image", "Image artifact download"), "SCAN": ("scan", "Blocking Trivy scan"),
                "SARIF": ("sarif", "PR SARIF generation"), "UPLOAD": ("upload", "Code scanning upload"),
            },
        }
        for job, outcomes in jobs.items():
            with self.subTest(job=job), tempfile.TemporaryDirectory() as directory:
                content = self.jobs[job]
                matches = re.findall(
                    r"^      - name: Publish job overview\n(.*?)(?=^      - |\Z)",
                    content, re.MULTILINE | re.DOTALL,
                )
                self.assertEqual(len(matches), 1)
                step = matches[0]
                condition = "!cancelled()"
                if job == "report":
                    condition += " && steps.checkout-report.outcome == 'success'"
                self.assertIn(f"if: ${{{{ {condition} }}}}", step)
                self.assertIn("continue-on-error: true", step)
                self.assertIn("PRIOR_STATUS: ${{ job.status }}", step)
                title_match = re.search(
                    r"^          OVERVIEW_TITLE: (.+)$", step, re.MULTILINE,
                )
                assert title_match is not None
                title = title_match.group(1)
                self.assertIn("OVERVIEW_COLLAPSED: 'true'", step)
                for variable, (step_id, _) in outcomes.items():
                    self.assertIn(f"        id: {step_id}\n", content)
                    self.assertIn(f"{variable}: ${{{{ steps.{step_id}.outcome || 'skipped' }}}}", step)
                shell = textwrap.dedent(step.split("        run: |\n", 1)[1])
                variants = (
                    ("development", "production") if job in ("docker-build", "docker-scan")
                    else ("Psalm", "PHPStan", "PHP Insights") if job == "static-analyzers"
                    else ("",)
                )
                scenarios: list[tuple[str | None, str]] = [
                    (None, variant) for variant in variants
                ]
                scenarios.extend((failed, variants[0]) for failed in outcomes)
                for failed, variant in scenarios:
                    rendered_title = title.replace("${{ matrix.analyzer.name }}", variant)
                    values = dict.fromkeys(outcomes, "success" if failed is None else "skipped")
                    if failed is not None:
                        values[failed] = "failure"
                    summary = Path(directory) / "summary"
                    result = subprocess.run(
                        ["bash", "-euo", "pipefail", "-c", shell], cwd=WORKFLOW.parents[2],
                        env={**os.environ, "GITHUB_STEP_SUMMARY": str(summary),
                             "OVERVIEW_TITLE": rendered_title, "OVERVIEW_COLLAPSED": "true",
                             "EVENT_NAME": "pull_request",
                             "PRIOR_STATUS": "success" if failed is None else "failure",
                             "ANALYZER": variant if job == "static-analyzers" else "",
                             "TARGET": variant if job in (
                                 "docker-build", "docker-scan",
                             ) else "", **values},
                        capture_output=True, text=True, check=False,
                    )
                    self.assertEqual(result.returncode, 0, result.stderr)
                    output = summary.read_text()
                    self.assertTrue(output.startswith("<details>\n" if failed is None else "<details open>\n"))
                    self.assertIn(f"<summary>{rendered_title}", output)
                    self.assertTrue(output.endswith("\n</details>\n"))
                    self.assertIn(f"Prior steps: **{'success' if failed is None else 'failure'}**", output)
                    for variable, (_, label) in outcomes.items():
                        self.assertIn(f"| {label} | {values[variable]} |", output)
                    if job in ("docker-build", "docker-scan"):
                        self.assertIn(f"Image: {variant}", output)
                    if job == "static-analyzers":
                        self.assertIn(f"| Analyzer | {variant} |", output)
                    summary.unlink()

    def test_standalone_workflow_overviews_report_actual_step_outcomes(self):
        workflows = {
            "dependency-review.yml": {"REVIEW": ("review", "Dependency review")},
            "codeql.yml": {
                "INITIALIZE": ("initialize", "CodeQL initialization"),
                "ANALYZE": ("analyze", "Workflow analysis and upload"),
            },
            "scorecard.yml": {
                "ANALYSIS": ("analysis", "Scorecard analysis"),
                "ARTIFACT": ("artifact", "SARIF artifact upload"),
                "UPLOAD": ("upload", "Code scanning upload"),
            },
            "vulnerability-scan.yml": {
                "BUILD": ("build", "Image build"), "SARIF": ("sarif", "Trivy SARIF report"),
                "UPLOAD": ("upload", "Code scanning upload"), "SCAN": ("scan", "Blocking Trivy scan"),
            },
            "docker-image-updates.yml": {
                "BOOTSTRAP": ("bootstrap", "Maintenance container"),
                "TESTS": ("tests", "Helper regression tests"), "UPDATE": ("update", "Image pin updates"),
                "VERIFY": ("verify", "Pin verification"),
                "COMPOSER": ("composer", "Composer synchronization"),
                "DOCTRINE": ("doctrine", "Doctrine synchronization"),
                "CI_MARIADB": ("ci-mariadb", "CI MariaDB synchronization"),
                "DETAILS": ("details", "Change report preparation"),
                "PULL_REQUEST": ("pull-request", "Pull request action"),
            },
            "action-updates.yml": {
                "UPDATES": ("updates", "Action and workflow tool update check"),
            },
        }
        for filename, outcomes in workflows.items():
            with self.subTest(workflow=filename), tempfile.TemporaryDirectory() as directory:
                content = (WORKFLOWS / filename).read_text()
                match = re.search(
                    r"^      - name: Publish workflow run overview\n"
                    r"(.*?)(?=^      - |\Z)", content, re.MULTILINE | re.DOTALL,
                )
                assert match is not None
                step = match.group(1)
                if filename == "dependency-review.yml":
                    self.assertIn(
                        "if: ${{ !cancelled() && (github.event_name != 'pull_request' || "
                        "!github.event.pull_request.draft) }}", step,
                    )
                else:
                    self.assertIn("if: ${{ !cancelled() }}", step)
                self.assertIn("continue-on-error: true", step)
                self.assertIn("PRIOR_STATUS: ${{ job.status }}", step)
                for variable, (step_id, _) in outcomes.items():
                    self.assertIn(f"        id: {step_id}\n", content)
                    self.assertIn(
                        f"{variable}: ${{{{ steps.{step_id}.outcome || 'skipped' }}}}", step,
                    )
                shell = textwrap.dedent(step.split("        run: |\n", 1)[1])
                self.assertIn("bash scripts/ci/ci-workflow-overview.sh", shell)
                summary = Path(directory) / "summary"
                (Path(directory) / "docker-image-changes.md").write_text("## Proposed changes\n")
                extra = dict(
                    RUNNER_TEMP=directory, BASE_BRANCH="master", PR_URL="", PR_OPERATION="",
                    PHP_READINESS_SUMMARY="", PHP_READINESS_RESULT="failure",
                    UPDATE_RESULT="unavailable",
                )
                scenarios = [("success", None)] + [
                    ("failure", variable) for variable in outcomes
                ]
                for status, failed in scenarios:
                    values = {
                        variable: "skipped" if status == "failure" else "success"
                        for variable in outcomes
                    }
                    if failed is not None:
                        values[failed] = status
                    result = subprocess.run(
                        ["bash", "-euo", "pipefail", "-c", shell],
                        env={**os.environ, "GITHUB_STEP_SUMMARY": str(summary),
                             "EVENT_NAME": "workflow_dispatch", "PRIOR_STATUS": status,
                             "TARGET": "production" if filename == "vulnerability-scan.yml" else "",
                             **extra, **values},
                        cwd=WORKFLOW.parents[2],
                        capture_output=True, text=True, check=False,
                    )
                    self.assertEqual(result.returncode, 0, result.stderr)
                    output = summary.read_text()
                    self.assertIn("## Workflow run overview", output)
                    self.assertIn(f"Prior steps: **{status}**", output)
                    for variable, (_, label) in outcomes.items():
                        self.assertIn(f"| {label} | {values[variable]} |", output)
                    if filename == "vulnerability-scan.yml":
                        self.assertIn("Image: production", output)
                    else:
                        self.assertNotIn("Image:", output)
                    if filename == "docker-image-updates.yml":
                        self.assertIn("> [!WARNING]", output)
                        self.assertIn("**Result: Unavailable**", output)
                        self.assertIn("readiness job: failure", output)
                    summary.unlink()
                unavailable = subprocess.run(
                    ["bash", "-euo", "pipefail", "-c", shell], cwd=directory,
                    env={**os.environ, "GITHUB_STEP_SUMMARY": str(summary),
                         "EVENT_NAME": "push", "PRIOR_STATUS": "failure",
                         "TARGET": "", **extra, **dict.fromkeys(outcomes, "skipped")},
                    text=True, capture_output=True, check=False,
                )
                self.assertNotEqual(unavailable.returncode, 0)
                self.assertIn("ci-workflow-overview.sh", unavailable.stderr)

    def test_overview_helper_rejects_invalid_inputs_and_write_errors(self):
        helper = WORKFLOW.parents[2] / "scripts/ci/ci-workflow-overview.sh"
        environment = {**os.environ, "EVENT_NAME": "push", "PRIOR_STATUS": "success", "TARGET": ""}
        for arguments in ([], ["footer"], ["footer", "check"], ["footer", "check", "success", "extra"]):
            with self.subTest(arguments=arguments):
                result = subprocess.run(
                    ["bash", str(helper), *arguments], env=environment,
                    text=True, capture_output=True, check=False,
                )
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("Usage:", result.stderr)
        with tempfile.TemporaryDirectory() as directory:
            result = subprocess.run(
                ["bash", "-c", 'bash "$1" footer check success > "$2"', "_", str(helper), directory],
                env=environment, text=True, capture_output=True, check=False,
            )
            self.assertNotEqual(result.returncode, 0)

    def test_scheduled_image_enforcement_is_independent_of_reporting(self):
        workflow = (WORKFLOWS / "vulnerability-scan.yml").read_text()
        steps = dict(re.findall(
            r"^      - name: ([^\n]+)\n(.*?)(?=^      - |\Z)",
            workflow, re.MULTILINE | re.DOTALL,
        ))
        build = steps["Build the Docker image"]
        sarif = steps["Create Trivy SARIF report"]
        upload = steps["Upload Trivy SARIF report"]
        scan = steps["Scan image with Trivy"]
        self.assertIn("        id: build\n", build)
        self.assertIn("        id: sarif\n", sarif)
        self.assertIn("        id: upload\n", upload)
        self.assertIn("        id: scan\n", scan)
        self.assertLess(workflow.index("name: Upload Trivy SARIF report"),
                        workflow.index("name: Scan image with Trivy"))
        condition = re.search(r"^        if: \$\{\{ (.+) \}\}$", scan, re.MULTILINE)
        assert condition is not None
        self.assertEqual(condition.group(1),
                         "!cancelled() && steps.build.outcome == 'success'")
        for build_result in ("success", "failure", "skipped"):
            for sarif_result in ("success", "failure", "skipped"):
                for upload_result in ("success", "failure", "skipped"):
                    for cancelled in (False, True):
                        with self.subTest(build=build_result, sarif=sarif_result,
                                          upload=upload_result, cancelled=cancelled):
                            expression = condition.group(1).replace(
                                "!cancelled()", repr(not cancelled),
                            ).replace("steps.build.outcome", repr(build_result)).replace(
                                "&&", "and",
                            )
                            result = eval(expression, {"__builtins__": {}})
                            self.assertEqual(result, not cancelled and build_result == "success")
        for step in (sarif, upload, scan):
            self.assertNotIn("continue-on-error", step)
        self.assertIn("exit-code: '1'", scan)

    def test_action_update_workflow_preserves_checker_outcomes(self):
        workflow = (WORKFLOWS / "action-updates.yml").read_text()
        self.assertIn("  schedule:\n", workflow)
        self.assertIn("    - cron: '17 6 * * 3'\n", workflow)
        self.assertIn("  workflow_dispatch:\n", workflow)
        self.assertIn("permissions:\n  contents: read\n", workflow)
        self.assertIn("          persist-credentials: false\n", workflow)
        self.assertIn("          install-dependencies: 'false'\n", workflow)
        self.assertIn("          print-versions: 'false'\n", workflow)
        self.assertIn("        if: ${{ failure() }}\n", workflow)
        self.assertIn("        if: ${{ always() }}\n", workflow)
        match = re.search(
            r"^      - name: Check Action and workflow tool updates\n"
            r".*?^        run: \|\n(.*?)(?=^      - |\Z)",
            workflow, re.MULTILINE | re.DOTALL,
        )
        assert match is not None
        shell = textwrap.dedent(match.group(1))
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            docker = root / "docker"
            docker.write_text(
                '#!/bin/sh\nprintf "%s\\n" "$*" > "$DOCKER_ARGS"\nexit "$CHECKER_STATUS"\n'
            )
            docker.chmod(0o755)
            for status, expected, message in (
                (0, 0, "current"),
                (1, 0, "updates available"),
                (2, 2, "incomplete"),
                (17, 17, "incomplete"),
            ):
                with self.subTest(checker_status=status):
                    output = root / "output"
                    args = root / "args"
                    result = subprocess.run(
                        ["bash", "-euo", "pipefail", "-c", shell], cwd=root,
                        env={**os.environ, "PATH": f"{root}:{os.environ['PATH']}",
                             "DOCKER_ARGS": str(args), "CHECKER_STATUS": str(status),
                             "GITHUB_OUTPUT": str(output)},
                        text=True, capture_output=True, check=False,
                    )
                    self.assertEqual(result.returncode, expected, result.stderr)
                    self.assertEqual(output.read_text(), f"result={message}\n")
                    self.assertEqual(
                        args.read_text().strip(),
                        "compose -f compose.yaml -f docker/ci-compose.yaml exec -T php "
                        "bash scripts/checks/check-action-updates.sh",
                    )
                    if status > 1:
                        self.assertIn(f"::error::Action and workflow tool update checks failed "
                                      f"(exit {status}).", result.stdout)
                    output.unlink()

    def test_action_update_overview_displays_finding_and_step_outcome(self):
        workflow = (WORKFLOWS / "action-updates.yml").read_text()
        match = re.search(
            r"^      - name: Publish workflow run overview\n"
            r"(.*?)(?=^      - |\Z)", workflow, re.MULTILINE | re.DOTALL,
        )
        assert match is not None
        step = match.group(1)
        self.assertIn("UPDATE_RESULT: ${{ steps.updates.outputs.result || 'unavailable' }}", step)
        shell = textwrap.dedent(step.split("        run: |\n", 1)[1])
        for outcome, finding in (
            ("success", "current"), ("success", "updates available"),
            ("failure", "incomplete"), ("failure", "unavailable"),
            ("skipped", "unavailable"),
        ):
            with self.subTest(outcome=outcome, finding=finding), tempfile.TemporaryDirectory() as directory:
                summary = Path(directory) / "summary"
                result = subprocess.run(
                    ["bash", "-euo", "pipefail", "-c", shell], cwd=WORKFLOW.parents[2],
                    env={**os.environ, "GITHUB_STEP_SUMMARY": str(summary), "EVENT_NAME": "schedule",
                         "PRIOR_STATUS": "success" if outcome == "success" else "failure",
                         "UPDATES": outcome, "UPDATE_RESULT": finding},
                    text=True, capture_output=True, check=False,
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn(f"| Action and workflow tool update check | {outcome} |", summary.read_text())
                self.assertIn(f"| Update finding | {finding} |", summary.read_text())

    def test_gates_run_after_failed_dependencies_but_not_cancelled_workflows(self):
        for job in ("ci-summary", "ci-result"):
            with self.subTest(job=job):
                conditions = re.findall(
                    r"^    if: (.+)$", self.jobs[job], re.MULTILINE,
                )
                # Keep failed prerequisites visible and release cancelled runs.
                condition = "${{ !cancelled() }}"
                if job == "ci-summary":
                    condition = (
                        "${{ !cancelled() && (github.event_name != 'pull_request' || "
                        "!github.event.pull_request.draft) }}"
                    )
                self.assertEqual(conditions, [condition])

    def test_container_cleanup_remains_unconditional(self):
        cleanup = re.findall(
            r"^        name: Stop PHP development container\n"
            r"        if: (.+)\n"
            r"        run: bash scripts/make/make\.sh ci-stop$",
            self.workflow, re.MULTILINE,
        )
        self.assertTrue(cleanup, "Expected unconditional container cleanup")
        self.assertTrue(all(condition == "${{ always() }}" for condition in cleanup))

    def test_every_bootstrap_consumer_finishes_with_container_cleanup(self):
        consumers = {
            name: body for name, body in self.jobs.items()
            if "uses: ./.github/actions/php-bootstrap\n" in body
        }
        self.assertIn("test", consumers)
        for name, body in consumers.items():
            with self.subTest(job=name):
                if name == "lint-configuration":
                    self.assertTrue(body.rstrip().endswith("run: bash scripts/make/make.sh ci-stop"))
                elif name == "report":
                    self.assertTrue(body.rstrip().endswith("run: bash scripts/make/make.sh ci-stop"))
                    self.assertIn(
                        "if: ${{ always() && steps.bootstrap.outcome != 'skipped' }}", body,
                    )
                else:
                    self.assertTrue(body.rstrip().endswith("- *stop_php_step"))
                    self.assertEqual(body.count("- *stop_php_step"), 1)

    def test_gate_scripts_are_checked_out_without_prerequisite_success_guards(self):
        for job, script in (
            ("ci-summary", "ci-prerequisite-gate.sh"),
            ("ci-result", "ci-result-gate.sh"),
        ):
            with self.subTest(job=job):
                steps = self.jobs[job].split("    steps:\n", 1)[1]
                if job == "ci-result":
                    steps, count = re.subn(
                        r"\n      - name: Block draft pull requests\n.*?(?=\n      - )",
                        "", steps, flags=re.DOTALL,
                    )
                    self.assertEqual(count, 1)
                self.assertEqual(
                    steps.split("        env:\n", 1)[0],
                    "      - *harden_runner_step\n\n"
                    "      - *checkout_step\n\n"
                    f"      - name: Validate {'prerequisite' if job == 'ci-summary' else 'final'} job results\n",
                )
                self.assertEqual(
                    re.findall(r"^        run: (.+)$", steps, re.MULTILINE),
                    [f"bash scripts/ci/{script}"],
                )
                self.assertNotIn("        if:", steps)

    def test_every_php_consumer_diagnoses_failure_before_cleanup(self):
        for name, body in self.jobs.items():
            if "uses: ./.github/actions/php-bootstrap\n" not in body:
                continue
            with self.subTest(job=name):
                if name in ("lint-configuration", "report"):
                    self.assertLess(body.index("name: Diagnose PHP container failure"),
                                    body.index("name: Stop PHP development container"))
                    condition = "failure()" + (
                        " && steps.bootstrap.outcome != 'skipped'" if name == "report" else ""
                    )
                    self.assertIn(
                        f"        if: ${{{{ {condition} }}}}\n"
                        "        timeout-minutes: 2\n"
                        "        run: bash scripts/ci/ci-failure-diagnostics.sh\n", body,
                    )
                else:
                    self.assertIn(
                        "      - *failure_diagnostics_step\n      - *stop_php_step\n", body,
                    )

    def test_full_history_is_limited_to_history_consumers(self):
        anchors = dict(re.findall(
            r"^      - &(checkout\w+)\n(.*?)(?=^      - |\Z)",
            self.workflow, re.MULTILINE | re.DOTALL,
        ))
        self.assertEqual(set(anchors), {"checkout_step", "checkout_full_history_step"})
        history_consumers = {"changes", "gitleaks", "lightweight-analyzers", "report", "statistics"}
        self.assertTrue(history_consumers <= self.jobs.keys())
        job_names = re.findall(
            r"^  ([\w-]+):$", self.workflow.split("jobs:\n", 1)[1], re.MULTILINE,
        )
        for name in job_names:
            body = self.jobs[name]
            with self.subTest(job=name):
                checkouts = []
                for step in re.findall(
                    r"^      - (.*?)(?=^      - |\Z)", body, re.MULTILINE | re.DOTALL,
                ):
                    if step.strip().startswith("*checkout"):
                        step = anchors[step.strip()[1:]]
                    if "uses: actions/checkout@" in step:
                        checkouts.append(step)
                self.assertEqual(len(checkouts), 1)
                depth = "0" if name in history_consumers else "1"
                self.assertIn(f"          fetch-depth: '{depth}'\n", checkouts[0])
                self.assertIn("          persist-credentials: false\n", checkouts[0])
                self.assertRegex(checkouts[0], r"uses: actions/checkout@[a-f0-9]{40}\b")
                if name == "report":
                    self.assertIn("          ref: ${{ github.sha }}\n", checkouts[0])
                    self.assertIn(
                        "        if: ${{ steps.reporting.outputs.enabled == 'true' }}\n",
                        checkouts[0],
                    )

    def test_missing_shared_helper_or_required_inputs_fail_explicitly(self):
        for script in ("ci/ci-prerequisite-gate.sh", "ci/ci-result-gate.sh"):
            with self.subTest(script=script), tempfile.TemporaryDirectory() as directory:
                root = Path(directory)
                (root / "ci").mkdir()
                shutil.copyfile(WORKFLOW.parents[2] / "scripts" / script, root / script)
                environment = {"PATH": os.environ["PATH"]}
                missing_helper = subprocess.run(
                    ["bash", str(root / script)], cwd=WORKFLOW.parent, env=environment,
                    text=True, capture_output=True, check=False, timeout=10,
                )
                self.assertNotEqual(missing_helper.returncode, 0)
                self.assertIn("ci-gate-common.sh", missing_helper.stderr)
                shutil.copyfile(
                    WORKFLOW.parents[2] / "scripts/ci/ci-gate-common.sh",
                    root / "ci/ci-gate-common.sh",
                )
                missing_inputs = subprocess.run(
                    ["bash", str(root / script)], cwd=WORKFLOW.parent, env=environment,
                    text=True, capture_output=True, check=False, timeout=10,
                )
                self.assertNotEqual(missing_inputs.returncode, 0)
                self.assertIn("EVENT_NAME: unbound variable", missing_inputs.stderr)

    def test_image_scans_consume_build_artifacts_without_rebuilding(self):
        build = self.jobs["docker-build"]
        scan = self.jobs["docker-scan"]
        self.assertNotIn("aquasecurity/trivy-action@", build)
        self.assertIn("outputs: type=docker,dest=${{ runner.temp }}/docker-image.tar", build)
        self.assertIn("uses: actions/upload-artifact@", build)
        self.assertIn("path: ${{ runner.temp }}/docker-image.tar", build)
        self.assertIn("if-no-files-found: error", build)
        self.assertNotIn("uses: docker/build-push-action@", scan)
        self.assertIn("uses: actions/download-artifact@", scan)
        self.assertIn("path: ${{ runner.temp }}/docker-image", scan)
        self.assertIn("input: ${{ runner.temp }}/docker-image/docker-image.tar", scan)
        self.assertIn("uses: aquasecurity/trivy-action@", scan)
        self.assertIn("exit-code: '1'", scan)
        self.assertIn("severity: 'CRITICAL,HIGH'", scan)
        self.assertIn("ignore-unfixed: true", scan)
        self.assertIn("vuln-type: 'os,library'", scan)
        self.assertIn("    needs:\n      - changes\n      - docker-build\n", scan)
        self.assertIn("      - docker-scan\n", self.jobs["ci-result"])
        for job in (build, scan):
            self.assertIn("name: docker-image-${{ matrix.target }}", job)
            self.assertIn("target: [development, production]", job)
            self.assertIn("fail-fast: false", job)
            self.assertIn("needs.changes.outputs.docker == 'true'", job)
        self.assertIn(
            "if: ${{ github.event_name != 'schedule' && "
            "needs.changes.outputs.docker == 'true' }}", build,
        )
        self.assertIn(
            "if: ${{ !cancelled() && github.event_name != 'schedule' && "
            "needs.changes.result == 'success' && needs.changes.outputs.docker == 'true' && "
            "(needs.docker-build.result == 'success' || needs.docker-build.result == 'failure') }}",
            scan,
        )

    def test_pr_image_sarif_is_scoped_and_independent_of_scan_result(self):
        scan = self.jobs["docker-scan"]
        self.assertIn("    permissions:\n      contents: read\n      security-events: write\n", scan)
        steps = dict(re.findall(
            r"^      - name: ([^\n]+)\n(.*?)(?=^      - |\Z)",
            scan, re.MULTILINE | re.DOTALL,
        ))
        image = steps["Download Docker image for scanning"]
        blocking = steps["Scan Docker image with Trivy vulnerability scanner"]
        sarif = steps["Generate PR image scan SARIF"]
        upload = steps["Upload PR image scan SARIF"]
        self.assertIn("        id: image\n", image)
        self.assertNotIn("        if:", blocking)
        self.assertLess(scan.index("name: Scan Docker image with Trivy vulnerability scanner"),
                        scan.index("name: Generate PR image scan SARIF"))
        self.assertIn("        id: sarif\n", sarif)
        condition = re.search(r"^        if: \$\{\{ (.+) \}\}$", sarif, re.MULTILINE)
        assert condition is not None
        self.assertEqual(
            condition.group(1),
            "!cancelled() && steps.image.outcome == 'success' && "
            "github.event_name == 'pull_request' && "
            "github.event.pull_request.head.repo.full_name == github.repository",
        )
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch"):
            for same_repo in (False, True):
                for image_result in ("success", "failure", "skipped"):
                    for cancelled in (False, True):
                        with self.subTest(event=event, same_repo=same_repo,
                                          image=image_result, cancelled=cancelled):
                            expression = condition.group(1).replace(
                                "!cancelled()", repr(not cancelled),
                            ).replace("steps.image.outcome", repr(image_result)).replace(
                                "github.event_name", repr(event),
                            ).replace("github.event.pull_request.head.repo.full_name",
                                      repr("owner/repo" if same_repo else "fork/repo")).replace(
                                "github.repository", repr("owner/repo"),
                            ).replace("&&", "and")
                            result = eval(expression, {"__builtins__": {}})
                            self.assertEqual(result, not cancelled and image_result == "success"
                                             and event == "pull_request" and same_repo)
        for item in (blocking, sarif):
            self.assertIn("input: ${{ runner.temp }}/docker-image/docker-image.tar", item)
            self.assertIn("ignore-unfixed: true", item)
            self.assertIn("vuln-type: 'os,library'", item)
            self.assertIn("severity: 'CRITICAL,HIGH'", item)
        self.assertIn("exit-code: '1'", blocking)
        self.assertIn("exit-code: '0'", sarif)
        self.assertIn("uses: aquasecurity/trivy-action@", sarif)
        self.assertIn("format: sarif", sarif)
        self.assertIn("limit-severities-for-sarif: 'true'", sarif)
        self.assertIn("output: trivy-${{ matrix.target }}.sarif", sarif)
        self.assertIn("if: ${{ !cancelled() && steps.sarif.outcome == 'success' }}", upload)
        self.assertIn("uses: github/codeql-action/upload-sarif@", upload)
        self.assertIn("sarif_file: trivy-${{ matrix.target }}.sarif", upload)
        self.assertIn("category: trivy-${{ matrix.target }}", upload)
        self.assertLess(scan.index("name: Generate PR image scan SARIF"),
                        scan.index("name: Upload PR image scan SARIF"))
        without_overview = re.sub(
            r"^      - name: Publish job overview\n.*?(?=^      - |\Z)",
            "", scan, flags=re.MULTILINE | re.DOTALL,
        )
        self.assertNotIn("continue-on-error", without_overview)

    def test_trivy_policies_across_image_workflows(self):
        workflows = {
            "core": self.jobs["docker-scan"],
            "periodic": (WORKFLOWS / "vulnerability-scan.yml").read_text(),
        }
        names = {
            "core": ("Scan Docker image with Trivy vulnerability scanner",
                     "Generate PR image scan SARIF", "Upload PR image scan SARIF"),
            "periodic": ("Scan image with Trivy", "Create Trivy SARIF report",
                         "Upload Trivy SARIF report"),
        }
        trivy_actions = set()
        for workflow_name, content in workflows.items():
            steps = dict(re.findall(
                r"^      - name: ([^\n]+)\n(.*?)(?=^      - |\Z)",
                content, re.MULTILINE | re.DOTALL,
            ))
            blocking, sarif, upload = (steps[name] for name in names[workflow_name])
            with self.subTest(workflow=workflow_name):
                for step in (blocking, sarif):
                    action = re.search(
                        r"^        uses: (aquasecurity/trivy-action@[a-f0-9]{40})",
                        step, re.MULTILINE,
                    )
                    assert action is not None
                    trivy_actions.add(action.group(1))
                    self.assertRegex(step, r"(?m)^          ignore-unfixed: '?true'?$")
                    self.assertIn("          vuln-type: 'os,library'\n", step)
                    self.assertIn("          severity: 'CRITICAL,HIGH'\n", step)
                self.assertIn("          exit-code: '1'\n", blocking)
                self.assertIn("          exit-code: '0'\n", sarif)
                self.assertIn("          format: sarif\n", sarif)
                self.assertIn("          output: trivy-${{ matrix.target }}.sarif\n", sarif)
                self.assertIn("          sarif_file: trivy-${{ matrix.target }}.sarif\n", upload)
                self.assertIn("          category: trivy-${{ matrix.target }}\n", upload)
                self.assertNotIn("continue-on-error", blocking)
                if workflow_name == "core":
                    self.assertIn("          limit-severities-for-sarif: 'true'\n", sarif)
                else:
                    self.assertNotIn("limit-severities-for-sarif:", sarif)
        self.assertEqual(len(trivy_actions), 1)

    def test_image_tags_are_valid_and_independent_of_git_ref_names(self):
        build = self.jobs["docker-build"]
        tags = re.findall(r"^          tags: (.+)$", build, re.MULTILINE)
        self.assertEqual(tags, [
            "symfony-flex-backend:ci-${{ github.sha }}-${{ matrix.target }}",
        ])
        self.assertNotIn("github.ref_name", build)
        self.assertNotIn("DOCKER_TAG", build)
        self.assertNotIn("steps.vars", build)
        for sha in ("a" * 40, "0123456789abcdef" * 2 + "01234567"):
            for target in ("development", "production"):
                with self.subTest(sha=sha, target=target):
                    image = tags[0].replace("${{ github.sha }}", sha).replace(
                        "${{ matrix.target }}", target,
                    )
                    tag = image.split(":", 1)[1]
                    self.assertRegex(tag, r"^[A-Za-z0-9_][A-Za-z0-9_.-]{0,127}$")
                    self.assertEqual(tag, f"ci-{sha}-{target}")

    def run_gate(self, job, environment):
        """Run the Bash gate named in the workflow with the fixture's CI inputs."""
        script = re.search(
            r"^        run: bash (scripts/ci/ci-[\w-]+\.sh)$",
            self.jobs[job], re.MULTILINE,
        )
        assert script is not None, f"Missing result script for {job}"
        return subprocess.run(
            ["bash", str(WORKFLOW.parents[2] / script.group(1))],
            cwd=WORKFLOW.parent,
            env=dict(os.environ, **environment),
            text=True, capture_output=True, check=False, timeout=10,
        )

    def test_actual_scripts_preserve_result_validation(self):
        labels = {
            "CHANGES": "Change detection",
            "SECURITY_CHECK": "Dependency security",
            "DOCKER_UPDATES": "Docker image updates",
            "DOCKER_PINS": "Docker pin verification",
            "GITLEAKS": "Gitleaks",
            "LINT_CONFIGURATION": "Configuration lint",
            "LINT_DOCUMENTATION": "Documentation lint",
            "LINT_WORKFLOWS": "Workflow validation",
            "STATIC_ANALYZERS": "Static analyzer matrix",
            "LIGHTWEIGHT_ANALYZERS": "Lightweight analyzers",
            "CI_SUMMARY": "Prerequisite gate",
            "TEST": "PHPUnit",
            "DOCKER_BUILD": "Docker builds",
            "DOCKER_SCAN": "Trivy image scans",
            "REPORT": "External reporting",
        }
        for job in ("ci-summary", "ci-result"):
            result_names = re.findall(
                r"^          (\w+): \$\{\{ needs\.[\w-]+\.result \}\}$",
                self.jobs[job], re.MULTILINE,
            )
            self.assertTrue(result_names, f"Missing result bindings for {job}")
            for event in ("pull_request", "push", "merge_group",
                          "workflow_dispatch", "schedule"):
                environment = dict.fromkeys(result_names, "success")
                environment.update(EVENT_NAME=event,
                                   DOCUMENTATION_CHANGED="true", REF="refs/heads/master")
                flags = re.findall(
                    r"^          (\w+): \$\{\{ needs\.changes\.outputs\.\w+ \}\}$",
                    self.jobs[job], re.MULTILINE,
                )
                environment.update(dict.fromkeys(flags, "true"))
                if job == "ci-result" and event in ("merge_group", "workflow_dispatch"):
                    environment["REPORT"] = "skipped"
                if event == "schedule":
                    periodic = {"SECURITY_CHECK", "DOCKER_UPDATES", "CI_SUMMARY"}
                    for name in result_names:
                        if name not in periodic:
                            environment[name] = "skipped"
                with self.subTest(job=job, event=event):
                    result = self.run_gate(job, environment)
                    self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                    self.assertNotIn("::error::", result.stderr)
                # Vary one result at a time so each rejection identifies its own dependency.
                for name in result_names:
                    for status in ("success", "failure", "cancelled", "skipped", "", "unknown"):
                        if status == environment[name]:
                            continue
                        with self.subTest(job=job, event=event, name=name, status=status):
                            result = self.run_gate(job, dict(environment, **{name: status}))
                            self.assertNotEqual(result.returncode, 0, result.stdout)
                            self.assertEqual(
                                result.stderr,
                                f"::error::{labels[name]}: expected result "
                                f"'{environment[name]}', got '{status}'.\n",
                            )


if __name__ == "__main__":
    unittest.main()
