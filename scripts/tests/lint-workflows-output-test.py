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
        for directory in ("scripts", "bin", ".github/workflows", ".github/actions/example"):
            (self.root / directory).mkdir(parents=True)
        for name in ("lint-workflows.sh", "command-output.sh"):
            shutil.copyfile(ROOT / "scripts" / name, self.root / "scripts" / name)
        (self.root / ".github/workflows/main.yml").write_text("name: fixture\n")
        (self.root / ".github/actions/example/action.yml").write_text("name: fixture\n")
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
        cat >/dev/null
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
        for name in ("curl", "tar", "sha256sum"):
            shutil.copyfile(self.fixture, self.root / "bin" / name)
            (self.root / "bin" / name).chmod(0o755)

    def run_script(self, **overrides):
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
            "Download and verify actionlint v1.7.12",
            "Download and verify ShellCheck v0.11.0",
            "Download and verify action-validator v0.9.0",
            "Download and verify zizmor v1.31.1",
            "Workflow syntax and embedded shell (1 files)",
            "Workflow and action security audit (zizmor)",
            "Local action metadata: .github/actions/example/action.yml",
            "Workflow validator script (ShellCheck)",
        ):
            self.assertIn("[RUN] " + label, result.stderr)
            self.assertIn("[OK] " + label, result.stderr)
        self.assertIn("fixture warning remains visible", result.stderr)
        self.assertIn("1 ignored, 15 suppressed", result.stdout)
        self.assertEqual(calls.count("sha256sum --check --quiet"), 4)
        self.assertIn("zizmor --no-progress --offline --min-severity low", calls)
        self.assertIn("RUST_LOG=warn", calls)
        self.assertNotIn("\033", result.stderr)
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
                result, calls = self.run_script(FAIL_TOOL=tool)
                self.assertEqual(result.returncode, 7, result.stderr)
                self.assertIn(f"fixture failure: {tool}", result.stderr)
                self.assertIn("[FAIL]", result.stderr)
                if tool == "curl":
                    self.assertNotIn("sha256sum", calls)
                if tool in ("curl", "sha256sum"):
                    self.assertNotIn("[OK] Download and verify actionlint", result.stderr)

    def test_explicit_debug_logging_is_preserved(self):
        result, calls = self.run_script(RUST_LOG="debug", COLOR="always", NO_COLOR="1")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("RUST_LOG=debug", calls)
        self.assertNotIn("\033", result.stderr)


if __name__ == "__main__":
    unittest.main()
