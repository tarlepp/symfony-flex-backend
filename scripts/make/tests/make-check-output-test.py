#!/usr/bin/env python3
"""Test how Make check targets report output and command failures offline."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make test-command-output
#   To run only this suite from the repository root inside the container:
#     python3 scripts/make/tests/make-check-output-test.py
#
# Requirements:
#   Python 3, Bash, Make, and Linux pseudo-terminal support.
#
# Details:
#   Tests run copied Make recipes and output helpers in a temporary directory.
#   Stubs replace the underlying check commands; a separate case runs the real
#   documentation checker against the fixture root to verify error colors.
#
# Callers:
#   Scripts:
#     - `scripts/tests/command-output-contract-test.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py`.

import os
from pathlib import Path
import pty
import re
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[3]
CHECKS = (
    ("check-skills", "Skill metadata and documentation links", "python3 scripts/checks/check-skills.py"),
    ("lint-workflows", "GitHub workflows and local action metadata", "bash scripts/checks/lint-workflows.sh"),
    ("check-requirements", "Application runtime requirements", "php ./vendor/bin/requirements-checker"),
    ("test-make-routing", "Make container routing (offline mocked tests)",
     "bash scripts/make/tests/make-container-test.sh"),
    ("docker-image-verify-pins", "Repository Dockerfile pins (live registry verification)",
     "./scripts/docker/docker-images.sh verify"),
)


class MakeCheckOutputTest(unittest.TestCase):
    def setUp(self):
        """Copy the Make and output helpers, replacing the commands they wrap."""
        self.temporary = tempfile.TemporaryDirectory(prefix="make-check-output-")

        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)

        for directory in (
            "scripts/make",
            "scripts/make/tests",
            "scripts/output",
            "scripts/checks",
            "scripts/docker",
            "bin",
        ):
            (self.root / directory).mkdir(parents=True)

        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")

        for name in (
            "output/command-output.sh",
            "output/native-output.sh",
            "output/native-output.py",
            "make/make.sh",
        ):
            shutil.copyfile(ROOT / "scripts" / name, self.root / "scripts" / name)
        # Let the Python output filter run for real even though checks are stubs.
        fixture = (
            "#!/usr/bin/env bash\n"
            "if [[ \"$0\" == */python3 && ( \"${1:-}\" == scripts/output/native-output.py || \"${1:-}\" == */scripts/output/native-output.py ) ]]; then exec /usr/bin/python3 \"$@\"; fi\n"
            "printf '%s\\n' \"$0 $*\" >> \"$CALLS\"\n"
            "if [[ \"${1:-}\" == ./vendor/bin/requirements-checker && \"${CHECK_XDEBUG:-0}\" = 1 && \"${XDEBUG_MODE:-}\" != off ]]; then\n"
            "  echo 'Xdebug: [Step Debug] unexpected connection attempt' >&2; exit 8\n"
            "fi\n"
            "if [[ \"${1:-}\" == ./vendor/bin/requirements-checker && \"${CHECKER_COLOR:-0}\" = 1 ]]; then\n"
            "  if [ \"${TERM_PROGRAM:-}\" = Hyper ] || [ \"${EMIT_ANSI:-0}\" = 1 ]; then\n"
            "    printf '\\033[34mSymfony Requirements Checker\\033[0m\\n\\033[32m[OK]\\033[0m\\n'\n"
            "  else printf 'Symfony Requirements Checker\\n[OK]\\n'; fi\n"
            "  printf 'checker diagnostic\\n' >&2\n"
            "  exit \"$FIXTURE_STATUS\"\n"
            "fi\n"
            "printf 'fixture stdout\\n'\n"
            "printf 'fixture diagnostic\\n' >&2\n"
            "exit \"$FIXTURE_STATUS\"\n"
        )

        for name in (
            "bin/python3", "bin/php", "scripts/checks/lint-workflows.sh",
            "scripts/make/tests/make-container-test.sh", "scripts/docker/docker-images.sh",
        ):
            file = self.root / name

            file.write_text(fixture)
            file.chmod(0o755)

        docker = self.root / "bin/docker"

        docker.write_text("#!/usr/bin/env bash\nexit 0\n")
        docker.chmod(0o755)

    def run_check(self, target, status=0, color="never", no_color="", run_target=""):
        """Return the Make result and the fresh command log for one invocation."""
        calls = self.root / "calls"

        calls.write_text("")
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(calls), FIXTURE_STATUS=str(status), NO_COLOR=no_color,
            OUTPUT_RUN_TARGET=run_target,
            OUTPUT_RUN_TARGET_ACTIVE="",
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

    def test_requirements_native_color_and_failures(self):
        calls = self.root / "calls"
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}", CALLS=str(calls),
            CHECKER_COLOR="1", FIXTURE_STATUS="0", TERM="xterm", NO_COLOR="", EMIT_ANSI="0",
        )

        environment.pop("NATIVE_COLOR", None)
        command = ["make", "--no-print-directory", "APPLICATION_CONFIG=unused",
                   "INSIDE_DOCKER_CONTAINER=1", "check-requirements"]
        plain = "Symfony Requirements Checker\n[OK]\n"

        for color, no_color, emit_ansi, expected in (
            ("always", "", "0", True), ("never", "", "1", False),
            ("always", "1", "1", False), ("auto", "", "0", False),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = subprocess.run(
                    command, cwd=self.root, env=dict(environment, COLOR=color, NO_COLOR=no_color, EMIT_ANSI=emit_ansi),
                    capture_output=True, text=True, check=False,
                )

                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[34mSymfony Requirements Checker\x1b[0m" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain)
                self.assertIn("checker diagnostic", result.stderr)

        # Auto color needs a terminal on stdout; keep stderr separate.
        for term, no_color, expected in (("xterm", "", True), ("dumb", "", False), ("xterm", "1", False)):
            with self.subTest(term=term, no_color=no_color):
                master, slave = pty.openpty()

                try:
                    result = subprocess.run(
                        ["bash", "scripts/make/make.sh", *command[1:]], cwd=self.root,
                        env=dict(environment, COLOR="auto", NO_COLOR=no_color, TERM=term),
                        stdout=slave, stderr=subprocess.PIPE, check=False,
                    )

                    os.close(slave)
                    slave = -1
                    output = os.read(master, 65536).decode().replace("\r\n", "\n")
                finally:
                    os.close(master)

                    if slave != -1:
                        os.close(slave)

                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[34mSymfony Requirements Checker\x1b[0m" in output, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", output), plain)

        result = subprocess.run(
            command, cwd=self.root, env=dict(environment, COLOR="always", FIXTURE_STATUS="7"),
            capture_output=True, text=True, check=False,
        )

        self.assertNotEqual(result.returncode, 0)
        self.assertIn("checker diagnostic", result.stderr)
        self.assertIn("[FAIL]", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_requirements_disables_xdebug_without_hiding_diagnostics(self):
        calls = self.root / "calls"
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}", CALLS=str(calls),
            XDEBUG_MODE="debug", CHECK_XDEBUG="1", CHECKER_COLOR="1", NO_COLOR="", TERM="xterm",
        )

        for color in ("always", "never"):
            for status in ("0", "7"):
                with self.subTest(color=color, status=status):
                    result = subprocess.run(
                        ["make", "--no-print-directory", "APPLICATION_CONFIG=unused",
                         "INSIDE_DOCKER_CONTAINER=1", f"COLOR={color}", "check-requirements"],
                        cwd=self.root, env=dict(environment, FIXTURE_STATUS=status),
                        capture_output=True, text=True, check=False,
                    )
                    stderr = re.sub(r"\x1b\[[0-9;]*m", "", result.stderr)

                    self.assertEqual(result.returncode == 0, status == "0", stderr)
                    self.assertNotIn("Xdebug:", stderr)
                    self.assertIn("checker diagnostic", stderr)

                    if status == "0":
                        self.assertIn("[OK] Application runtime requirements", stderr)
                    else:
                        self.assertIn("[FAIL] Application runtime requirements (exit 7)", stderr)
                        self.assertNotIn("[OK] Application runtime requirements", stderr)

    def test_pre_pr_requirements_label_includes_make_command_and_indents_output(self):
        result, _ = self.run_check(
            "check-requirements", run_target="check-requirements"
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn(
            "[RUN] Application runtime requirements (make check-requirements)\n",
            result.stderr,
        )
        self.assertEqual(result.stdout, "  fixture stdout\n")
        self.assertIn("  fixture diagnostic\n", result.stderr)
        self.assertNotIn("[OK] Application runtime requirements\n", result.stderr)
        self.assertIn(
            "[OK] Application runtime requirements (make check-requirements)\n",
            result.stderr,
        )

        failed, _ = self.run_check(
            "check-requirements", status=7, run_target="check-requirements"
        )
        self.assertNotEqual(failed.returncode, 0)
        self.assertIn("  fixture diagnostic\n", failed.stderr)
        self.assertIn(
            "[FAIL] Application runtime requirements (make check-requirements) (exit 7)\n",
            failed.stderr,
        )

    def test_documentation_checker_error_label_respects_color_controls(self):
        for color, no_color, expected in (
            ("always", "", "\033[31m[FAIL]\033[0m"),
            ("never", "", "[FAIL]"),
            ("always", "1", "[FAIL]"),
            ("auto", "", "[FAIL]"),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = subprocess.run(
                    ["python3", str(ROOT / "scripts/checks/check-skills.py"),
                     "--root", str(self.root)],
                    env=dict(os.environ, COLOR=color, NO_COLOR=no_color),
                    text=True, capture_output=True, check=False,
                )

                self.assertEqual(result.returncode, 1)
                self.assertIn(expected, result.stderr)

                if expected == "[FAIL]":
                    self.assertNotIn("\033", result.stderr)


if __name__ == "__main__":
    unittest.main()
