#!/usr/bin/env python3
"""Test workflow lint output, failure propagation, and validator caching offline."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make test-command-output
#   To run only this suite from the repository root inside the container:
#     python3 scripts/checks/tests/lint-workflows-output-test.py
#
# Requirements:
#   Python 3, PyYAML, Bash, and standard Linux command-line tools. The suite
#   substitutes fixtures for downloads and validator binaries.
#
# Details:
#   Each test runs in a temporary copy of the relevant repository files.
#   Repeated runs within a test share that fixture's cache so cold and warm
#   behavior can be compared without touching the repository cache.
#
# Callers:
#   Scripts:
#     - `scripts/tests/command-output-contract-test.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py`.
#   Makefile:
#     - `test-command-output` runs the aggregate suite.

import os
from pathlib import Path
import shlex
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[3]


class WorkflowOutputTest(unittest.TestCase):
    def setUp(self):
        """Build a throwaway checkout around the real validator entrypoint."""
        self.temporary = tempfile.TemporaryDirectory(prefix="workflow-output-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        for directory in (
            "scripts/checks/tests",
            "scripts/ci/tests",
            "scripts/docker/tests",
            "scripts/output",
            "scripts/tests",
            "bin",
            ".github/workflows",
            ".github/actions/example",
        ):
            (self.root / directory).mkdir(parents=True)
        for name in (
            "checks/lint-workflows.sh",
            "checks/check-composite-action-shell.py",
            "checks/download-workflow-validator.sh",
            "ci/ci-prerequisite-gate.sh",
            "ci/ci-result-gate.sh",
            "ci/ci-gate-common.sh",
            "ci/ci-workflow-overview.sh",
            "ci/ci-docker-hub-auth.sh",
            "ci/ci-statistics-summary.sh",
            "output/command-output.sh",
            "output/native-output.py",
            "tests/run-workflow-tests.py",
        ):
            shutil.copyfile(ROOT / "scripts" / name, self.root / "scripts" / name)
        shutil.copyfile(
            ROOT / "scripts/ci/tests/ci-gates-test.py",
            self.root / "scripts/ci/tests/ci-gates-test.py",
        )
        for workflow in (ROOT / ".github/workflows").glob("*.yml"):
            shutil.copyfile(workflow, self.root / ".github/workflows" / workflow.name)
        # Stand-ins let each nested suite fail without running unrelated tests.
        (self.root / "scripts/ci/tests/composer-cache-test.py").write_text(
            "import os\n"
            "import sys\n"
            "sys.exit(int(os.environ.get('CACHE_TEST_EXIT', '0')))\n"
        )
        (self.root / "scripts/ci/tests/analyzer-workflow-test.py").write_text(
            "import os\n"
            "import sys\n"
            "sys.exit(int(os.environ.get('ANALYZER_TEST_EXIT', '0')))\n"
        )
        (self.root / "scripts/docker/tests/docker-image-workflow-test.py").write_text(
            "import os\n"
            "import sys\n"
            "sys.exit(int(os.environ.get('DOCKER_WORKFLOW_TEST_EXIT', '0')))\n"
        )
        (self.root / "scripts/checks/tests/composite-action-shell-test.py").write_text(
            "import os\nimport sys\n"
            "sys.exit(int(os.environ.get('COMPOSITE_TEST_EXIT', '0')))\n"
        )
        (self.root / ".github/actions/example/action.yml").write_text(
            "name: fixture\nruns:\n  using: composite\n  steps:\n"
            "    - name: Fixture shell\n      shell: bash\n      run: echo fixture\n"
        )
        fixture = """#!/usr/bin/env bash
name=$(basename "$0")
printf '%s %s\\n' "$name" "$*" >> "$CALLS"
if [ "$name" = "${FAIL_TOOL:-}" ]; then
    printf 'fixture failure: %s\\n' "$name" >&2
    exit 7
fi
case "$name" in
    curl)
        while [ "$1" != --output ]; do shift; done
        if [ "${CURL_TIMEOUT:-}" = 1 ]; then
            printf 'partial download' > "$2"
            printf 'curl: (28) fixture transfer timed out\\n' >&2
            exit 28
        fi
        cp "$FIXTURE" "$2"
        ;;
    tar)
        while [ "$1" != -C ]; do shift; done
        directory=$2
        shift 2
        name=$(basename "${!#}")
        cp "$FIXTURE" "$directory/$name"
        chmod +x "$directory/$name"
        ;;
    sha256sum)
        read -r checksum file
        if ! cmp -s "$file" "$FIXTURE"; then
            printf 'fixture checksum mismatch\\n' >&2
            exit 9
        fi
        ;;
    uname)
        if [ "$1" = -m ]; then printf '%s\\n' "${FIXTURE_ARCH:-x86_64}"; else echo Linux; fi
        ;;
    shellcheck)
        if [ "${1:-}" = --shell ]; then cat >> "$CALLS"; fi
        for input in "$@"; do
            if [ "$input" = "${FAIL_SHELLCHECK_SCRIPT:-}" ]; then
                printf 'fixture shell finding: %s\\n' "$input" >&2
                exit 7
            fi
        done
        ;;
    zizmor)
        printf 'RUST_LOG=%s\\n' "$RUST_LOG" >> "$CALLS"
        printf 'WARN: fixture warning remains visible\\n' >&2
        printf 'No findings to report. (1 ignored, 15 suppressed)\\n'
        ;;
esac
"""
        self.fixture = self.root / "fixture"
        self.fixture.write_text(fixture)
        self.fixture.chmod(0o755)
        for name in ("curl", "tar", "sha256sum", "uname"):
            shutil.copyfile(self.fixture, self.root / "bin" / name)
            (self.root / "bin" / name).chmod(0o755)

    def run_script(self, **overrides):
        """Return the process result and tool calls for this run, keeping the cache."""
        (self.root / "calls").unlink(missing_ok=True)
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(self.root / "calls"), FIXTURE=str(self.fixture),
            COLOR="never", NO_COLOR="", FAIL_TOOL="",
        )
        environment.pop("RUST_LOG", None)
        environment.update(overrides)
        result = subprocess.run(
            ["bash", "scripts/checks/lint-workflows.sh"], cwd=self.root, env=environment,
            text=True, capture_output=True, check=False,
        )
        calls = self.root / "calls"
        return result, calls.read_text() if calls.exists() else ""

    def test_named_stages_keep_warnings_and_native_audit_summary(self):
        result, calls = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        for label in (
            "CI gate regressions (offline)",
            "Prepare and verify actionlint v1.7.12",
            "Prepare and verify ShellCheck v0.11.0",
            "Prepare and verify action-validator v0.9.0",
            "Prepare and verify zizmor v1.32.0",
            "Workflow syntax and embedded shell (7 files)",
            "Workflow and action security audit (zizmor)",
            "Local action metadata: .github/actions/example/action.yml",
            "Local composite action shell (ShellCheck)",
            "Composite action shell regressions (offline)",
            "CI tooling scripts (ShellCheck)",
            "Composer cache mount regressions (offline)",
            "Analyzer job and gate regressions (offline)",
            "Docker maintenance workflow regressions (offline)",
        ):
            self.assertIn("[RUN] " + label, result.stderr)
            self.assertIn("[OK] " + label, result.stderr)
        self.assertIn("fixture warning remains visible", result.stderr)
        self.assertIn("1 ignored, 15 suppressed", result.stdout)
        self.assertEqual(calls.count("sha256sum --check --quiet"), 4)
        self.assertIn("zizmor --no-progress --offline --min-severity low", calls)
        self.assertIn("RUST_LOG=warn", calls)
        self.assertIn(
            "shellcheck scripts/checks/lint-workflows.sh scripts/checks/download-workflow-validator.sh "
            "scripts/output/command-output.sh scripts/ci/check-composer-cache.sh scripts/ci/ci-docker-hub-auth.sh scripts/ci/ci-detect-changes.sh "
            "scripts/ci/ci-prerequisite-gate.sh scripts/ci/ci-result-gate.sh "
            "scripts/ci/ci-gate-common.sh scripts/ci/ci-workflow-overview.sh scripts/ci/ci-statistics-summary.sh scripts/ci/ci-validate-compose.sh "
            "scripts/ci/ci-failure-diagnostics.sh scripts/ci/ci-validate-bootstrap.sh "
            "scripts/checks/check-action-updates.sh scripts/docker/docker-images.sh "
            "scripts/docker/sync-doctrine-mariadb.sh scripts/docker/sync-ci-mariadb.sh scripts/sync-composer-php.sh "
            "scripts/make/make.sh "
            "scripts/checks/check-security.sh scripts/reports/prepare-sonar-reports.sh", calls,
        )
        self.assertNotIn("\033", result.stderr)
        self.assertIn("shellcheck --shell bash -", calls)
        self.assertIn("echo fixture", calls)
        groups = (
            "1/5 - Validate CI gate contracts",
            "2/5 - Prepare pinned workflow validators",
            "3/5 - Validate workflows",
            "4/5 - Validate local actions",
            "5/5 - Validate tooling scripts",
        )
        positions = []
        for heading in groups:
            marker = f"\n[RUN] {heading}\n"
            self.assertEqual(result.stderr.count(marker), 1)
            positions.append(result.stderr.index(marker))
        self.assertEqual(positions, sorted(positions))
        gate_start = result.stderr.index("[RUN] CI gate regressions (offline)\n")
        gate_end = result.stderr.index("[OK] CI gate regressions (offline)\n")
        self.assertIn("Ran 22 tests", result.stderr[gate_start:gate_end])
        self.assertIn(
            "\n[RUN] 1/5 - Validate CI gate contracts\n\n"
            "  [RUN] CI gate regressions (offline)\n", result.stderr,
        )
        self.assertRegex(
            result.stderr,
            r"\n    Ran 22 tests in [^\n]+\n  \[OK\] CI gate regressions \(offline\)\n",
        )
        self.assertNotIn("\nOK\n", result.stderr)
        self.assertNotIn("\n    OK\n", result.stderr)
        self.assertIn("\n    WARN: fixture warning remains visible\n", result.stderr)
        self.assertIn("\n    No findings to report.", "\n" + result.stdout)
        self.assertIn(
            "\n    .github/actions/example/action.yml: step 1 (Fixture shell)\n",
            result.stdout + result.stderr,
        )
        self.assertIn(
            "\n  [RUN] Prepare and verify actionlint v1.7.12\n"
            "  [OK] Prepare and verify actionlint v1.7.12\n\n"
            "  [RUN] Prepare and verify ShellCheck v0.11.0\n"
            "  [OK] Prepare and verify ShellCheck v0.11.0\n",
            result.stderr,
        )
        self.assertNotIn("\n\n\n", result.stderr)
        self.assertTrue(result.stderr.endswith(
            "[OK] Docker maintenance workflow regressions (offline)\n",
        ))

    def test_gate_regression_failure_is_labeled_and_stops_before_downloads(self):
        (self.root / "scripts/ci/tests/ci-gates-test.py").write_text(
            "import sys\n"
            "print('fixture gate diagnostic', file=sys.stderr)\n"
            "sys.exit(7)\n",
        )
        result, calls = self.run_script()
        self.assertEqual(result.returncode, 7, result.stderr)
        self.assertIn("[RUN] CI gate regressions (offline)\n", result.stderr)
        self.assertIn("fixture gate diagnostic\n", result.stderr)
        self.assertIn(
            "fixture gate diagnostic\n  [FAIL] CI gate regressions (offline)",
            result.stderr,
        )
        self.assertIn("[FAIL] CI gate regressions (offline) (exit 7)\n", result.stderr)
        self.assertNotIn("[RUN] 2/5", result.stderr)
        self.assertEqual(calls, "")

    def test_nested_runner_preserves_results_diagnostics_and_suite_arguments(self):
        suite = self.root / "scripts/checks/tests/example.py"
        suite.write_text(
            "import sys\nimport unittest\n"
            "argument = sys.argv.pop(1)\n"
            "class Example(unittest.TestCase):\n"
            "    def test_example(self):\n"
            "        print('OK')\n"
            "        if argument == 'skip': self.skipTest('fixture skip')\n"
            "        self.assertEqual(argument, 'success', 'fixture assertion')\n"
            "if __name__ == '__main__': unittest.main()\n",
        )
        for argument, status in (("success", 0), ("skip", 0), ("failure", 1)):
            with self.subTest(argument=argument):
                result = subprocess.run(
                    ["python3", str(self.root / "scripts/tests/run-workflow-tests.py"),
                     str(suite), argument],
                    cwd=self.root, text=True, capture_output=True, check=False,
                    timeout=10,
                )
                self.assertEqual(result.returncode, status, result.stderr)
                self.assertEqual(result.stdout, "OK\n")
                self.assertIn("    Ran 1 test", result.stderr)
                self.assertTrue(all(
                    not line or line.startswith("    ")
                    for line in result.stderr.splitlines()
                ))
                self.assertNotIn("\n    OK\n", result.stderr)
                if argument == "skip":
                    self.assertIn("skipped=1", result.stderr)
                if argument == "failure":
                    self.assertIn("fixture assertion", result.stderr)
                    self.assertIn("FAILED (failures=1)", result.stderr)

    def test_failures_stop_the_stage_and_preserve_exit_code(self):
        for tool in ("curl", "sha256sum", "actionlint", "zizmor", "action-validator", "shellcheck"):
            with self.subTest(tool=tool):
                # The download and checksum failure cases need a cold cache.
                for cached in self.root.glob("build/cache/workflow-validators/**/*"):
                    if cached.is_file():
                        cached.unlink()
                result, calls = self.run_script(FAIL_TOOL=tool)
                self.assertEqual(result.returncode, 7, result.stderr)
                self.assertIn(f"fixture failure: {tool}", result.stderr)
                self.assertIn("[FAIL]", result.stderr)
                if tool == "curl":
                    self.assertNotIn("sha256sum", calls)
                if tool in ("curl", "sha256sum"):
                    self.assertNotIn("[OK] Prepare and verify actionlint", result.stderr)

    def test_ci_entrypoint_shell_findings_block_validation(self):
        for script in (
            "scripts/checks/download-workflow-validator.sh",
            "scripts/checks/check-action-updates.sh",
            "scripts/ci/ci-docker-hub-auth.sh",
            "scripts/docker/docker-images.sh",
            "scripts/docker/sync-doctrine-mariadb.sh",
            "scripts/docker/sync-ci-mariadb.sh",
            "scripts/sync-composer-php.sh",
            "scripts/make/make.sh",
            "scripts/checks/check-security.sh",
            "scripts/reports/prepare-sonar-reports.sh",
        ):
            with self.subTest(script=script):
                result, _ = self.run_script(FAIL_SHELLCHECK_SCRIPT=script)
                self.assertEqual(result.returncode, 7, result.stderr)
                self.assertIn(f"fixture shell finding: {script}", result.stderr)
                self.assertIn("[FAIL] CI tooling scripts (ShellCheck)", result.stderr)
                self.assertNotIn("[RUN] Composer cache mount regressions", result.stderr)

    def test_warm_cache_is_verified_without_downloads(self):
        cold, cold_calls = self.run_script()
        self.assertEqual(cold.returncode, 0, cold.stderr)
        self.assertEqual(cold_calls.count("curl "), 4)
        warm, warm_calls = self.run_script()
        self.assertEqual(warm.returncode, 0, warm.stderr)
        self.assertNotIn("curl ", warm_calls)
        self.assertEqual(warm_calls.count("sha256sum --check --quiet"), 4)
        self.assertEqual(warm_calls.count("tar "), 3)
        self.assertIn("action-validator .github/actions/example/action.yml", warm_calls)
        cached = list(self.root.glob("build/cache/workflow-validators/linux-amd64/*"))
        self.assertEqual(len(cached), 4)
        self.assertTrue(all(len(path.name) == 64 for path in cached))

    def test_cold_downloads_have_bounded_connection_transfer_and_retries(self):
        result, calls = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        downloads = [shlex.split(line) for line in calls.splitlines()
                     if line.startswith("curl ")]
        self.assertEqual(len(downloads), 4)
        for arguments in downloads:
            with self.subTest(url=arguments[-3]):
                for option, value in (
                    ("--connect-timeout", "10"), ("--max-time", "60"),
                    ("--retry", "3"), ("--retry-max-time", "120"),
                ):
                    self.assertEqual(arguments.count(option), 1)
                    self.assertEqual(arguments[arguments.index(option) + 1], value)
                self.assertIn("--fail", arguments)
                self.assertIn("--show-error", arguments)

    def test_timeout_preserves_status_and_cleans_partial_download_without_caching(self):
        temporary = self.root / "downloads"
        temporary.mkdir()
        result, calls = self.run_script(CURL_TIMEOUT="1", TMPDIR=str(temporary))
        self.assertEqual(result.returncode, 28, result.stderr)
        self.assertIn("\n    curl: (28) fixture transfer timed out\n", result.stderr)
        self.assertNotIn("\ncurl:", result.stderr)
        self.assertIn("[FAIL] Prepare and verify actionlint", result.stderr)
        self.assertNotIn("[OK] Prepare and verify actionlint", result.stderr)
        self.assertEqual(calls.count("curl "), 1)
        self.assertNotIn("sha256sum", calls)
        self.assertNotIn("tar ", calls)
        self.assertNotIn("actionlint -shellcheck", calls)
        self.assertEqual(list(temporary.iterdir()), [])
        self.assertEqual(list(self.root.glob("build/cache/workflow-validators/**/*")), [])

    def test_corrupt_cached_download_fails_before_extraction_or_execution(self):
        first, _ = self.run_script()
        self.assertEqual(first.returncode, 0, first.stderr)
        for cached in self.root.glob("build/cache/workflow-validators/linux-amd64/*"):
            cached.write_text("corrupt")
        result, calls = self.run_script()
        self.assertEqual(result.returncode, 9, result.stderr)
        self.assertIn("\n    fixture checksum mismatch\n", result.stderr)
        self.assertNotIn("curl ", calls)
        self.assertNotIn("tar ", calls)
        self.assertNotIn("actionlint -shellcheck", calls)

    def test_platform_and_checksum_changes_cannot_reuse_wrong_download(self):
        first, _ = self.run_script()
        self.assertEqual(first.returncode, 0, first.stderr)
        arm, arm_calls = self.run_script(FIXTURE_ARCH="aarch64")
        self.assertEqual(arm.returncode, 0, arm.stderr)
        self.assertEqual(arm_calls.count("curl "), 4)
        self.assertEqual(len(list(self.root.glob(
            "build/cache/workflow-validators/linux-arm64/*",
        ))), 4)
        script = self.root / "scripts/checks/lint-workflows.sh"
        script.write_text(script.read_text().replace(
            "8aca8db96f1b94770f1b0d72b6dddcb1ebb8123cb3712530b08cc387b349a3d8",
            "a" * 64,
        ))
        changed, changed_calls = self.run_script()
        self.assertEqual(changed.returncode, 0, changed.stderr)
        self.assertEqual(changed_calls.count("curl "), 1)

    def test_failed_download_or_checksum_never_populates_cache(self):
        for tool in ("curl", "sha256sum"):
            with self.subTest(tool=tool):
                result, _ = self.run_script(FAIL_TOOL=tool)
                self.assertEqual(result.returncode, 7, result.stderr)
                self.assertEqual(list(self.root.glob(
                    "build/cache/workflow-validators/**/*",
                )), [])

    def test_explicit_debug_logging_is_preserved(self):
        result, calls = self.run_script(RUST_LOG="debug", COLOR="always", NO_COLOR="1")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("RUST_LOG=debug", calls)
        self.assertNotIn("\033", result.stderr)

    def test_cache_regression_failure_blocks_validation(self):
        result, _ = self.run_script(CACHE_TEST_EXIT="7")
        self.assertEqual(result.returncode, 7, result.stderr)
        self.assertIn("[FAIL] Composer cache mount regressions (offline)", result.stderr)
        self.assertNotIn("[OK] Composer cache mount regressions (offline)", result.stderr)

    def test_analyzer_regression_failure_blocks_validation(self):
        result, _ = self.run_script(ANALYZER_TEST_EXIT="7")
        self.assertEqual(result.returncode, 7, result.stderr)
        self.assertIn("[FAIL] Analyzer job and gate regressions (offline)", result.stderr)
        self.assertNotIn("[OK] Analyzer job and gate regressions (offline)", result.stderr)

    def test_docker_workflow_regression_failure_blocks_validation(self):
        result, _ = self.run_script(DOCKER_WORKFLOW_TEST_EXIT="7")
        self.assertEqual(result.returncode, 7, result.stderr)
        self.assertIn("[FAIL] Docker maintenance workflow regressions (offline)", result.stderr)
        self.assertNotIn("[OK] Docker maintenance workflow regressions (offline)", result.stderr)

    def test_composite_shell_regression_failure_blocks_validation(self):
        result, _ = self.run_script(COMPOSITE_TEST_EXIT="7")
        self.assertEqual(result.returncode, 7, result.stderr)
        self.assertIn("[FAIL] Composite action shell regressions (offline)", result.stderr)
        self.assertNotIn("[RUN] 5/5", result.stderr)

    def test_invalid_composite_metadata_blocks_shell_validation(self):
        (self.root / ".github/actions/example/action.yml").write_text("runs: [")
        result, _ = self.run_script()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL] Local composite action shell (ShellCheck)", result.stderr)
        self.assertNotIn("[RUN] 5/5", result.stderr)


if __name__ == "__main__":
    unittest.main()
