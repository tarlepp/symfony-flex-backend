#!/usr/bin/env python3
"""Exercise composite action parsing and real ShellCheck failure propagation."""

from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

import yaml


CHECKER = Path(__file__).resolve().parents[1] / "check-composite-action-shell.py"
SHELLCHECK = sys.argv.pop(1)


class CompositeShellTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="composite-shell-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)

    def check(self, steps, using="composite"):
        action = self.root / "action.yml"
        action.write_text(yaml.safe_dump({"runs": {"using": using, "steps": steps}}))
        return self.run_checker(action)

    def run_checker(self, *actions, shellcheck=SHELLCHECK):
        return subprocess.run(
            [sys.executable, str(CHECKER), "--shellcheck", shellcheck,
             *(str(path) for path in actions)],
            text=True, capture_output=True, check=False, timeout=15,
        )

    def test_valid_bash_sh_and_uses_steps(self):
        result = self.check([
            {"uses": "example/action@fixture"},
            {"name": "Bash block", "shell": "bash", "run": 'name=value\necho "$name"\n'},
            {"shell": "sh", "run": 'printf "%s\\n" "${{ inputs.value }}"'},
            {"shell": "bash --noprofile --norc -euo pipefail {0}", "run": "true"},
        ])
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("step 2 (Bash block)", result.stderr)
        self.assertIn("step 4 (run)", result.stderr)
        self.assertNotIn("step 1", result.stderr)

    def test_shell_errors_in_later_steps_fail_with_identity(self):
        for script, code in (("if true; then", "SC"), ("echo $undefined", "SC2086")):
            with self.subTest(script=script):
                result = self.check([
                    {"shell": "bash", "run": "true"},
                    {"id": "broken", "shell": "bash", "run": script},
                    {"shell": "sh", "run": "true"},
                ])
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(code, result.stdout)
                self.assertIn("step 2 (broken)", result.stderr)
                self.assertIn("step 3", result.stderr)

    def test_noncomposite_actions_are_not_shell_checked(self):
        result = self.check([], using="node24")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout + result.stderr, "")

    def test_unsupported_shell_fails_explicitly(self):
        for shell, message in (("pwsh", "unsupported shell 'pwsh'"),
                               ("", "shell must not be empty")):
            with self.subTest(shell=shell):
                result = self.check([{"shell": shell, "run": "true"}])
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(message, result.stderr)

    def test_invalid_or_missing_metadata_fails(self):
        action = self.root / "invalid.yaml"
        for content in ("runs: [", "runs: {}", "null"):
            with self.subTest(content=content):
                action.write_text(content)
                result = self.run_checker(action)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(f"::error file={action}::", result.stderr)
        action.unlink()
        self.assertNotEqual(self.run_checker(action).returncode, 0)

    def test_shellcheck_launch_failure_is_not_success(self):
        self.check([{"shell": "bash", "run": "true"}])
        result = self.run_checker(self.root / "action.yml",
                                  shellcheck=str(self.root / "missing-shellcheck"))
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Composite action shell validation failed", result.stderr)

    def test_scripts_are_never_executed_and_multiple_actions_are_checked(self):
        marker = self.root / "must-not-exist"
        result = self.check([{"shell": "bash", "run": f"touch '{marker}'"}])
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(marker.exists())
        invalid = self.root / "second action.yaml"
        invalid.write_text("runs:\n  using: composite\n  steps:\n"
                           "    - shell: sh\n      run: echo $undefined\n")
        result = self.run_checker(self.root / "action.yml", invalid)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn(str(invalid), result.stderr)


if __name__ == "__main__":
    unittest.main()
