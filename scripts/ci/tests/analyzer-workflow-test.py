#!/usr/bin/env python3
"""Test CI job wiring, merge gates, report processing, and change selection."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make lint-workflows
#   To run only this suite from the repository root inside the container:
#     python3 scripts/ci/tests/analyzer-workflow-test.py
#
# Requirements:
#   Python 3, Bash, Git, and standard Linux command-line tools.
#
# Details:
#   The suite reads the checked-out workflow and exercises its helpers offline.
#   It uses temporary Git history and report fixtures, and stubs Docker calls
#   rather than starting containers or running hosted CI jobs.
#
# Callers:
#   Scripts:
#     - `scripts/checks/lint-workflows.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py`.

import os
from pathlib import Path
import re
import shlex
import shutil
import subprocess
import tempfile
import textwrap
import unittest


WORKFLOW = Path(__file__).resolve().parents[3] / ".github/workflows/main.yml"
CONTENT = WORKFLOW.read_text()
DRAFT_GUARD = "(github.event_name != 'pull_request' || !github.event.pull_request.draft)"
PR_TYPES = (
    "    types:\n      - opened\n      - synchronize\n      - reopened\n"
    "      - ready_for_review\n      - converted_to_draft\n"
)


def job(name):
    """Extract the named job's raw YAML block for source-level assertions."""
    match = re.search(
        rf"^  {re.escape(name)}:\n(.*?)(?=^  [a-z][a-z-]*:\n|\Z)",
        CONTENT, re.MULTILINE | re.DOTALL,
    )
    if match is None:
        raise AssertionError(f"Missing workflow job: {name}")
    return match.group(1)


def without_overview(section):
    """Exclude the advisory overview when testing validation failure policy."""
    result, count = re.subn(
        r"^      - name: Publish job overview\n.*?(?=^      - |\Z)",
        "", section, flags=re.MULTILINE | re.DOTALL,
    )
    if count != 1:
        raise AssertionError("Expected exactly one informational job overview")
    return result


def dependencies(name):
    """Read the scalar or block-list needs forms used by this workflow."""
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
    def test_failure_diagnostics_are_bounded_allowlisted_and_read_only(self):
        with tempfile.TemporaryDirectory(prefix="ci-diagnostics-") as directory:
            root = Path(directory)
            (root / "scripts/ci").mkdir(parents=True)
            script = root / "scripts/ci/ci-failure-diagnostics.sh"
            script.write_text((WORKFLOW.parents[2] / "scripts/ci/ci-failure-diagnostics.sh").read_text())
            docker = root / "docker"
            docker.write_text(
                "#!/usr/bin/env bash\nset -eu\n"
                'printf "%s\\n" "$*" >> "$CALLS"\n'
                'if [[ "$1" == compose ]]; then\n'
                '  printf "%s" "$IDS"; exit "${LIST_EXIT:-0}"\n'
                'elif [[ "$1" == inspect && "$2" == --format ]]; then\n'
                '  printf "%s" "$STATE"; echo "secret native error" >&2\n'
                '  exit "${INSPECT_EXIT:-0}"\n'
                'fi\nexit 99\n'
            )
            docker.chmod(0o755)
            timeout = root / "timeout"
            timeout.write_text(
                "#!/usr/bin/env bash\nset -eu\n"
                '[[ "$1" == 10s ]] || exit 99\nshift\n'
                'if [[ "${TIMED_OUT:-0}" == 1 ]]; then exit 124; fi\nexec "$@"\n'
            )
            timeout.chmod(0o755)
            calls = root / "calls"

            def diagnose(**overrides):
                calls.write_text("")
                return subprocess.run(
                    ["bash", str(script)], cwd=root.parent,
                    env={**os.environ, "PATH": f"{root}:{os.environ['PATH']}",
                         "CALLS": str(calls), "IDS": "a" * 64,
                         "STATE": "exited 137 true unhealthy",
                         "LIST_EXIT": "0", "INSPECT_EXIT": "0", "TIMED_OUT": "0",
                         **overrides},
                    text=True, capture_output=True, check=False, timeout=10,
                )

            for state in ("exited 137 true unhealthy", "running 0 false none",
                          "running 0 false healthy", "restarting 1 false starting"):
                with self.subTest(state=state):
                    result = diagnose(STATE=state)
                    self.assertEqual(result.returncode, 0, result.stderr)
                    self.assertEqual(len(result.stdout.splitlines()), 1)
                    self.assertLess(len(result.stdout), 150)
                    self.assertEqual(result.stderr, "")
                    commands = calls.read_text().splitlines()
                    self.assertEqual(commands[0],
                                     "compose -f compose.yaml -f docker/ci-compose.yaml "
                                     "ps --all --quiet php")
                    self.assertTrue(commands[1].startswith("inspect --format "))
                    self.assertIn("{{.State.Status}} {{.State.ExitCode}}", commands[1])
                    self.assertNotIn("State.Health.Log", commands[1])
            result = diagnose(IDS="")
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("no PHP container exists", result.stdout)
            self.assertEqual(len(calls.read_text().splitlines()), 1)
            for overrides in (
                {"IDS": "secret-invalid-id"}, {"IDS": "\n".join(["a" * 64] * 6)},
                {"STATE": "secret-token"}, {"STATE": "running 0 false healthy\nsecret"},
                {"STATE": "running " + "9" * 1000 + " false none"},
                {"LIST_EXIT": "7"}, {"INSPECT_EXIT": "7"}, {"TIMED_OUT": "1"},
            ):
                with self.subTest(overrides=overrides):
                    result = diagnose(**overrides)
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn("::error::Container diagnostics failed:", result.stderr)
                    self.assertNotIn("secret", result.stdout + result.stderr)
                    self.assertEqual(result.stdout, "")
            result = diagnose(IDS="\n".join(["a" * 64] * 5))
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(len(result.stdout.splitlines()), 5)

    def test_workflow_validator_download_cache_is_scoped_and_shared_with_container(self):
        section = job("lint-workflows")
        self.assertIn(
            "      - name: Cache verified workflow validator downloads\n"
            "        uses: actions/cache@55cc8345863c7cc4c66a329aec7e433d2d1c52a9 # v6.1.0\n"
            "        with:\n"
            "          path: build/cache/workflow-validators\n"
            "          key: workflow-validators-v1-${{ runner.os }}-${{ runner.arch }}-"
            "${{ hashFiles('scripts/checks/lint-workflows.sh') }}\n", section,
        )
        self.assertNotIn("restore-keys", section)
        self.assertLess(section.index("Cache verified workflow validator downloads"),
                        section.index("Bootstrap development container"))
        self.assertLess(section.index("*checkout_step"),
                        section.index("Cache verified workflow validator downloads"))
        self.assertNotIn("continue-on-error", without_overview(section))

    def test_both_required_workflows_validate_merge_groups(self):
        review = (WORKFLOW.parent / "dependency-review.yml").read_text()
        self.assertEqual(
            review.split("on:\n", 1)[1].split("\npermissions:", 1)[0],
            f"  pull_request:\n{PR_TYPES}  merge_group:\n    types:\n      - checks_requested\n",
        )
        self.assertIn("  merge_group:\n    types:\n      - checks_requested\n", CONTENT)
        self.assertIn("\npermissions:\n  contents: read\n", review)
        self.assertIn("\n  dependency-review:\n", review)
        self.assertNotRegex(review, r"(?m)^    if:")
        self.assertNotIn("warn-only:", review)
        self.assertNotIn("pull_request_target:", review)
        self.assertIn("          persist-credentials: false\n", review)
        action = re.search(r"        uses: actions/dependency-review-action@\S+.*\n", review)
        assert action is not None
        self.assertRegex(action.group(), r"@[a-f0-9]{40}")
        review_step = review.split("      - name: 'Dependency Review'\n", 1)[1].split(
            "\n      - ", 1,
        )[0]
        self.assertNotIn("if:", review_step)
        self.assertNotIn("continue-on-error", review_step)
        # Native action defaults select PR or merge-group base/head SHAs.
        self.assertEqual(review_step.split(action.group().strip(), 1)[1].strip(), "")
        self.assertEqual(self.run_gate("ci-summary", "merge_group").returncode, 0)
        self.assertEqual(self.run_gate("ci-result", "merge_group").returncode, 0)

    def test_every_blocking_core_job_is_checked_by_the_required_gate(self):
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
        self.assertEqual(names - covered, {"statistics"})
        self.assertEqual(dependencies("statistics"), set())
        for name in covered:
            header = job(name).split("    steps:", 1)[0]
            self.assertNotRegex(header, r"^    continue-on-error:", name)
        self.assertIn("report", covered)

    def test_gate_dependencies_have_real_result_bindings(self):
        for name in ("ci-summary", "ci-result"):
            section = job(name)
            self.assertNotIn("CODE_CHANGED", section)
            script = "ci-prerequisite-gate.sh" if name == "ci-summary" else "ci-result-gate.sh"
            self.assertNotIn(
                "CODE_CHANGED", (WORKFLOW.parents[2] / "scripts/ci" / script).read_text(),
            )
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

    def test_pr_edits_do_not_create_duplicate_skipped_checks(self):
        events = CONTENT.split("  pull_request:\n", 1)[1].split("  merge_group:", 1)[0]
        self.assertEqual(
            events,
            PR_TYPES,
        )
        self.assertNotIn("github.event.action", CONTENT)
        self.assertNotIn("github.event.changes", CONTENT)
        self.assertIn("    name: Validate CI result\n", job("ci-result"))

    def test_draft_guards_preserve_validation_for_other_events(self):
        for name in ("changes", "gitleaks", "statistics"):
            self.assertIn(
                f"    if: ${{{{ github.event_name != 'schedule' && {DRAFT_GUARD} }}}}\n",
                job(name),
            )
        self.assertIn(f"    if: ${{{{ !cancelled() && {DRAFT_GUARD} }}}}\n", job("ci-summary"))
        self.assertIn("    if: ${{ !cancelled() }}\n", job("ci-result"))
        codeql = (WORKFLOW.parent / "codeql.yml").read_text()
        self.assertIn(f"  pull_request:\n{PR_TYPES}", codeql)
        self.assertIn(f"    if: ${{{{ {DRAFT_GUARD[1:-1]} }}}}\n", codeql)
        for workflow in (CONTENT, (WORKFLOW.parent / "dependency-review.yml").read_text()):
            self.assertIn("  cancel-in-progress: true\n", workflow)
            block = workflow.split("      - name: Block draft pull requests\n", 1)[1].split(
                "\n      - ", 1,
            )[0]
            self.assertIn(
                "        if: ${{ github.event_name == 'pull_request' && "
                "github.event.pull_request.draft }}\n", block,
            )
            command = block.split("        run: |\n", 1)[1]
            result = subprocess.run(
                ["bash", "-c", textwrap.dedent(command)],
                text=True, capture_output=True, check=False,
            )
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("::error::", result.stdout)
        for name in ("lint-configuration", "docker-image-verify-pins",
                     "lightweight-analyzers", "static-analyzers", "test", "docker-build"):
            self.assertRegex(job(name), r"    needs:(?: changes\n|\n      - changes\n)")
            self.assertNotIn("always()", job(name).split("    steps:", 1)[0])
        for name in ("security-check", "docker-image-check-updates"):
            self.assertIn("needs.changes.result == 'success'", job(name))
        self.assertIn("github.event_name == 'push'", job("report"))

    def test_only_image_update_availability_is_advisory(self):
        section = job("docker-image-check-updates")
        self.assertIn("run: bash scripts/docker/docker-images.sh check-updates --advisory\n", section)
        self.assertIn("run: bash scripts/make/make.sh test-docker-images\n", section)
        self.assertNotIn("continue-on-error", without_overview(section))
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
        assert image is not None
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
            assert needs is not None
            self.assertEqual(needs.group(1), "      - changes\n")
            self.assertIn(
                "    if: ${{ github.event_name != 'schedule' && "
                f"needs.changes.outputs.{output} == 'true' }}}}\n", section,
            )

    def test_image_scans_survive_sibling_build_failure_without_weakening_gates(self):
        section = job("docker-scan")
        condition = re.search(r"^    if: \$\{\{ (.+) \}\}$", section, re.MULTILINE)
        assert condition is not None
        self.assertEqual(
            condition.group(1),
            "!cancelled() && github.event_name != 'schedule' && "
            "needs.changes.result == 'success' && needs.changes.outputs.docker == 'true' && "
            "(needs.docker-build.result == 'success' || needs.docker-build.result == 'failure')",
        )
        self.assertEqual(dependencies("docker-scan"), {"changes", "docker-build"})
        for event in ("pull_request", "push", "merge_group", "workflow_dispatch", "schedule"):
            for detection in ("success", "failure", "skipped", "cancelled"):
                for selected in ("true", "false", ""):
                    for build in ("success", "failure", "skipped", "cancelled"):
                        for cancelled in (False, True):
                            expression = condition.group(1).replace(
                                "!cancelled()", repr(not cancelled),
                            ).replace("github.event_name", repr(event)).replace(
                                "needs.changes.result", repr(detection),
                            ).replace("needs.changes.outputs.docker", repr(selected)).replace(
                                "needs.docker-build.result", repr(build),
                            ).replace("&&", "and").replace("||", "or")
                            with self.subTest(event=event, detection=detection,
                                              selected=selected, build=build,
                                              cancelled=cancelled):
                                self.assertEqual(
                                    eval(expression, {"__builtins__": {}}),
                                    not cancelled and event != "schedule"
                                    and detection == "success" and selected == "true"
                                    and build in ("success", "failure"),
                                )
        self.assertIn("      fail-fast: false\n", section)
        self.assertIn("          name: docker-image-${{ matrix.target }}\n", section)
        self.assertIn(
            "          input: ${{ runner.temp }}/docker-image/docker-image.tar\n", section,
        )
        self.assertIn("          exit-code: '1'\n", section)
        self.assertEqual(section.count("          severity: 'CRITICAL,HIGH'\n"), 2)
        self.assertEqual(section.count("          ignore-unfixed: true\n"), 2)
        self.assertNotIn("docker/build-push-action@", section)
        self.assertNotIn("continue-on-error", without_overview(section))
        download = re.search(
            r"^      - name: Download Docker image for scanning\n(.*?)(?=^      - )",
            section, re.MULTILINE | re.DOTALL,
        )
        assert download is not None
        self.assertNotIn("        if:", download.group(1))
        self.assertIn(
            "        if: ${{ !cancelled() && steps.image.outcome == 'success' && "
            "github.event_name == 'pull_request' && "
            "github.event.pull_request.head.repo.full_name == github.repository }}\n",
            section,
        )
        self.assertIn(
            "        if: ${{ !cancelled() && steps.sarif.outcome == 'success' }}\n", section,
        )
        self.assertIn("          category: trivy-${{ matrix.target }}\n", section)
        self.assertTrue({"docker-build", "docker-scan"} <= dependencies("ci-result"))

    def test_reporting_still_requires_prerequisites_and_tests(self):
        section = job("report")
        self.assertEqual(dependencies("report"), {"ci-summary", "test"})
        condition = re.search(r"^    if: \$\{\{ (.+) \}\}$", section, re.MULTILINE)
        assert condition is not None
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
            assert step is not None
            self.assertNotIn("continue-on-error", step.group(1))
            if token == "COVERALLS_REPO_TOKEN":
                self.assertIn("        if: ${{ env." + token + " != '' }}\n", step.group(1))
            self.assertIn(token + ": ${{ secrets." + token + " }}\n", step.group(1))
        self.assertIn(
            "      - name: Prepare reports for host-side SonarQube scan\n"
            "        id: prepare-sonar\n", section,
        )
        self.assertNotIn("continue-on-error", without_overview(section))
        self.assertNotIn("|| true", section)
        self.assertIn(
            "        if: ${{ always() && steps.bootstrap.outcome != 'skipped' }}\n"
            "        run: bash scripts/make/make.sh ci-stop\n", section,
        )
        # Markdown-only PRs skip tests, so success() prevents artifact downloads.
        self.assertIn(
            "    if: ${{ github.event_name != 'schedule' && "
            "needs.changes.outputs.tests == 'true' }}\n", job("test"),
        )

    def test_sonar_attempts_are_independent_but_require_successful_inputs(self):
        section = job("report")
        conditions = {}
        for name, expected in (
            ("Prepare reports for host-side SonarQube scan",
             "!cancelled() && env.SONAR_TOKEN != '' && "
             "steps.checkout-report.outcome == 'success' && "
             "steps.clover.outcome == 'success' && steps.junit.outcome == 'success' && "
             "steps.bootstrap.outcome == 'success'"),
            ("SonarQube Scan",
             "!cancelled() && env.SONAR_TOKEN != '' && "
             "steps.prepare-sonar.outcome == 'success'"),
        ):
            step = re.search(
                rf"^      - name: {re.escape(name)}[^\n]*\n(.*?)(?=^      - |\Z)",
                section, re.MULTILINE | re.DOTALL,
            )
            assert step is not None
            condition = re.search(r"^        if: \$\{\{ (.+) \}\}$",
                                  step.group(1), re.MULTILINE)
            assert condition is not None
            self.assertEqual(condition.group(1), expected)
            conditions[name] = condition.group(1)

        for coveralls_token in ("", "coveralls-fixture"):
            for sonar_token in ("", "sonar-fixture"):
                for failed_input in (None, "checkout-report", "clover", "junit", "bootstrap"):
                    for outcome in ("success", "failure", "skipped", "cancelled"):
                        for coveralls_result in ("success", "failure", "skipped"):
                            for cancelled in (False, True):
                                inputs = dict.fromkeys(
                                    ("checkout-report", "clover", "junit", "bootstrap"), "success",
                                )
                                if failed_input:
                                    inputs[failed_input] = outcome
                                expression = conditions[
                                    "Prepare reports for host-side SonarQube scan"
                                ].replace("!cancelled()", repr(not cancelled)).replace(
                                    "env.SONAR_TOKEN", repr(sonar_token),
                                )
                                for identifier, result in inputs.items():
                                    expression = expression.replace(
                                        f"steps.{identifier}.outcome", repr(result),
                                    )
                                with self.subTest(
                                    coveralls_token=bool(coveralls_token),
                                    sonar_token=bool(sonar_token), inputs=inputs,
                                    coveralls_result=coveralls_result, cancelled=cancelled,
                                ):
                                    self.assertEqual(
                                        eval(expression.replace("&&", "and"),
                                             {"__builtins__": {}}),
                                        not cancelled and bool(sonar_token)
                                        and all(value == "success" for value in inputs.values()),
                                    )

        for outcome in ("success", "failure", "skipped", "cancelled"):
            for token in ("", "sonar-fixture"):
                for cancelled in (False, True):
                    expression = conditions["SonarQube Scan"].replace(
                        "!cancelled()", repr(not cancelled),
                    ).replace("env.SONAR_TOKEN", repr(token)).replace(
                        "steps.prepare-sonar.outcome", repr(outcome),
                    ).replace("&&", "and")
                    self.assertEqual(eval(expression, {"__builtins__": {}}),
                                     not cancelled and bool(token) and outcome == "success")

    def test_reporting_selection_skips_expensive_setup_without_tokens(self):
        section = job("report")
        selection = re.search(
            r"^      - name: Select external reporting\n"
            r"(.*?)(?=^      - )", section, re.MULTILINE | re.DOTALL,
        )
        assert selection is not None
        self.assertIn("        id: reporting\n", selection.group(1))
        for token in ("COVERALLS_REPO_TOKEN", "SONAR_TOKEN"):
            self.assertIn(token + ": ${{ secrets." + token + " }}\n", selection.group(1))
        script = textwrap.dedent(selection.group(1).split("        run: |\n", 1)[1])
        for coveralls, sonar in (("", ""), ("coveralls-fixture", ""),
                                 ("", "sonar-fixture"),
                                 ("coveralls-fixture", "sonar-fixture")):
            with self.subTest(coveralls=bool(coveralls), sonar=bool(sonar)):
                with tempfile.TemporaryDirectory() as directory:
                    output = Path(directory) / "output"
                    summary = Path(directory) / "summary"
                    result = subprocess.run(
                        ["bash", "--noprofile", "--norc", "-euo", "pipefail", "-c", script],
                        env={**os.environ, "COVERALLS_REPO_TOKEN": coveralls,
                             "SONAR_TOKEN": sonar, "GITHUB_OUTPUT": str(output),
                             "GITHUB_STEP_SUMMARY": str(summary)},
                        text=True, capture_output=True, check=False, timeout=10,
                    )
                    self.assertEqual(result.returncode, 0, result.stderr)
                    enabled = bool(coveralls or sonar)
                    self.assertEqual(
                        output.read_text(),
                        f"coveralls={'available' if coveralls else 'not available'}\n"
                        f"sonar={'available' if sonar else 'not available'}\n"
                        f"enabled={str(enabled).lower()}\n",
                    )
                    self.assertEqual(
                        summary.read_text() if summary.exists() else "",
                        "" if enabled else
                        "External reporting skipped: neither upload token is available.\n",
                    )
                    self.assertEqual(result.stdout + result.stderr, "")
        for name in ("Checkout tested source", "Download Clover coverage report",
                     "Download JUnit test report", "Bootstrap PHP tools"):
            step = re.search(
                rf"^      - name: {name}\n(.*?)(?=^      - )",
                section, re.MULTILINE | re.DOTALL,
            )
            assert step is not None
            self.assertIn(
                "        if: ${{ steps.reporting.outputs.enabled == 'true' }}\n",
                step.group(1),
            )
            self.assertLess(selection.start(), step.start())
        self.assertIn(
            "      - name: Bootstrap PHP tools\n        id: bootstrap\n", section,
        )
        self.assertIn(
            "      - name: Install Coveralls reporter\n"
            "        id: install-coveralls\n"
            "        if: ${{ env.COVERALLS_REPO_TOKEN != '' }}\n"
            "        env:\n"
            "          COVERALLS_REPO_TOKEN: ${{ secrets.COVERALLS_REPO_TOKEN }}\n",
            section,
        )
        cleanup = re.search(
            r"^      - name: Stop PHP development container\n"
            r"        if: \$\{\{ (.+) \}\}$", section, re.MULTILINE,
        )
        assert cleanup is not None
        self.assertEqual(
            cleanup.group(1), "always() && steps.bootstrap.outcome != 'skipped'",
        )
        for outcome in ("success", "failure", "cancelled", "skipped"):
            with self.subTest(bootstrap=outcome):
                expression = cleanup.group(1).replace("always()", "True").replace(
                    "steps.bootstrap.outcome", repr(outcome),
                ).replace("&&", "and")
                self.assertEqual(
                    eval(expression, {"__builtins__": {}}), outcome != "skipped",
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
        assert upload is not None
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
            "  group: ${{ github.workflow }}-${{ github.ref }}"
            "${{ github.event_name == 'schedule' && '-schedule' || '' }}\n"
            "  cancel-in-progress: true\n",
        )

    def test_summaries_explain_selected_unaffected_and_scheduled_jobs(self):
        scenarios = [
            ("ci-summary", "pull_request",
             {"SECURITY_CHANGED": "false", "SECURITY_CHECK": "skipped"},
             "| Dependency security | skipped | Changed files do not affect dependency audit inputs. |"),
            ("ci-summary", "schedule", {"LIGHTWEIGHT_ANALYZERS": "skipped"},
             "| Configuration lint | skipped | Scheduled CI runs only dependency security and image-update checks. |"),
            ("ci-result", "pull_request",
             {"TESTS_CHANGED": "false", "TEST": "skipped", "REPORT": "skipped"},
             "| External reporting | skipped | Tests were not selected, so no coverage artifacts were produced. |"),
            ("ci-result", "workflow_dispatch", {},
             "| External reporting | skipped | Uploads run only on PRs and master pushes with selected, successful tests and prerequisites. |"),
        ]
        for gate, event, overrides, explanation in scenarios:
            with self.subTest(gate=gate, event=event):
                with tempfile.TemporaryDirectory() as directory:
                    summary = Path(directory) / "summary.md"
                    result = self.run_gate(
                        gate, event, GITHUB_STEP_SUMMARY=str(summary), **overrides,
                    )
                    self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                    self.assertIn(explanation, summary.read_text())
                    self.assertIn("| Check | Result | Explanation |", summary.read_text())
                    if gate == "ci-result":
                        self.assertIn("## Core CI result", summary.read_text())
                        self.assertIn("✅ Required gate inputs match expected results.", summary.read_text())
                        self.assertIn("| Change detection | success |", summary.read_text())
                        self.assertIn("| Project statistics | independent |", summary.read_text())
                        self.assertIn("this gate does not wait for collection", summary.read_text())
                    else:
                        self.assertTrue(summary.read_text().startswith(
                            "<details>\n<summary>CI prerequisite details</summary>\n",
                        ))
                        self.assertTrue(summary.read_text().endswith("</details>\n"))

    def test_gate_summaries_do_not_repeat_analyzers(self):
        scenarios = (
            ("pull_request", {}, "success", "Job completed with result: success.", True),
            ("pull_request", {
                "PHP_CHANGED": "false", "STATIC_ANALYZERS": "skipped",
                "LIGHTWEIGHT_ANALYZERS": "skipped",
            }, "skipped", "Changed files do not affect PHP validation inputs.", True),
            ("pull_request", {
                "STATIC_ANALYZERS": "failure", "LIGHTWEIGHT_ANALYZERS": "failure",
            }, "failure", "Job failure; inspect its logs and annotations.", False),
            ("pull_request", {
                "STATIC_ANALYZERS": "skipped", "LIGHTWEIGHT_ANALYZERS": "skipped",
            }, "skipped", "Unexpected skip: validation was selected. The merge gate rejects this result.", False),
            ("pull_request", {
                "CHANGES": "failure", "STATIC_ANALYZERS": "skipped",
                "LIGHTWEIGHT_ANALYZERS": "skipped",
            }, "skipped", "Change detection did not succeed; validation selection is unavailable.", False),
            ("schedule", {"LIGHTWEIGHT_ANALYZERS": "skipped"}, "skipped",
             "Not part of scheduled dependency-security and image-update validation.", True),
        )
        for event, overrides, outcome, explanation, valid in scenarios:
            with self.subTest(event=event, outcome=outcome, explanation=explanation):
                with tempfile.TemporaryDirectory() as directory:
                    prerequisite = Path(directory) / "prerequisite.md"
                    final = Path(directory) / "final.md"
                    prerequisite_result = self.run_gate(
                        "ci-summary", event, GITHUB_STEP_SUMMARY=str(prerequisite), **overrides,
                    )
                    final_result = self.run_gate(
                        "ci-result", event, GITHUB_STEP_SUMMARY=str(final), **overrides,
                    )
                    self.assertEqual(prerequisite_result.returncode == 0, valid)
                    self.assertEqual(final_result.returncode == 0, valid)
                    prerequisite_text = prerequisite.read_text()
                    final_text = final.read_text()
                    self.assertIn("Analyzer results and their skip reasons appear in Core CI result.", prerequisite_text)
                    for label in ("Static analyzer matrix", "Lightweight analyzers"):
                        self.assertNotIn(f"| {label} |", prerequisite_text)
                        self.assertEqual(final_text.count(f"| {label} |"), 1)
                        self.assertIn(f"| {label} | {outcome} | {explanation} |", final_text)

    def test_final_summary_prioritizes_actionable_results_without_duplicate_rows(self):
        cases = (
            ("pull_request", {"TEST": "failure"}, "PHPUnit", "Job failure; inspect its logs"),
            ("pull_request", {"TEST": "skipped"}, "PHPUnit", "Unexpected skip"),
            ("pull_request", {"TEST": ""}, "PHPUnit", "Result is unavailable or unrecognized"),
            ("pull_request", {"TESTS_CHANGED": "", "TEST": "skipped"}, "PHPUnit",
             "Validation selection is unavailable or invalid"),
            ("pull_request", {"REPORT": "skipped"}, "External reporting", "Unexpected skip"),
            ("workflow_dispatch", {"REPORT": "success"}, "External reporting",
             "Unexpected result: reporting was not expected"),
            ("schedule", {"CI_SUMMARY": "skipped", "LIGHTWEIGHT_ANALYZERS": "skipped"},
             "Prerequisite gate", "Unexpected skip: the prerequisite gate is required"),
        )
        for event, overrides, first, explanation in cases:
            with self.subTest(event=event, first=first, overrides=overrides):
                with tempfile.TemporaryDirectory() as directory:
                    summary = Path(directory) / "summary.md"
                    result = self.run_gate("ci-result", event, GITHUB_STEP_SUMMARY=str(summary), **overrides)
                    self.assertNotEqual(result.returncode, 0)
                    rows = re.findall(r"^\| ([^|\n]+) \|.*$", summary.read_text(), re.MULTILINE)[2:]
                    self.assertEqual(rows[0], first)
                    self.assertEqual(len(rows), len(set(rows)))
                    self.assertIn("**Needs attention:", summary.read_text())
                    self.assertIn("⚠️ **Needs attention:", summary.read_text())
                    self.assertIn(explanation, summary.read_text())

    def test_final_summary_keeps_expected_skips_out_of_attention(self):
        with tempfile.TemporaryDirectory() as directory:
            summary = Path(directory) / "summary.md"
            result = self.documentation_gate(
                "ci-result", GITHUB_STEP_SUMMARY=str(summary),
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            rows = re.findall(r"^\| ([^|\n]+) \|.*$", summary.read_text(), re.MULTILINE)[2:]
            self.assertEqual(rows[0], "Change detection")
            self.assertNotIn("**Needs attention:", summary.read_text())
            self.assertIn("Changed files do not affect PHP validation inputs.", summary.read_text())

    def test_final_summary_does_not_call_missing_test_selection_an_unexpected_report_skip(self):
        with tempfile.TemporaryDirectory() as directory:
            summary = Path(directory) / "summary.md"
            result = self.run_gate(
                "ci-result", TESTS_CHANGED="", TEST="skipped", REPORT="skipped",
                GITHUB_STEP_SUMMARY=str(summary),
            )
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("Test selection is unavailable or invalid", summary.read_text())
            self.assertNotIn("Unexpected skip: uploads were expected", summary.read_text())

    def test_summaries_explain_failures_without_relaxing_gates(self):
        scenarios = [
            ("ci-summary", {"SECURITY_CHECK": "skipped"}, "Unexpected skip: validation was selected."),
            ("ci-summary", {"CHANGES": "failure"}, "Change detection | failure"),
            ("ci-result", {"CHANGES": "failure", "TEST": "skipped"},
             "Change detection did not succeed; validation selection is unavailable."),
            ("ci-result", {"CI_SUMMARY": "failure", "REPORT": "skipped"},
             "Tests or prerequisite checks did not succeed; uploads require both."),
            ("ci-result", {"REPORT": "skipped"}, "Unexpected skip: uploads were expected."),
        ]
        for gate, overrides, explanation in scenarios:
            with self.subTest(gate=gate, overrides=overrides):
                with tempfile.TemporaryDirectory() as directory:
                    summary = Path(directory) / "summary.md"
                    result = self.run_gate(
                        gate, GITHUB_STEP_SUMMARY=str(summary), **overrides,
                    )
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn(explanation, summary.read_text())

    def test_summary_write_failures_do_not_override_gate_results(self):
        for gate, result_variable in (("ci-summary", "GITLEAKS"), ("ci-result", "TEST")):
            with tempfile.TemporaryDirectory() as directory:
                for result in ("success", "failure", "skipped", ""):
                    with self.subTest(gate=gate, result=result):
                        outcome = self.run_gate(
                            gate, GITHUB_STEP_SUMMARY=directory,
                            **{result_variable: result},
                        )
                        self.assertEqual(outcome.returncode == 0, result == "success")
                        self.assertIn("::warning::Could not write", outcome.stdout)

    def test_gate_diagnostics_identify_invalid_selection_and_expected_skips(self):
        for gate, flag, result, label in (
            ("ci-summary", "DOCUMENTATION_CHANGED", "LINT_DOCUMENTATION", "Documentation lint"),
            ("ci-result", "TESTS_CHANGED", "TEST", "PHPUnit"),
        ):
            for selection in ("", "unknown", "skipped"):
                with self.subTest(gate=gate, selection=selection):
                    outcome = self.run_gate(gate, **{flag: selection})
                    self.assertNotEqual(outcome.returncode, 0)
                    self.assertEqual(
                        outcome.stderr,
                        f"::error::{label}: expected selection 'true' or 'false', "
                        f"got '{selection}' (job result 'success').\n",
                    )
            outcome = self.run_gate(gate, **{flag: "false", result: "failure"})
            self.assertNotEqual(outcome.returncode, 0)
            self.assertEqual(
                outcome.stderr,
                f"::error::{label}: expected result 'skipped', got 'failure'.\n",
            )

    def test_gate_annotations_escape_percent_and_line_breaks(self):
        outcome = self.run_gate("ci-result", TEST="bad%\r\n::warning::not-an-annotation")
        self.assertNotEqual(outcome.returncode, 0)
        self.assertEqual(
            outcome.stderr,
            "::error::PHPUnit: expected result 'success', "
            "got 'bad%25%0D%0A::warning::not-an-annotation'.\n",
        )

    def run_gate(self, name, event="pull_request", lightweight="success", **overrides):
        """Run the gate script wired into the job with simulated CI results."""
        section = job(name)
        script = re.search(r"^        run: bash (scripts/ci/ci-[\w-]+\.sh)$", section, re.MULTILINE)
        assert script is not None, f"Missing gate script for {name}"
        variables = re.findall(r"^          ([A-Z_]+):", section, re.MULTILINE)
        # Derive the inputs from the job so the fixture follows new gate bindings.
        environment = dict(os.environ, **dict.fromkeys(variables, "success"))
        environment.update(EVENT_NAME=event,
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
            ["bash", str(WORKFLOW.parents[2] / script.group(1))], env=environment,
            capture_output=True, text=True, check=False, timeout=10,
        )

    def test_every_lightweight_tool_keeps_failure_independent_steps(self):
        section = job("lightweight-analyzers")
        for target in ("phpcs", "ecs", "phplint", "php-parallel-lint"):
            with self.subTest(target=target):
                self.assertIn(
                    "        if: ${{ !cancelled() && steps.bootstrap.outcome == 'success' }}\n"
                    f"        run: bash scripts/make/make.sh {target}\n", section,
                )
                self.assertEqual(CONTENT.count(f"run: bash scripts/make/make.sh {target}\n"), 1)
        self.assertIn('run: bash scripts/make/make.sh phploc | tee "$RUNNER_TEMP/phploc-output.txt"', section)
        phploc = section.split("      - name: Run PHPLoc\n", 1)[1].split("\n      - ", 1)[0]
        self.assertIn("        shell: bash\n", phploc)
        self.assertNotIn("continue-on-error", phploc)
        summary = section.split("      - name: Publish PHPLoc statistics\n", 1)[1].split("\n      - ", 1)[0]
        self.assertIn("continue-on-error: true", summary)
        self.assertIn("steps.phploc.outcome == 'failure'", summary)
        self.assertIn("        id: bootstrap\n", section)
        self.assertIn("      - *stop_php_step\n", section)
        self.assertIn("          name: phploc.json\n", section)
        self.assertIn("          path: ./build/phploc.json\n", section)
        self.assertIn("          if-no-files-found: error\n", section)
        self.assertIn("          retention-days: 14\n", section)
        self.assertIn("steps.phploc.outcome == 'failure'", section)

    def test_statistics_job_is_informational_and_runs_on_non_scheduled_events(self):
        section = job("statistics")
        self.assertIn(f"    if: ${{{{ github.event_name != 'schedule' && {DRAFT_GUARD} }}}}\n", section)
        self.assertIn("      - *checkout_full_history_step\n", section)
        for gate in ("ci-summary", "ci-result"):
            self.assertNotIn("statistics", dependencies(gate))
            self.assertNotIn("STATISTICS", job(gate))
        gate_script = (WORKFLOW.parents[2] / "scripts/ci/ci-result-gate.sh").read_text()
        self.assertNotIn("STATISTICS", gate_script)
        self.assertIn("| Project statistics | independent |", gate_script)
        self.assertIn("this gate does not wait for collection", gate_script)
        self.assertIn("    timeout-minutes: 15\n", section)
        with tempfile.TemporaryDirectory() as directory:
            summary = Path(directory) / "summary"
            result = self.run_gate(
                "ci-result", "schedule", "skipped", GITHUB_STEP_SUMMARY=str(summary),
            )
            self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
            self.assertIn(
                "| Project statistics | skipped | Not collected on scheduled runs. |",
                summary.read_text(),
            )
        self.assertLess(section.index("Summarize selected tool versions"), section.index("Summarize project files"))
        for step, target in (
            ("Summarize project files", "project-stats"),
            ("Summarize dependency licenses", "check-licenses"),
            ("Summarize Docker builder platforms", "docker-builder-platforms"),
        ):
            content = section.split(f"      - name: {step}\n", 1)[1].split("\n      - ", 1)[0]
            self.assertIn("continue-on-error: true", content)
            self.assertIn(f"bash scripts/make/make.sh {target}", content)
            self.assertIn("bash scripts/ci/ci-statistics-summary.sh run ", content)
        self.assertIn("      - *stop_php_step\n", section)
        pr_step = section.split("      - name: Summarize PR changes by area\n", 1)[1].split("\n      - ", 1)[0]
        self.assertIn("github.event_name == 'pull_request'", pr_step)
        self.assertIn("continue-on-error: true", pr_step)
        self.assertIn('python3 scripts/reports/project-stats.py --base "$BASE_SHA" --head "$HEAD_SHA"', pr_step)

    def test_phploc_pipeline_preserves_analyzer_failure(self):
        section = job("lightweight-analyzers")
        step = section.split("      - name: Run PHPLoc\n", 1)[1].split("\n      - ", 1)[0]
        command_match = re.search(r"^        run: (.+)$", step, re.MULTILINE)
        assert command_match is not None
        command = command_match.group(1)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "scripts/make").mkdir(parents=True)
            (root / "scripts/make/make.sh").write_text("echo metric; echo diagnostic >&2; exit 7\n")
            result = subprocess.run(
                ["bash", "--noprofile", "--norc", "-eo", "pipefail", "-c", command],
                cwd=root, env={**os.environ, "RUNNER_TEMP": str(root)},
                text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 7)
            self.assertEqual(result.stdout, "metric\n")
            self.assertEqual(result.stderr, "diagnostic\n")
            self.assertEqual((root / "phploc-output.txt").read_text(), "metric\n")

    def test_metrics_and_insights_summaries_do_not_change_analyzer_results(self):
        for job_name, label, producer, filename in (
            ("test", "Publish PhpMetrics statistics", "metrics", "phpmetrics-output.txt"),
            ("static-analyzers", "Publish PHP Insights scores", "analyzer", "phpinsights-output.txt"),
        ):
            section = job(job_name)
            step = section.split(f"      - name: {label}\n", 1)[1].split("\n      - ", 1)[0]
            self.assertIn("continue-on-error: true", step)
            self.assertIn(f"steps.{producer}.outcome == 'success'", step)
            self.assertIn(f"steps.{producer}.outcome == 'failure'", step)
            self.assertIn(filename, step)
        section = job("test")
        step = section.split("      - name: Create PhpMetrics report\n", 1)[1].split("\n      - ", 1)[0]
        self.assertIn("        shell: bash\n", step)
        command = textwrap.dedent(step.split("        run: |\n", 1)[1])
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "scripts/make").mkdir(parents=True)
            (root / "scripts/make/make.sh").write_text("echo metrics; exit 7\n")
            docker = root / "docker"
            docker.write_text("#!/usr/bin/env bash\nexit 0\n")
            docker.chmod(0o755)
            result = subprocess.run(
                ["bash", "-eo", "pipefail", "-c", command], cwd=root,
                env={**os.environ, "RUNNER_TEMP": str(root), "PATH": f"{root}:{os.environ['PATH']}"},
                text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 7)
            self.assertEqual((root / "phpmetrics-output.txt").read_text(), "metrics\n")

    def test_security_checks_continue_after_failure_but_require_successful_setup(self):
        section = job("security-check")
        self.assertIn(
            "      - name: Bootstrap PHP project\n"
            "        id: bootstrap\n"
            "        uses: ./.github/actions/php-bootstrap\n", section,
        )
        self.assertNotIn("continue-on-error", without_overview(section))
        self.assertNotIn("|| true", section)
        for name, command in (
            ("PHP Security Checker", "uses: symfonycorp/security-checker-action@"),
            ("Check installed dependencies for known vulnerabilities",
             "run: bash scripts/make/make.sh check-security"),
        ):
            step_match = re.search(
                rf"^      - name: {name}\n(.*?)(?=^      - |\Z)",
                section, re.MULTILINE | re.DOTALL,
            )
            assert step_match is not None
            step = step_match.group(1)
            self.assertIn(command, step)
            condition_match = re.search(
                r"^        if: \$\{\{ (.+) \}\}$", step, re.MULTILINE,
            )
            assert condition_match is not None
            condition = condition_match.group(1)
            self.assertEqual(
                condition, "!cancelled() && steps.bootstrap.outcome == 'success'",
            )
            for bootstrap in ("success", "failure", "cancelled", "skipped"):
                for cancelled in (False, True):
                    with self.subTest(step=name, bootstrap=bootstrap, cancelled=cancelled):
                        expression = condition.replace(
                            "!cancelled()", repr(not cancelled),
                        ).replace(
                            "steps.bootstrap.outcome", repr(bootstrap),
                        ).replace("&&", "and")
                        self.assertEqual(
                            eval(expression, {"__builtins__": {}}),
                            not cancelled and bootstrap == "success",
                        )
        self.assertIn("- *failure_diagnostics_step\n      - *stop_php_step", section)

    def test_remaining_matrix_contains_only_heavy_analyzers(self):
        names = re.findall(r"^          - name: (.+)$", job("static-analyzers"), re.MULTILINE)
        self.assertEqual(names, ["Psalm", "PHPStan", "PHP Insights"])

    def test_only_matrix_jobs_have_a_strategy_and_keep_fail_fast_disabled(self):
        names = re.findall(r"^  ([a-z][a-z-]*):$", CONTENT.split("jobs:\n", 1)[1],
                           re.MULTILINE)
        matrix_jobs = {"static-analyzers", "docker-build", "docker-scan"}
        for name in names:
            with self.subTest(job=name):
                header = job(name).split("    steps:", 1)[0]
                if name in matrix_jobs:
                    self.assertIn("    strategy:\n      fail-fast: false\n      matrix:\n", header)
                else:
                    self.assertNotIn("    strategy:", header)

    def test_analyzer_matrix_passes_targets_as_one_argument_and_preserves_failures(self):
        section = job("static-analyzers")
        entries = re.findall(
            r"^          - name: (.+)\n            target: ([\w-]+)$",
            section, re.MULTILINE,
        )
        self.assertEqual(entries, [
            ("Psalm", "psalm-github"), ("PHPStan", "phpstan-github"),
            ("PHP Insights", "phpinsights"),
        ])
        self.assertNotIn("            command:", section)
        step_match = re.search(
            r"^      - name: Run static analyzer[^\n]*\n(.*?)(?=^      - |\Z)",
            section, re.MULTILINE | re.DOTALL,
        )
        assert step_match is not None
        step = step_match.group(1)
        self.assertIn("          ANALYZER_TARGET: ${{ matrix.analyzer.target }}\n", step)
        self.assertIn("        shell: bash\n", step)
        command = textwrap.dedent(step.split("        run: |\n", 1)[1])
        self.assertNotIn("${{", command)

        with tempfile.TemporaryDirectory(prefix="ci-analyzer-target-") as directory:
            root = Path(directory)
            (root / "scripts/make").mkdir(parents=True)
            (root / "scripts/make/make.sh").write_text(
                'printf "%s\\n" "$#" "$@"\nexit "$FIXTURE_EXIT"\n',
            )
            for target in [value for _, value in entries] + [
                "target with spaces", "$(touch injected); echo unexpected", "",
            ]:
                for status in (0, 7):
                    with self.subTest(target=target, status=status):
                        result = subprocess.run(
                            ["bash", "-eo", "pipefail", "-c", command], cwd=root,
                            env=dict(os.environ, ANALYZER_TARGET=target, RUNNER_TEMP=str(root),
                                     FIXTURE_EXIT=str(status)),
                            text=True, capture_output=True, check=False, timeout=10,
                        )
                        self.assertEqual(result.returncode, status, result.stderr)
                        self.assertEqual(result.stdout, f"1\n{target}\n")
                        self.assertFalse((root / "injected").exists())
                        if target == "phpinsights":
                            self.assertEqual((root / "phpinsights-output.txt").read_text(), f"1\n{target}\n")

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
            "DOCKER_SCAN", "REPORT",
            "LINT_WORKFLOWS",
        )
        environment: dict[str, str] = dict.fromkeys(variables, "skipped")
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
                if variable in ("EVENT_NAME", "REF") or variable.endswith("_CHANGED"):
                    continue
                expected = "skipped" if variable in (
                    "SECURITY_CHECK", "DOCKER_UPDATES", "DOCKER_PINS", "LINT_CONFIGURATION",
                    "STATIC_ANALYZERS", "LIGHTWEIGHT_ANALYZERS", "TEST", "DOCKER_BUILD",
                    "DOCKER_SCAN", "REPORT",
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
                f"    if: ${{{{ github.event_name != 'schedule' && {DRAFT_GUARD} }}}}\n",
                job(name),
            )
            self.assertNotIn("needs.changes", job(name))
        for name in ("ci-summary", "ci-result"):
            self.assertIn("      - changes\n", job(name))
        self.assertIn(f"    if: ${{{{ !cancelled() && {DRAFT_GUARD} }}}}\n", job("ci-summary"))
        self.assertIn("    if: ${{ !cancelled() }}\n", job("ci-result"))

    def test_documentation_job_and_step_follow_detected_inputs(self):
        section = job("lint-documentation")
        self.assertEqual(dependencies("lint-documentation"), {"changes"})
        self.assertIn(
            "    if: ${{ github.event_name != 'schedule' && "
            "needs.changes.outputs.documentation == 'true' }}\n", section,
        )
        self.assertIn(
            "        if: ${{ !cancelled() && steps.bootstrap.outcome == 'success' && "
            "needs.changes.outputs.markdown == 'true' }}\n"
            "        run: bash scripts/make/make.sh lint-markdown\n", section,
        )
        self.assertIn("run: bash scripts/make/make.sh check-skills\n", section)
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
                "DOCKER_CHANGED": ("DOCKER_BUILD", "DOCKER_SCAN"),
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
                "        if: ${{ !cancelled() && steps.bootstrap.outcome == 'success' && "
                f"needs.changes.outputs.{output} == 'true' }}}}\n"
                f"        run: bash scripts/make/make.sh {target}\n", section,
            )
        self.assertIn(
            "        if: ${{ !cancelled() && steps.bootstrap.outcome == 'success' && "
            "needs.changes.outputs.compose == 'true' }}\n"
            "        run: bash scripts/ci/ci-validate-compose.sh\n", section,
        )
        self.assertIn(
            "        if: ${{ !cancelled() && steps.bootstrap.outcome == 'success' && "
            "needs.changes.outputs.tooling == 'true' }}\n"
            "        run: |\n          bash scripts/make/make.sh test-command-output\n", section,
        )
        self.assertIn(
            "          docker compose -f compose.yaml -f docker/ci-compose.yaml exec -T php "
            "python3 scripts/checks/tests/check-workflow-tool-updates-test.py\n"
            "          bash scripts/make/make.sh lint-python\n",
            section,
        )
        self.assertIn(
            "      - name: Check Python types with Pyright\n"
            "        id: pyright\n"
            "        if: ${{ !cancelled() && steps.bootstrap.outcome == 'success' && "
            "needs.changes.outputs.tooling == 'true' }}\n"
            "        run: bash scripts/make/make.sh typecheck-python\n",
            section,
        )

    def test_configuration_checks_continue_after_failure_but_require_successful_setup(self):
        section = job("lint-configuration")
        blocking_checks = without_overview(section)
        self.assertIn(
            "      - name: Bootstrap PHP project\n"
            "        id: bootstrap\n"
            "        uses: ./.github/actions/php-bootstrap\n", section,
        )
        self.assertNotIn("continue-on-error", blocking_checks)
        self.assertNotIn("|| true", blocking_checks)
        conditions = re.findall(r"^        if: \$\{\{ (.+) \}\}$", without_overview(section), re.MULTILINE)
        self.assertEqual(len(conditions), 9)
        self.assertEqual(conditions[-1], "always()")
        self.assertEqual(conditions[-2], "failure()")
        self.assertEqual(
            re.findall(r"needs.changes.outputs.(\w+)", "\n".join(conditions[:-2])),
            ["runtime", "composer", "runtime", "yaml", "tooling", "tooling", "compose"],
        )
        for condition in conditions[:-2]:
            output_match = re.search(r"needs.changes.outputs.(\w+)", condition)
            assert output_match is not None
            output = output_match.group(1)
            self.assertEqual(
                condition,
                "!cancelled() && steps.bootstrap.outcome == 'success' && "
                f"needs.changes.outputs.{output} == 'true'",
            )
            for bootstrap in ("success", "failure", "cancelled", "skipped"):
                for selected in ("true", "false", "", "unknown"):
                    for cancelled in (False, True):
                        for earlier in ("success", "failure", "skipped"):
                            with self.subTest(condition=condition, bootstrap=bootstrap,
                                              selected=selected, cancelled=cancelled,
                                              earlier=earlier):
                                # GitHub adds success() only without a status function.
                                expression = condition.replace(
                                    "!cancelled()", repr(not cancelled),
                                ).replace(
                                    "steps.bootstrap.outcome", repr(bootstrap),
                                ).replace(
                                    f"needs.changes.outputs.{output}", repr(selected),
                                ).replace("&&", "and")
                                self.assertEqual(
                                    eval(expression, {"__builtins__": {}}),
                                    not cancelled and bootstrap == "success"
                                    and selected == "true",
                                )

    def test_documentation_checks_are_independent_after_successful_bootstrap(self):
        section = job("lint-documentation")
        self.assertIn(
            "      - name: Bootstrap development container\n"
            "        id: bootstrap\n"
            "        uses: ./.github/actions/php-bootstrap\n", section,
        )
        self.assertNotIn("continue-on-error", without_overview(section))
        self.assertNotIn("|| true", section)
        for target in ("lint-markdown", "check-skills"):
            condition_match = re.search(
                r"^        if: \$\{\{ (.+) \}\}\n"
                rf"        run: bash scripts/make/make.sh {target}\n",
                section, re.MULTILINE,
            )
            assert condition_match is not None
            condition = condition_match.group(1)
            expected = "!cancelled() && steps.bootstrap.outcome == 'success'"
            if target == "lint-markdown":
                expected += " && needs.changes.outputs.markdown == 'true'"
            self.assertEqual(condition, expected)
            for bootstrap in ("success", "failure", "cancelled", "skipped"):
                for selected in ("true", "false", "", "unknown"):
                    for cancelled in (False, True):
                        with self.subTest(target=target, bootstrap=bootstrap,
                                          selected=selected, cancelled=cancelled):
                            expression = condition.replace(
                                "!cancelled()", repr(not cancelled),
                            ).replace(
                                "steps.bootstrap.outcome", repr(bootstrap),
                            ).replace(
                                "needs.changes.outputs.markdown", repr(selected),
                            ).replace("&&", "and")
                            self.assertEqual(
                                eval(expression, {"__builtins__": {}}),
                                not cancelled and bootstrap == "success"
                                and (target == "check-skills" or selected == "true"),
                            )
        self.assertIn("- *failure_diagnostics_step\n      - *stop_php_step", section)

    def test_devcontainer_validation_parses_compose_and_json(self):
        section = job("lint-configuration")
        step = re.search(
            r"^      - name: Validate Compose and Dev Container configurations\n"
            r"        id: compose\n"
            r"        if: \$\{\{ !cancelled\(\) && steps.bootstrap.outcome == 'success' && "
            r"needs.changes.outputs.compose == 'true' \}\}\n"
            r"        run: bash scripts/ci/ci-validate-compose.sh\n",
            section, re.MULTILINE,
        )
        assert step is not None
        with tempfile.TemporaryDirectory(prefix="ci-devcontainer-") as directory:
            root = Path(directory)
            (root / "scripts/ci").mkdir(parents=True)
            script = root / "scripts/ci/ci-validate-compose.sh"
            script.write_text((WORKFLOW.parents[2] / "scripts/ci/ci-validate-compose.sh").read_text())
            docker = root / "docker"
            docker.write_text(
                "#!/usr/bin/env bash\nset -eu\n"
                'printf "%s\\n" "$*" >> "$CALLS"\n'
                'if [[ $(wc -l < "$CALLS") == "${FAIL_AT:-0}" ]]; then\n'
                '  echo "fixture failure" >&2; exit 7\nfi\n'
                'if [[ "$*" == *" exec "* ]]; then\n'
                '  shift 8\n  exec "$@"\nfi\n'
            )
            docker.chmod(0o755)
            calls = root / "calls"
            expected = [
                "compose config --quiet",
                "compose -f compose.yaml -f compose.prod.yaml config --quiet",
                "compose -f compose.yaml -f docker/ci-compose.yaml config --quiet",
                "compose -f compose.yaml -f .devcontainer/docker-compose.devcontainer.yml config --quiet",
                "compose -f compose.yaml -f docker/ci-compose.yaml exec -T php "
                "python3 -m json.tool .devcontainer/devcontainer.json /dev/null",
            ]
            def validate(fail_at=0):
                calls.unlink(missing_ok=True)
                return subprocess.run(
                    ["bash", str(script)], cwd=root.parent,
                    env={**os.environ, "PATH": f"{root}:{os.environ['PATH']}",
                         "CALLS": str(calls), "FAIL_AT": str(fail_at)},
                    text=True, capture_output=True, check=False, timeout=10,
                )
            target = root / ".devcontainer/devcontainer.json"
            target.parent.mkdir()
            for content, valid in (
                ('{"name": "Fixture", "service": "php"}', True),
                ('{"name": "Fixture" "service": "php"}', False),
                ('{"name": "Fixture",}', False),
            ):
                with self.subTest(content=content):
                    target.write_text(content)
                    result = validate()
                    self.assertEqual(result.returncode == 0, valid, result.stderr)
                    self.assertEqual(calls.read_text().splitlines(), expected)
                    self.assertEqual(result.stdout, "")
            for fail_at in range(1, 6):
                with self.subTest(fail_at=fail_at):
                    result = validate(fail_at)
                    self.assertEqual(result.returncode, 7, result.stderr)
                    self.assertIn("fixture failure", result.stderr)
                    self.assertEqual(calls.read_text().splitlines(), expected[:fail_at])
            target.unlink()
            result = validate()
            self.assertNotEqual(result.returncode, 0)


class TestReportProcessingTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="ci-test-reports-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.write("build/logs/junit.xml", '<testsuites><testsuite tests="4" assertions="8" '
                   'failures="1" errors="0" skipped="1"/></testsuites>')
        self.write("build/logs/clover.xml", '<coverage><project><metrics statements="8" '
                   'coveredstatements="3"/></project></coverage>')
        self.write("build/report/index.html", "<html>Coverage</html>")
        self.write("build/phpmetrics/index.html", "<html>Metrics</html>")

    def write(self, name, content):
        path = self.root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content)

    def run_report(self, operation):
        """Run the real report helper with paths relative to the fixture root."""
        return subprocess.run(
            ["python3", str(WORKFLOW.parents[2] / "scripts/ci/ci-test-reports.py"), operation],
            cwd=self.root, text=True, capture_output=True, check=False, timeout=10,
        )

    def test_workflow_keeps_container_execution_and_failure_policies(self):
        section = job("test")
        condition = (
            "${{ !cancelled() && (steps.phpunit.outcome == 'success' || "
            "steps.phpunit.outcome == 'failure')"
        )
        for name, operation, suffix in (
            ("Create PhpMetrics report", "validate-junit",
             " && hashFiles('build/logs/junit.xml') != '' }}"),
            ("Verify required report files", "verify", " }}"),
            ("Publish test and coverage summary", "summary",
             " && hashFiles('build/logs/junit.xml', 'build/logs/clover.xml') != '' }}"),
        ):
            step_match = re.search(
                rf"^      - name: {name}\n(.*?)(?=^      - |\Z)",
                section, re.MULTILINE | re.DOTALL,
            )
            assert step_match is not None
            step = step_match.group(1)
            self.assertIn(f"        if: {condition}{suffix}\n", step)
            command = (
                "docker compose -f compose.yaml -f docker/ci-compose.yaml "
                f"exec -T php python3 scripts/ci/ci-test-reports.py {operation}"
            )
            self.assertIn(command, step)
            self.assertEqual("continue-on-error: true" in step, operation == "summary")
            if operation == "validate-junit":
                self.assertLess(step.index(command), step.index("bash scripts/make/make.sh phpmetrics"))
            if operation == "summary":
                self.assertIn(command + ' >> "$GITHUB_STEP_SUMMARY"\n', step)

    def test_valid_reports_and_exact_summary(self):
        for operation in ("validate-junit", "verify"):
            result = self.run_report(operation)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(result.stdout, "")
        result = self.run_report("summary")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(
            result.stdout,
            "### PHPUnit results\n\n"
            "| Tests | Assertions | Failures | Errors | Skipped |\n"
            "|---|---|---|---|---|\n| 4 | 8 | 1 | 0 | 1 |\n\n"
            "Line coverage: **37.50%** (3/8 statements)\n",
        )

    def test_junit_validation_rejects_missing_empty_malformed_and_wrong_shapes(self):
        junit = self.root / "build/logs/junit.xml"
        for content in (None, "", "<testsuites>", "<testsuite/>", "<testsuites/>"):
            with self.subTest(content=content):
                if content is None:
                    junit.unlink()
                else:
                    junit.write_text(content)
                result = self.run_report("validate-junit")
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("::error::Test report validate-junit failed:", result.stderr)

    def test_verify_reports_all_missing_or_empty_paths(self):
        for path in self.root.glob("build/**/*"):
            if path.is_file():
                path.write_text("")
        (self.root / "build/logs/clover.xml").unlink()
        result = self.run_report("verify")
        self.assertNotEqual(result.returncode, 0)
        for name in ("logs/clover.xml", "logs/junit.xml", "report/index.html", "phpmetrics/index.html"):
            self.assertIn(f"::error file=build/{name}::", result.stderr)
        self.assertEqual(result.stderr.count("::error file="), 4)

    def test_junit_count_metrics_are_required_in_every_suite(self):
        counts = dict(tests="4", assertions="8", failures="1", errors="0", skipped="1")
        for name in counts:
            for value in (None, "", "-1", "1.5", "NaN", "inf", " 1", "+1", "\u0661"):
                with self.subTest(metric=name, value=value):
                    attributes = counts.copy()
                    if value is None:
                        del attributes[name]
                    else:
                        attributes[name] = value
                    invalid = "<testsuite " + " ".join(
                        f'{key}="{value}"' for key, value in attributes.items()
                    ) + "/>"
                    valid = '<testsuite tests="4" assertions="8" failures="1" errors="0" skipped="1">'
                    self.write("build/logs/junit.xml",
                               f"<testsuites>{valid}{invalid}</testsuite></testsuites>")
                    for operation in ("validate-junit", "verify"):
                        result = self.run_report(operation)
                        self.assertNotEqual(result.returncode, 0)
                        self.assertIn(f"nonnegative integer {name} metric", result.stderr)

    def test_verify_rejects_malformed_and_wrong_xml_shapes(self):
        for path, contents in (
            ("build/logs/junit.xml", ("<testsuites>", "<testsuite/>", "<testsuites/>")),
            ("build/logs/clover.xml", ("<coverage>", "<wrong><project><metrics "
                                      'statements="1" coveredstatements="0"/></project></wrong>',
                                      "<coverage/>", "<coverage><project/></coverage>",
                                      "<coverage><metrics/></coverage>")),
        ):
            original = (self.root / path).read_text()
            for content in contents:
                with self.subTest(path=path, content=content):
                    self.write(path, content)
                    result = self.run_report("verify")
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn(f"::error file={path}::", result.stderr)
            self.write(path, original)

    def test_clover_statement_metrics_reject_missing_invalid_and_excess_coverage(self):
        for name in ("statements", "coveredstatements"):
            for value in (None, "", "-1", "1.5", "NaN", "inf", " 1", "+1", "\u0661"):
                with self.subTest(metric=name, value=value):
                    attributes = dict(statements="8", coveredstatements="3")
                    if value is None:
                        del attributes[name]
                    else:
                        attributes[name] = value
                    metrics = "<metrics " + " ".join(
                        f'{key}="{value}"' for key, value in attributes.items()
                    ) + "/>"
                    self.write("build/logs/clover.xml",
                               f"<coverage><project>{metrics}</project></coverage>")
                    result = self.run_report("verify")
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn(f"nonnegative integer {name} metric", result.stderr)
        self.write("build/logs/clover.xml", '<coverage><project><metrics statements="2" '
                   'coveredstatements="3"/></project></coverage>')
        result = self.run_report("verify")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("coveredstatements exceeds statements", result.stderr)

    def test_verify_accepts_zero_counts_and_full_coverage(self):
        self.write("build/logs/junit.xml", '<testsuites><testsuite tests="0" assertions="0" '
                   'failures="0" errors="0" skipped="0"/></testsuites>')
        for total, covered in ((0, 0), (8, 8)):
            with self.subTest(total=total, covered=covered):
                self.write("build/logs/clover.xml", "<coverage><project>"
                           f'<metrics statements="{total}" coveredstatements="{covered}"/>'
                           "</project></coverage>")
                result = self.run_report("verify")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout + result.stderr, "")

    def test_verify_reports_all_content_errors_together(self):
        self.write("build/logs/junit.xml", "<testsuites>")
        self.write("build/logs/clover.xml", "<coverage/>")
        self.write("build/report/index.html", "")
        result = self.run_report("verify")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(result.stderr.count("::error file="), 3)
        for path in ("logs/junit.xml", "logs/clover.xml", "report/index.html"):
            self.assertIn(f"::error file=build/{path}::", result.stderr)
        self.assertEqual(result.stdout, "")

    def test_summary_sums_siblings_without_double_counting_nested_totals(self):
        child = '<testsuite tests="2" assertions="3"><testsuite tests="99"/></testsuite>'
        for junit in (
            f"<testsuites>{child}{child}</testsuites>",
            f'<testsuites><testsuites tests="4" assertions="6">{child}{child}</testsuites></testsuites>',
        ):
            with self.subTest(junit=junit):
                self.write("build/logs/junit.xml", junit)
                result = self.run_report("summary")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn("| 4 | 6 | 0 | 0 | 0 |", result.stdout)

    def test_summary_omits_coverage_when_metrics_absent_or_statements_zero(self):
        for metrics in ("", '<metrics statements="0" coveredstatements="0"/>'):
            with self.subTest(metrics=metrics):
                self.write("build/logs/clover.xml", f"<coverage><project>{metrics}</project></coverage>")
                result = self.run_report("summary")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertNotIn("Line coverage:", result.stdout)

    def test_summary_fails_explicitly_for_unusable_inputs_and_invalid_operation(self):
        for path, content in (
            ("build/logs/junit.xml", '<testsuite tests="invalid"/>'),
            ("build/logs/clover.xml", "<coverage>"),
            ("build/logs/clover.xml", ""),
        ):
            with self.subTest(path=path, content=content):
                self.write(path, content)
                result = self.run_report("summary")
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("::error::", result.stderr)
                self.write("build/logs/junit.xml", "<testsuites><testsuite/></testsuites>")
        (self.root / "build/logs/clover.xml").unlink()
        self.assertNotEqual(self.run_report("summary").returncode, 0)
        self.assertNotEqual(self.run_report("unknown").returncode, 0)


class ChangeDetectionTest(unittest.TestCase):
    labels = {
        "markdown": "Markdown lint",
        "documentation": "Documentation links and skill metadata",
        "runtime": "Application information and requirements",
        "composer": "Root Composer validation",
        "yaml": "Symfony YAML lint",
        "compose": "Compose and Dev Container configuration",
        "tooling": "Development tooling checks",
        "workflows": "GitHub workflow validation",
        "security": "PHP dependency security checks",
        "images": "Docker image pins and update checks",
        "php": "PHP static and lightweight analyzers",
        "tests": "PHPUnit and test reports",
        "docker": "Docker image builds and scans",
        "configuration": "Configuration job (any configuration check)",
    }

    def test_workflow_invokes_detector_with_event_inputs_and_declared_outputs(self):
        section = job("changes")
        self.assertIn("        run: bash scripts/ci/ci-detect-changes.sh\n", section)
        for binding in (
            "EVENT_NAME: ${{ github.event_name }}",
            "BASE_SHA: ${{ github.event.pull_request.base.sha || github.event.merge_group.base_sha || github.event.before }}",
            "HEAD_SHA: ${{ github.event.pull_request.head.sha || github.event.merge_group.head_sha || github.sha }}",
        ):
            self.assertIn("          " + binding + "\n", section)
        outputs = re.findall(
            r"^      (\w+): \$\{\{ steps\.detect\.outputs\.(\w+) \}\}$",
            section, re.MULTILINE,
        )
        self.detect()
        self.assertNotIn("code", dict(outputs))
        self.assertEqual(dict(outputs), {key: key for key in self.outputs})

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

    def detect(self, event="pull_request", base=None, head=None, **overrides):
        """Run change detection and capture its flags, reasons, and job preview."""
        output = self.root / ".git/detection-output"
        output.unlink(missing_ok=True)
        environment = dict(
            os.environ, EVENT_NAME=event, BASE_SHA=self.base if base is None else base,
            HEAD_SHA=self.git("rev-parse", "HEAD") if head is None else head,
            GITHUB_OUTPUT=str(output),
            **dict(dict(COLOR="never", NO_COLOR="", GITHUB_ACTIONS="false",
                        GITHUB_STEP_SUMMARY=""), **overrides),
        )
        environment = {key: value for key, value in environment.items() if value is not None}
        result = subprocess.run(
            ["bash", str(WORKFLOW.parents[2] / "scripts/ci/ci-detect-changes.sh")],
            cwd=self.root, capture_output=True,
            text=True, check=False, timeout=10,
            env=environment,
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        lines = output.read_text().splitlines()
        self.outputs = dict(line.split("=", 1) for line in lines)
        self.assertEqual(len(lines), len(self.outputs), "Duplicate detector outputs")
        self.assertTrue(set(self.outputs.values()) <= {"true", "false"})
        self.assertNotIn("code", self.outputs)
        plain_stdout = re.sub(r"\x1b\[[0-9;]*m", "", result.stdout)
        self.assertIn("CI validation preview (not results or changed-file categories):", plain_stdout)
        self.assertIn("Enabled means eligible to run; event rules and prerequisites still apply.", result.stdout)
        self.assertIn("Numbers refer to the reasons below.", result.stdout)
        selection = re.findall(
            r"^  (enabled|not enabled)\s+\[(\d+)\] (.+?) \((\w+)\)$",
            plain_stdout, re.MULTILINE,
        )
        self.assertEqual(
            [(label, name, status) for status, _, label, name in selection],
            [(self.labels[name], name, "enabled" if value == "true" else "not enabled")
             for name, value in self.outputs.items()],
        )
        reasons = dict(re.findall(r"^  \[(\d+)\] (.+)$", plain_stdout, re.MULTILINE))
        self.assertEqual(set(reasons), {reference for _, reference, _, _ in selection})
        self.reasons = {name: reasons[reference] for _, reference, _, name in selection}
        self.references = {name: reference for _, reference, _, name in selection}
        jobs_section = plain_stdout.split("\nCore CI jobs\n", 1)[1]
        groups = re.findall(
            r"^  (eligible|conditional|not enabled)\n((?:    - .+\n)+)",
            jobs_section, re.MULTILINE,
        )
        self.assertEqual([state for state, _ in groups], ["eligible", "conditional", "not enabled"])
        job_rows = [
            (state, label, name)
            for state, section in groups
            for label, name in re.findall(r"^    - (.+?) \(([\w-]+)\)$", section, re.MULTILINE)
        ]
        self.jobs = {name: state for state, _, name in job_rows}
        self.assertEqual(len(job_rows), len(self.jobs))
        workflow_ids = re.findall(
            r"^  ([a-z][a-z-]+):$", CONTENT.split("\njobs:\n", 1)[1], re.MULTILINE,
        )
        self.assertEqual(set(self.jobs), set(workflow_ids))
        return result

    def test_job_preview_matches_workflow_conditions_and_matrices(self):
        categories = {
            "lint-configuration": "configuration", "lint-documentation": "documentation",
            "lint-workflows": "workflows", "docker-image-verify-pins": "images",
            "lightweight-analyzers": "php", "static-analyzers": "php",
            "test": "tests", "docker-build": "docker", "docker-scan": "docker",
        }
        for job_id, category in categories.items():
            self.assertIn(f"needs.changes.outputs.{category} == 'true'", job(job_id))
        for job_id, category in (("security-check", "security"),
                                 ("docker-image-check-updates", "images")):
            section = job(job_id)
            self.assertIn(f"needs.changes.outputs.{category} == 'true'", section)
            self.assertIn("github.event_name == 'schedule'", section)
        self.assertEqual(
            re.findall(r"^          - name: (.+)$", job("static-analyzers"), re.MULTILINE),
            ["Psalm", "PHPStan", "PHP Insights"],
        )
        for job_id in ("docker-build", "docker-scan"):
            self.assertIn("target: [development, production]", job(job_id))
        self.assertIn("      - docker-build\n", job("docker-scan"))
        self.assertIn("github.event_name == 'pull_request'", job("report"))
        self.assertIn("github.ref == 'refs/heads/master'", job("report"))
        for job_id in ("changes", "gitleaks"):
            self.assertIn("github.event_name != 'schedule'", job(job_id))
        self.assertIn(f"if: ${{{{ !cancelled() && {DRAFT_GUARD} }}}}", job("ci-summary"))
        self.assertIn("if: ${{ !cancelled() }}", job("ci-result"))

    def test_job_preview_for_changed_files_and_event_types(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        result = self.detect()
        self.assertEqual(self.jobs["lint-documentation"], "eligible")
        self.assertEqual(self.jobs["docker-build"], "not enabled")
        self.assertEqual(self.jobs["report"], "not enabled")
        self.assertEqual(self.jobs["gitleaks"], "eligible")
        self.assertEqual(self.jobs["ci-result"], "conditional")
        self.assertRegex(result.stdout, r"\n  eligible\n(?:    - .+\n)+")
        self.assertRegex(result.stdout, r"\n  conditional\n(?:    - .+\n)+")
        self.assertRegex(result.stdout, r"\n  not enabled\n(?:    - .+\n)+")
        self.assertIn("Configuration and documentation steps depend on their category flags.", result.stdout)
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        summary = self.root / ".git/summary"
        result = self.detect(GITHUB_STEP_SUMMARY=str(summary))
        self.assertEqual(self.jobs["docker-build"], "eligible")
        self.assertEqual(self.jobs["docker-scan"], "eligible")
        self.assertEqual(self.jobs["report"], "conditional")
        self.assertIn("Docker build and scan: development and production; scans require target archives.", result.stdout)
        self.assertIn("Static analyzers: Psalm, PHPStan, PHP Insights.", result.stdout)
        self.assertIn("External reporting requires eligible events, successful tests and gates; uploads require tokens.", result.stdout)
        self.assertIn("| External reporting (`report`) | conditional |", summary.read_text())
        self.detect(event="push")
        self.assertEqual(self.jobs["report"], "conditional")
        self.detect(event="merge_group")
        self.assertEqual(self.jobs["report"], "not enabled")
        self.detect(event="workflow_dispatch")
        self.assertEqual(self.jobs["report"], "not enabled")
        self.detect(event="schedule")
        self.assertEqual(self.jobs["changes"], "not enabled")
        self.assertEqual(self.jobs["security-check"], "eligible")
        self.assertEqual(self.jobs["docker-image-check-updates"], "eligible")
        self.assertEqual(self.jobs["test"], "not enabled")

    def test_selection_colors_respect_shared_policy_and_keep_outputs_plain(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        for color, no_color, colored in (
            ("always", "", True), ("never", "", False),
            ("auto", "", False), ("always", "1", False),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = self.detect(COLOR=color, NO_COLOR=no_color)
                self.assertEqual("\x1b[34mChanged files\x1b[0m" in result.stdout, colored)
                self.assertEqual("\x1b[34mCI validation preview\x1b[0m" in result.stdout, colored)
                self.assertEqual("\x1b[34m[1]\x1b[0m" in result.stdout, colored)
                self.assertEqual("\x1b[32meligible\x1b[0m" in result.stdout, colored)
                self.assertEqual("\x1b[34mconditional\x1b[0m" in result.stdout, colored)
                self.assertEqual("\x1b[33mnot enabled\x1b[0m" in result.stdout, colored)
                if colored:
                    self.assertIn("  \x1b[34m[1]\x1b[0m Changed file: README.md (example)", result.stdout)
                    self.assertIn("\x1b[0m  \x1b[34m[1]\x1b[0m Markdown lint", result.stdout)
                self.assertIn("  - README.md\n", result.stdout)
                self.assertEqual("\x1b[32menabled" in result.stdout, colored)
                self.assertEqual("\x1b[33mnot enabled\x1b[0m" in result.stdout, colored)
                self.assertNotIn("\x1b", (self.root / ".git/detection-output").read_text())

    def test_actions_defaults_to_color_and_writes_ansi_free_summary(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        summary = self.root / ".git/summary"
        result = self.detect(
            COLOR=None, GITHUB_ACTIONS="true", GITHUB_STEP_SUMMARY=str(summary),
        )
        self.assertIn("\x1b[32m", result.stdout)
        content = summary.read_text()
        self.assertNotIn("\x1b", content)
        self.assertTrue(content.startswith(
            "Use your browser Find to search for **Core CI result** on this page for the final gate results. "
            "For gate logs, select **Validate CI result** in the jobs list or graph. "
            "A cancelled run may not reach the final gate.\n\n"
            "<details>\n<summary>CI validation preview (selection, not results)</summary>\n",
        ))
        self.assertTrue(content.endswith("</details>\n"))
        self.assertIn("not a pass/fail result", content)
        for category, selected in self.outputs.items():
            status = "🟢 Enabled" if selected == "true" else "⚪ Not enabled"
            self.assertIn(
                f"| {self.labels[category]} | `{category}` | {status} | "
                f"[{self.references[category]}] |\n", content,
            )
        self.assertEqual(len(re.findall(r"^\| [^|\n]+ \| `\w+` \|", content, re.MULTILINE)), 14)
        for overrides in ({"COLOR": "never"}, {"COLOR": None, "NO_COLOR": "1"}):
            result = self.detect(GITHUB_ACTIONS="true", **overrides)
            self.assertNotIn("\x1b", result.stdout)

    def test_summary_write_failure_is_explicit_and_preserves_selection(self):
        result = self.detect(GITHUB_STEP_SUMMARY=str(self.root / ".git"))
        self.assertIn("::warning::Could not write validation selection summary.", result.stderr)

    def test_selection_diagnostics_show_selected_and_unselected_categories(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        result = self.detect()
        self.assertIn("\nChanged files\n  - README.md\n\nCI validation preview", result.stdout)
        self.assertEqual(self.outputs["markdown"], "true")
        self.assertEqual(self.outputs["php"], "false")
        self.assertEqual(self.reasons["markdown"], "Changed file: README.md (example)")
        self.assertEqual(self.reasons["php"], "No changed files matched this check")
        self.assertEqual(self.reasons["configuration"], "No configuration categories enabled")
        self.assertEqual(self.references["markdown"], self.references["documentation"])
        self.assertEqual(result.stdout.count("Changed file: README.md (example)"), 1)

    def test_changed_file_list_preserves_spaces_and_leading_dashes(self):
        (self.root / "-notes.md").write_text("Notes\n")
        (self.root / "file name.md").write_text("Notes\n")
        self.commit()
        result = self.detect()
        self.assertIn("Changed files\n  - -notes.md\n  - file name.md\n", result.stdout)
        self.assertEqual(self.outputs["markdown"], "true")
        self.assertEqual(self.outputs["docker"], "false")

    def test_full_selection_diagnostics_explain_fallbacks(self):
        for event, base, expected in (
            ("workflow_dispatch", self.base, "Manual run"),
            ("push", "", "Comparison SHA unavailable"),
            ("push", "invalid-revision", "Changed-file comparison failed"),
            ("push", self.base, "No changed files found"),
        ):
            with self.subTest(event=event, base=base):
                result = self.detect(event=event, base=base)
                self.assertIn(expected, result.stdout)
                self.assertIn(expected, self.reasons["php"])
                self.assertEqual(set(self.outputs.values()), {"true"})

    def test_reasons_cover_shared_unknown_and_configuration_inputs(self):
        (self.root / "Dockerfile").write_text("Fixture\n")
        self.commit()
        result = self.detect()
        self.assertEqual(self.reasons["docker"], "Shared input: Dockerfile; full validation")
        self.assertEqual(result.stdout.count("Shared input: Dockerfile; full validation"), 1)
        self.assertEqual(
            self.reasons["configuration"],
            "Enabled configuration categories: runtime, composer, yaml, compose, tooling",
        )
        self.git("checkout", "--quiet", "-b", "unknown-reason", self.base)
        (self.root / "unclassified.new-format").write_text("Fixture\n")
        self.commit()
        result = self.detect()
        self.assertEqual(
            self.reasons["docker"], "Unclassified file: unclassified.new-format; full validation",
        )

    def test_summary_escapes_changed_filenames(self):
        (self.root / "a|<b>&_notes.md").write_text("Fixture\n")
        self.commit()
        summary = self.root / ".git/summary"
        result = self.detect(GITHUB_STEP_SUMMARY=str(summary))
        self.assertIn("Changed file: a|<b>&_notes.md (example)", result.stdout)
        content = summary.read_text()
        self.assertIn("Changed file: a&#124;&lt;b&gt;&amp;&#95;notes.md (example)", content)
        self.assertEqual(len(re.findall(r"^\| [^|\n]+ \| `\w+` \|", content, re.MULTILINE)), 14)

    def test_deleted_file_reason_is_explained(self):
        (self.root / "source.php").unlink()
        self.commit()
        result = self.detect()
        self.assertIn("Deleted or renamed files may affect documentation links", result.stdout)
        self.assertEqual(self.outputs["documentation"], "true")

    def test_deleted_file_comparison_failure_explains_documentation_fallback(self):
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        git = shutil.which("git")
        assert git is not None
        bin_path = self.root / "bin"
        bin_path.mkdir()
        mock = bin_path / "git"
        mock.write_text(
            "#!/bin/bash\n"
            "for arg in \"$@\"; do\n"
            "  if [ \"$arg\" = --diff-filter=D ]; then exit 1; fi\n"
            "done\n"
            f"exec {shlex.quote(git)} \"$@\"\n"
        )
        mock.chmod(0o755)
        result = self.detect(PATH=f"{bin_path}:{os.environ['PATH']}")
        self.assertIn("Deleted-file comparison failed; documentation checks retained", result.stdout)
        self.assertEqual(self.outputs["documentation"], "true")
        self.assertEqual(self.outputs["markdown"], "false")

    def test_mixed_changes_show_example_for_each_category(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        result = self.detect()
        self.assertIn("Changed file: README.md (example)", result.stdout)
        self.assertIn("Changed file: source.php (example)", result.stdout)
        self.assertIn("Enabled configuration categories: runtime", result.stdout)

    def assert_documentation(self, markdown, documentation):
        self.assertEqual(self.outputs["markdown"], str(markdown).lower())
        self.assertEqual(self.outputs["documentation"], str(documentation).lower())

    def test_path_selection_matrix(self):
        cases = {
            "src/Service/Example.php": {"runtime", "php", "tests", "docker"},
            "config/services.yaml": {"runtime", "yaml", "php", "tests", "docker"},
            "tests/Unit/ExampleTest.php": {"php", "tests"},
            "phpunit.xml.dist": {"php", "tests"},
            "scripts/ci/ci-test-reports.py": {"php", "tests", "workflows"},
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
            ".github/actionlint.yaml": {"workflows"},
            ".github/zizmor.yml": {"workflows"},
            "scripts/ci/tests/ci-gates-test.py": {"workflows", "tooling"},
            "scripts/docker/tests/docker-image-workflow-test.py": {"workflows"},
            "scripts/docker/docker-image-digest-summary.py": {"workflows", "tooling"},
            "scripts/docker/php-minor-readiness-report.py": {"workflows", "tooling"},
            "scripts/docker/php-minor-issues.py": {"workflows", "tooling"},
            "scripts/checks/lint-workflows.sh": {"workflows", "tooling"},
            "scripts/checks/download-workflow-validator.sh": {"workflows", "tooling"},
            "scripts/tests/run-workflow-tests.py": {"workflows", "tooling"},
            "scripts/ci/ci-validate-compose.sh": {"compose", "workflows"},
            "scripts/ci/ci-failure-diagnostics.sh": {"workflows"},
            "scripts/checks/check-composite-action-shell.py": {"workflows", "tooling"},
            "scripts/checks/tests/composite-action-shell-test.py": {"workflows"},
            "scripts/ci/ci-prerequisite-gate.sh": {"workflows", "tooling"},
            "scripts/ci/ci-result-gate.sh": {"workflows", "tooling"},
            "scripts/ci/ci-gate-common.sh": {"workflows", "tooling"},
            "scripts/ci/ci-workflow-overview.sh": {"workflows", "tooling"},
            "scripts/ci/ci-docker-hub-auth.sh": {"images", "workflows", "tooling"},
            "scripts/docker/sync-doctrine-mariadb.sh": {"images", "workflows", "tooling"},
            "scripts/docker/sync-ci-mariadb.sh": {"images", "workflows", "tooling"},
            "scripts/ci/ci-statistics-summary.sh": {"workflows", "tooling"},
            "scripts/docker/docker-images.sh": {"images", "tooling"},
            "scripts/docker/tests/docker-images-test.sh": {"images", "tooling"},
            "scripts/output/tests/command-output-test.py": {"tooling"},
            "scripts/tests/command-output-contract-test.sh": {"tooling"},
            "scripts/checks/check-security.sh": {"security", "tooling"},
            "scripts/make/make-help.sh": {"tooling"},
            "scripts/checks/tests/check-workflow-tool-updates-test.py": {"tooling"},
            "scripts/checks/check-skills.py": {"documentation"},
            "README.md": {"markdown", "documentation"},
            ".github/agents/implementer.agent.md": {"markdown", "documentation"},
            ".markdownlint.yml": {"markdown", "documentation", "tooling"},
            ".markdownlint-cli2.jsonc": {"markdown", "documentation", "tooling"},
            "scripts/checks/markdown-prose-wrap.cjs": {
                "markdown", "documentation", "tooling",
            },
            "scripts/checks/tests/markdown-prose-wrap-test.cjs": {
                "markdown", "documentation", "tooling",
            },
            "doc/images/new.png": {"documentation"},
        }
        for number, (path, expected) in enumerate(cases.items()):
            with self.subTest(path=path):
                self.git("checkout", "--quiet", "-b", f"path-{number}", self.base)
                target = self.root / path
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_text("Updated fixture\n")
                self.commit()
                if path.endswith(".py"):
                    expected = expected | {"tooling"}
                if expected & {"runtime", "composer", "yaml", "compose", "tooling"}:
                    expected = expected | {"configuration"}
                for event in ("pull_request", "push", "merge_group"):
                    self.detect(event)
                    selected = {key for key, value in self.outputs.items()
                                if value == "true"}
                    self.assertEqual(selected, expected)

    def test_shared_and_unknown_inputs_keep_all_checks(self):
        for number, path in enumerate((
            "Makefile", "Dockerfile", "requirements.txt", ".dockerignore",
            ".github/actions/php-bootstrap/action.yml",
            "scripts/ci/ci-validate-bootstrap.sh",
            ".github/workflows/main.yml", "scripts/make/make.sh", "scripts/output/command-output.sh",
            "scripts/output/native-output.sh", "scripts/ci/ci-detect-changes.sh", "unclassified.new-format",
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

    def test_markdown_only_edit_skips_application_checks(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        self.detect()
        self.assertEqual(self.outputs["php"], "false")
        self.assertEqual(self.outputs["tests"], "false")
        self.assertEqual(self.outputs["docker"], "false")
        self.assert_documentation(True, True)

    def test_code_only_edit_skips_documentation_on_diff_events(self):
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        for event in ("pull_request", "push", "merge_group"):
            with self.subTest(event=event):
                self.detect(event)
                self.assertEqual(self.outputs["php"], "true")
                self.assertEqual(self.outputs["tests"], "true")
                self.assert_documentation(False, False)

    def test_documentation_tool_inputs_and_deleted_link_targets(self):
        for path, markdown in (
            (".markdownlint.json", True),
            ("scripts/output/markdown-output.sh", True),
            ("Makefile", True),
            ("Dockerfile", True),
            ("scripts/checks/check-skills.py", False),
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

    def test_empty_comparison_inputs_keep_all_checks(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        for event in ("pull_request", "push", "merge_group"):
            for base, head in (("", None), (None, ""), ("", "")):
                with self.subTest(event=event, base=base, head=head):
                    self.detect(event=event, base=base, head=head)
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
        self.detect()
        self.assertEqual(self.outputs["php"], "true")
        self.assert_documentation(True, True)

    def test_markdown_add_delete_and_rename_skip_code(self):
        (self.root / "README.md").rename(self.root / "GUIDE.md")
        (self.root / "NEW.md").write_text("New documentation\n")
        self.commit()
        self.detect()
        self.assertEqual(self.outputs["php"], "false")
        self.assert_documentation(True, True)
        (self.root / "GUIDE.md").unlink()
        self.commit()
        self.detect()
        self.assertEqual(self.outputs["php"], "false")
        self.assert_documentation(True, True)

    def test_code_renamed_to_markdown_still_runs_code(self):
        (self.root / "source.php").rename(self.root / "source.md")
        self.commit()
        self.detect()
        self.assertEqual(self.outputs["php"], "true")
        self.assert_documentation(True, True)

    def test_mixed_changes_run_code(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        (self.root / "source.php").write_text("<?php echo 1;\n")
        self.commit()
        self.detect()
        self.assertEqual(self.outputs["php"], "true")

    def test_empty_diff_runs_code(self):
        self.detect()
        self.assertTrue(all(value == "true" for value in self.outputs.values()))

    def test_code_deletion_runs_code(self):
        (self.root / "source.php").unlink()
        self.commit()
        self.detect()
        self.assertEqual(self.outputs["php"], "true")

    def test_quoted_filename_fails_safe(self):
        (self.root / "unusual\nname.md").write_text("Documentation\n")
        self.commit()
        result = self.detect()
        self.assertIn('  - "unusual\\nname.md"\n', result.stdout)
        self.assertIn("Quoted Git paths found: retaining full validation.", result.stdout)
        self.assertEqual(self.reasons["docker"], "Quoted Git paths; full validation")
        self.assertTrue(all(value == "true" for value in self.outputs.values()))

    def test_failed_diff_runs_code_and_reports_error(self):
        result = self.detect(base="missing-commit")
        self.assertTrue(all(value == "true" for value in self.outputs.values()))
        self.assertIn("fatal:", result.stderr)

    def test_non_pr_events_use_categories_or_full_manual_validation(self):
        (self.root / "README.md").write_text("Updated documentation\n")
        self.commit()
        for event in ("push", "merge_group", "workflow_dispatch"):
            with self.subTest(event=event):
                self.detect(event)
                self.assertEqual(self.outputs["php"],
                                 "true" if event == "workflow_dispatch" else "false")


if __name__ == "__main__":
    unittest.main()
