#!/usr/bin/env python3
"""Offline stage-output and failure regression tests for workflow validation."""

import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]


class WorkflowOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="workflow-output-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        for directory in ("scripts/tests", "bin", ".github/workflows", ".github/actions/example"):
            (self.root / directory).mkdir(parents=True)
        for name in (
            "lint-workflows.sh", "command-output.sh", "ci-prerequisite-gate.sh",
            "ci-result-gate.sh", "ci-gate-common.sh", "check-composite-action-shell.py",
        ):
            shutil.copyfile(ROOT / "scripts" / name, self.root / "scripts" / name)
        shutil.copyfile(
            ROOT / "scripts/tests/ci-gates-test.py",
            self.root / "scripts/tests/ci-gates-test.py",
        )
        shutil.copyfile(
            ROOT / ".github/workflows/main.yml",
            self.root / ".github/workflows/main.yml",
        )
        (self.root / "scripts/tests/composer-cache-test.py").write_text(
            "import os\n"
            "import sys\n"
            "sys.exit(int(os.environ.get('CACHE_TEST_EXIT', '0')))\n"
        )
        (self.root / "scripts/tests/analyzer-workflow-test.py").write_text(
            "import os\n"
            "import sys\n"
            "sys.exit(int(os.environ.get('ANALYZER_TEST_EXIT', '0')))\n"
        )
        (self.root / "scripts/tests/docker-image-workflow-test.py").write_text(
            "import os\n"
            "import sys\n"
            "sys.exit(int(os.environ.get('DOCKER_WORKFLOW_TEST_EXIT', '0')))\n"
        )
        (self.root / "scripts/tests/composite-action-shell-test.py").write_text(
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
        (self.root / "calls").unlink(missing_ok=True)
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(self.root / "calls"), FIXTURE=str(self.fixture),
            COLOR="never", NO_COLOR="", FAIL_TOOL="",
        )
        environment.pop("RUST_LOG", None)
        environment.update(overrides)
        result = subprocess.run(
            ["bash", "scripts/lint-workflows.sh"], cwd=self.root, env=environment,
            text=True, capture_output=True, check=False,
        )
        return result, (self.root / "calls").read_text()

    def test_named_stages_keep_warnings_and_native_audit_summary(self):
        result, calls = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        for label in (
            "Prepare and verify actionlint v1.7.12",
            "Prepare and verify ShellCheck v0.11.0",
            "Prepare and verify action-validator v0.9.0",
            "Prepare and verify zizmor v1.32.0",
            "Workflow syntax and embedded shell (1 files)",
            "Workflow and action security audit (zizmor)",
            "Local action metadata: .github/actions/example/action.yml",
            "Local composite action shell (ShellCheck)",
            "Composite action shell regressions (offline)",
            "Workflow validator script (ShellCheck)",
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
            "shellcheck scripts/lint-workflows.sh scripts/command-output.sh "
            "scripts/check-composer-cache.sh scripts/ci-detect-changes.sh "
            "scripts/ci-prerequisite-gate.sh scripts/ci-result-gate.sh "
            "scripts/ci-gate-common.sh scripts/ci-validate-compose.sh", calls,
        )
        self.assertNotIn("\033", result.stderr)
        self.assertIn("shellcheck --shell bash -", calls)
        self.assertIn("echo fixture", calls)
        groups = (
            "1/4 - Prepare pinned workflow validators",
            "2/4 - Validate workflows",
            "3/4 - Validate local actions",
            "4/4 - Validate tooling scripts",
        )
        positions = []
        for heading in groups:
            marker = f"\n[RUN] {heading}\n"
            self.assertEqual(result.stderr.count(marker), 1)
            positions.append(result.stderr.index(marker))
        self.assertEqual(positions, sorted(positions))

    def test_failures_stop_the_stage_and_preserve_exit_code(self):
        for tool in ("curl", "sha256sum", "actionlint", "zizmor", "action-validator", "shellcheck"):
            with self.subTest(tool=tool):
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

    def test_corrupt_cached_download_fails_before_extraction_or_execution(self):
        first, _ = self.run_script()
        self.assertEqual(first.returncode, 0, first.stderr)
        for cached in self.root.glob("build/cache/workflow-validators/linux-amd64/*"):
            cached.write_text("corrupt")
        result, calls = self.run_script()
        self.assertEqual(result.returncode, 9, result.stderr)
        self.assertIn("fixture checksum mismatch", result.stderr)
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
        script = self.root / "scripts/lint-workflows.sh"
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
        self.assertNotIn("[RUN] 4/4", result.stderr)

    def test_invalid_composite_metadata_blocks_shell_validation(self):
        (self.root / ".github/actions/example/action.yml").write_text("runs: [")
        result, _ = self.run_script()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL] Local composite action shell (ShellCheck)", result.stderr)
        self.assertNotIn("[RUN] 4/4", result.stderr)


if __name__ == "__main__":
    unittest.main()
