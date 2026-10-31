#!/usr/bin/env python3
"""Exercise Make check recipes with offline command fixtures."""

import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
CHECKS = (
    ("check-skills", "Skill metadata and documentation links", "python3 scripts/check-skills.py"),
    ("lint-workflows", "GitHub workflows and local action metadata", "bash scripts/lint-workflows.sh"),
    ("check-requirements", "Application runtime requirements", "php ./vendor/bin/requirements-checker"),
    ("test-make-routing", "Make container routing (offline mocked tests)",
     "bash scripts/tests/make-container-test.sh"),
    ("docker-image-verify-pins", "Repository Dockerfile pins (live registry verification)",
     "./scripts/docker-images.sh verify"),
)


class MakeCheckOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-check-output-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        (self.root / "scripts/tests").mkdir(parents=True)
        (self.root / "bin").mkdir()
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        shutil.copyfile(ROOT / "scripts/command-output.sh", self.root / "scripts/command-output.sh")
        fixture = (
            "#!/usr/bin/env bash\n"
            "printf '%s\\n' \"$0 $*\" >> \"$CALLS\"\n"
            "printf 'fixture stdout\\n'\n"
            "printf 'fixture diagnostic\\n' >&2\n"
            "exit \"$FIXTURE_STATUS\"\n"
        )
        for name in (
            "bin/python3", "bin/php", "scripts/lint-workflows.sh",
            "scripts/tests/make-container-test.sh", "scripts/docker-images.sh",
        ):
            file = self.root / name
            file.write_text(fixture)
            file.chmod(0o755)
        docker = self.root / "bin/docker"
        docker.write_text("#!/usr/bin/env bash\nexit 0\n")
        docker.chmod(0o755)

    def run_check(self, target, status=0, color="never", no_color=""):
        calls = self.root / "calls"
        calls.write_text("")
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(calls), FIXTURE_STATUS=str(status), NO_COLOR=no_color,
        )
        result = subprocess.run(
            ["make", "--no-print-directory", "APPLICATION_CONFIG=unused",
             "INSIDE_DOCKER_CONTAINER=1", "INSIDE_CONTAINER_RUNTIME=0",
             f"COLOR={color}", target],
            cwd=self.root, env=environment, text=True, capture_output=True, check=False,
        )
        return result, calls.read_text()

    def test_checks_preserve_commands_streams_and_failure_status(self):
        for target, label, command in CHECKS:
            for status in (0, 1, 2, 7, 127):
                with self.subTest(target=target, status=status):
                    result, calls = self.run_check(target, status)
                    self.assertIn("[RUN] " + label, result.stderr)
                    self.assertEqual(result.stdout, "fixture stdout\n")
                    self.assertIn("fixture diagnostic", result.stderr)
                    arguments = command.split(" ", 1)[1]
                    self.assertTrue(calls.strip().endswith(arguments), calls)
                    self.assertEqual(len(calls.splitlines()), 1)
                    if status == 0:
                        self.assertEqual(result.returncode, 0, result.stderr)
                        self.assertEqual(result.stderr.count("[OK] " + label), 1)
                        self.assertIn(f"fixture diagnostic\n\n[OK] {label}", result.stderr)
                    else:
                        self.assertNotEqual(result.returncode, 0)
                        self.assertIn(f"[FAIL] {label} (exit {status})", result.stderr)
                        self.assertIn(f"fixture diagnostic\n\n[FAIL] {label}", result.stderr)
                        self.assertIn(f"Error {status}", result.stderr)
                        self.assertNotIn("[OK]", result.stderr)
                    self.assertNotIn("\033", result.stderr)

    def test_make_color_controls(self):
        for target, label, _ in CHECKS:
            with self.subTest(target=target):
                result, _ = self.run_check(target, color="always")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn("\033[34m[RUN]\033[0m " + label, result.stderr)
                self.assertIn("\033[32m[OK]\033[0m " + label, result.stderr)
                result, _ = self.run_check(target, color="always", no_color="1")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertNotIn("\033", result.stderr)

    def test_documentation_checker_error_label_respects_color_controls(self):
        for color, no_color, expected in (
            ("always", "", "\033[31mERROR:\033[0m"),
            ("never", "", "ERROR:"),
            ("always", "1", "ERROR:"),
            ("auto", "", "ERROR:"),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = subprocess.run(
                    ["python3", str(ROOT / "scripts/check-skills.py"),
                     "--root", str(self.root)],
                    env=dict(os.environ, COLOR=color, NO_COLOR=no_color),
                    text=True, capture_output=True, check=False,
                )
                self.assertEqual(result.returncode, 1)
                self.assertIn(expected, result.stderr)
                if expected == "ERROR:":
                    self.assertNotIn("\033", result.stderr)


if __name__ == "__main__":
    unittest.main()
