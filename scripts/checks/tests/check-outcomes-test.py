#!/usr/bin/env python3
"""Verify that informational and blocking checks report and propagate outcomes."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make test-command-output
#   To run only this suite from the repository root inside the container:
#     python3 scripts/checks/tests/check-outcomes-test.py
#
# Requirements:
#   Python 3, Bash, and Make. Composer and the security checker are stubbed.
#
# Callers:
#   Scripts:
#     - `scripts/tests/command-output-contract-test.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py`.
#   Makefile:
#     - `test-command-output` runs the aggregate suite.
#   GitHub Actions:
#     - `.github/workflows/main.yml` (`lint-configuration` job, via Make).

import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[3]


class CheckOutcomesTest(unittest.TestCase):
    # Handled advisory outcomes can print WARN or FAIL without failing Make.
    def test_action_wrapper_preserves_informational_policy(self):
        for status, label in ((0, "OK"), (1, "WARN"), (2, "FAIL"), (7, "FAIL"),
                              (64, "FAIL"), (130, "WARN"), (143, "WARN")):
            with self.subTest(status=status):
                result = subprocess.run(
                    ["bash", str(ROOT / "scripts/output/action-update-output.sh"), "bash", "-c",
                     f"printf 'native findings\\n'; exit {status}"],
                    env=dict(os.environ, COLOR="always", NO_COLOR=""),
                    text=True, capture_output=True, check=False,
                )
                self.assertEqual(result.returncode, 0 if status in (0, 1, 2) else status)
                self.assertEqual(result.stdout, "native findings\n")
                color = 32 if label == "OK" else 33 if label == "WARN" else 31
                self.assertIn(f"\033[{color}m[{label}]\033[0m", result.stderr)
                self.assertRegex(result.stderr, r"\n\n\x1b\[[0-9]+m\[" + label + r"\]")
                if status in (130, 143):
                    self.assertIn(f"cancelled (exit {status})", result.stderr)
                if status in (1, 2):
                    self.assertIn("informational Make target remains successful", result.stderr)
                if status:
                    self.assertNotIn("[OK]", result.stderr)

    def test_security_failure_and_explicit_optional_skip(self):
        with tempfile.TemporaryDirectory(prefix="security-outcomes-") as directory:
            root = Path(directory)
            script_dir = root / "scripts/checks"
            output_dir = root / "scripts/output"
            script_dir.mkdir(parents=True)
            output_dir.mkdir(parents=True)
            shutil.copyfile(
                ROOT / "scripts/output/command-output.sh",
                output_dir / "command-output.sh",
            )
            security_script = script_dir / "check-security.sh"
            shutil.copyfile(ROOT / "scripts/checks/check-security.sh", security_script)
            (root / "composer").write_text(
                "#!/bin/bash\nprintf '%s\\n' \"$*\" >> \"$CALLS\"\nexit \"$COMPOSER_STATUS\"\n"
            )
            (root / "composer").chmod(0o755)
            environment = dict(
                os.environ, PATH=str(root), COLOR="never", NO_COLOR="",
                CALLS=str(root / "calls"), COMPOSER_STATUS="0",
            )
            # Keep PATH isolated from real tools while letting the copied
            # security script resolve its sibling output helper.
            (root / "dirname").write_text(
                "#!/bin/bash\nprintf '%s\\n' \"$FIXTURE_ROOT/scripts/checks\"\n",
            )
            (root / "dirname").chmod(0o755)
            environment["FIXTURE_ROOT"] = str(root)
            result = subprocess.run(
                ["/bin/bash", str(security_script)],
                env=environment, text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("[SKIP] Installed dependency security audit", result.stderr)
            self.assertIn("update --dry-run --no-plugins roave/security-advisories", (root / "calls").read_text())
            environment["COMPOSER_STATUS"] = "7"
            result = subprocess.run(
                ["/bin/bash", str(security_script)],
                env=environment, text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 7, result.stderr)
            self.assertIn("[FAIL] Security advisory dependency constraints", result.stderr)
            self.assertNotIn("[SKIP]", result.stderr)
            checker = root / "local-php-security-checker"
            checker.write_text(
                "#!/bin/bash\n"
                "if [ \"$1\" = --update-cache ]; then exit \"$CACHE_STATUS\"; fi\n"
                "exit \"$AUDIT_STATUS\"\n"
            )
            checker.chmod(0o755)
            environment["COMPOSER_STATUS"] = "0"
            for cache, audit, expected, label in (
                ("0", "0", 0, "[OK] All dependency security checks completed"),
                ("7", "0", 7, "[FAIL] Refresh security advisory cache"),
                ("0", "1", 1, "[FAIL] Installed dependency security audit"),
            ):
                with self.subTest(cache=cache, audit=audit):
                    result = subprocess.run(
                        ["/bin/bash", str(security_script)],
                        env=dict(environment, CACHE_STATUS=cache, AUDIT_STATUS=audit),
                        text=True, capture_output=True, check=False,
                    )
                    self.assertEqual(result.returncode, expected, result.stderr)
                    self.assertIn(label, result.stderr)
                    self.assertNotIn("[SKIP]", result.stderr)
                    if expected:
                        self.assertNotIn("All dependency security checks completed", result.stderr)
                        self.assertNotIn("[OK]", result.stderr.split("[FAIL]", 1)[1])
            (root / "composer").unlink()
            result = subprocess.run(
                ["/bin/bash", str(security_script)],
                env=environment, text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 127)
            self.assertIn("composer: command not found", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            (root / "bash").symlink_to("/bin/bash")
            result = subprocess.run(
                ["make", "--no-print-directory", "APPLICATION_CONFIG=fixture",
                 "INSIDE_DOCKER_CONTAINER=1", "check-security"],
                cwd=ROOT, env=environment,
                executable=shutil.which("make"), text=True, capture_output=True, check=False,
            )
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("[FAIL] Security advisory dependency constraints", result.stderr)
            self.assertNotIn("[OK]", result.stderr)

    def test_action_make_target_keeps_handled_errors_nonblocking(self):
        with tempfile.TemporaryDirectory(prefix="action-make-outcomes-") as directory:
            root = Path(directory)
            (root / "scripts/checks").mkdir(parents=True)
            (root / "scripts/output").mkdir(parents=True)
            shutil.copyfile(ROOT / "Makefile", root / "Makefile")
            for path in (
                "output/command-output.sh", "output/action-update-output.sh",
            ):
                shutil.copyfile(ROOT / "scripts" / path, root / "scripts" / path)
            checker = root / "scripts/checks/check-action-updates.sh"
            checker.write_text('#!/bin/bash\nexit "$CHECKER_STATUS"\n')
            checker.chmod(0o755)
            for status, label in ((0, "OK"), (1, "WARN"), (2, "FAIL"), (7, "FAIL")):
                with self.subTest(status=status):
                    result = subprocess.run(
                        ["make", "--no-print-directory", "INSIDE_DOCKER_CONTAINER=1",
                         "APPLICATION_CONFIG=fixture", "check-action-updates"],
                        cwd=root, env=dict(os.environ, CHECKER_STATUS=str(status), COLOR="never"),
                        text=True, capture_output=True, check=False,
                    )
                    # Make reports a recipe failure as 2, not the checker status.
                    self.assertEqual(result.returncode, 0 if status < 3 else 2, result.stderr)
                    self.assertIn(f"[{label}]", result.stderr)
                    self.assertEqual(result.stderr.count("[RUN]"), 1)
                    if status:
                        self.assertNotIn("[OK]", result.stderr)
                    if status in (1, 2):
                        self.assertIn("informational Make target remains successful", result.stderr)

    def test_makefile_has_no_legacy_status_helpers(self):
        makefile = (ROOT / "Makefile").read_text()
        for helper in ("_WARNING", "_TITLE", "_ERROR"):
            self.assertNotRegex(makefile, rf"(?m)^{helper}\s*[:?+]?=")
        self.assertNotIn("RUN_WITH_DECORATED_EXIT_OUTPUT", makefile)


if __name__ == "__main__":
    unittest.main()
