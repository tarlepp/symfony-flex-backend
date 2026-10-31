#!/usr/bin/env python3
"""Offline regression checks for informational and blocking check outcomes."""

import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]


class CheckOutcomesTest(unittest.TestCase):
    def test_action_wrapper_preserves_informational_policy(self):
        for status, label in ((0, "OK"), (1, "WARN"), (2, "FAIL"), (7, "FAIL"), (64, "FAIL")):
            with self.subTest(status=status):
                result = subprocess.run(
                    ["bash", str(ROOT / "scripts/action-update-output.sh"), "bash", "-c",
                     f"printf 'native findings\\n'; exit {status}"],
                    env=dict(os.environ, COLOR="always", NO_COLOR=""),
                    text=True, capture_output=True, check=False,
                )
                self.assertEqual(result.returncode, 0 if status in (0, 1, 2) else status)
                self.assertEqual(result.stdout, "native findings\n")
                color = 32 if label == "OK" else 33 if label == "WARN" else 31
                self.assertIn(f"\033[{color}m[{label}]\033[0m", result.stderr)
                if status in (1, 2):
                    self.assertIn("informational Make target remains successful", result.stderr)
                if status:
                    self.assertNotIn("[OK]", result.stderr)

    def test_security_failure_and_explicit_optional_skip(self):
        with tempfile.TemporaryDirectory(prefix="security-outcomes-") as directory:
            root = Path(directory)
            for name in ("command-output.sh", "check-security.sh"):
                shutil.copyfile(ROOT / "scripts" / name, root / name)
            (root / "composer").write_text(
                "#!/bin/bash\nprintf '%s\\n' \"$*\" >> \"$CALLS\"\nexit \"$COMPOSER_STATUS\"\n"
            )
            (root / "composer").chmod(0o755)
            environment = dict(
                os.environ, PATH=str(root), COLOR="never", NO_COLOR="",
                CALLS=str(root / "calls"), COMPOSER_STATUS="0",
            )
            # The production scripts resolve their helper relative to their own path.
            (root / "dirname").write_text("#!/bin/bash\nprintf '%s\\n' \"$FIXTURE_ROOT\"\n")
            (root / "dirname").chmod(0o755)
            environment["FIXTURE_ROOT"] = str(root)
            result = subprocess.run(
                ["/bin/bash", str(root / "check-security.sh")],
                env=environment, text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("[SKIP] Installed dependency security audit", result.stderr)
            self.assertIn("update --dry-run --no-plugins roave/security-advisories", (root / "calls").read_text())
            environment["COMPOSER_STATUS"] = "7"
            result = subprocess.run(
                ["/bin/bash", str(root / "check-security.sh")],
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
                        ["/bin/bash", str(root / "check-security.sh")],
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
                ["/bin/bash", str(root / "check-security.sh")],
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
            (root / "scripts").mkdir()
            shutil.copyfile(ROOT / "Makefile", root / "Makefile")
            for name in ("command-output.sh", "action-update-output.sh"):
                shutil.copyfile(ROOT / "scripts" / name, root / "scripts" / name)
            checker = root / "scripts/check-action-updates.sh"
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
                    self.assertEqual(result.returncode, 0 if status < 3 else 2, result.stderr)
                    self.assertIn(f"[{label}]", result.stderr)


if __name__ == "__main__":
    unittest.main()
