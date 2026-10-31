#!/usr/bin/env python3
"""Check the Make launcher's filtering against real GNU Make invocations."""

import os
from pathlib import Path
import re
import select
import shutil
import signal
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
LAUNCHER = ROOT / "scripts/make.sh"
OUTPUT = ROOT / "scripts/command-output.sh"


class MakeLauncherTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-launcher-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.makefile = self.root / "fixture.mk"
        self.environment = dict(os.environ, COLOR="never", NO_COLOR="")
        self.environment.pop("MAKEFLAGS", None)
        self.environment.pop("MFLAGS", None)

    def run_make(self, recipe, *arguments):
        self.makefile.write_text(recipe)
        return subprocess.run(
            ["bash", str(LAUNCHER), "--no-print-directory", "-f",
             str(self.makefile), *arguments],
            cwd=self.root, env=self.environment, text=True,
            capture_output=True, check=False,
        )

    def test_success_and_failure_keep_native_diagnostics(self):
        for status in (0, 1, 2, 7, 127):
            with self.subTest(status=status):
                result = self.run_make(
                    f'all:\n\t@bash "{OUTPUT}" example bash -c '
                    f"'printf payload; printf \"diagnostic\\\\n\" >&2; exit {status}'\n"
                )
                self.assertEqual(result.returncode, 0 if status == 0 else 2)
                self.assertEqual(result.stdout, "payload")
                self.assertIn("diagnostic\n", result.stderr)
                self.assertNotIn("make: ***", result.stderr)
                label = "[OK] example" if status == 0 else f"[FAIL] example (exit {status})"
                self.assertIn("\n\n" + label, result.stderr)

    def test_recursive_make_error_lines_are_filtered(self):
        result = self.run_make(
            'all:\n\t@$(MAKE) --no-print-directory -f "$(lastword $(MAKEFILE_LIST))" child\n'
            'child:\n\t@printf "[FAIL] child\\n" >&2; exit 7\n'
        )
        self.assertEqual(result.returncode, 2)
        self.assertEqual(result.stderr, "[FAIL] child\n")

    def test_make_setup_errors_and_unrelated_diagnostics_remain_visible(self):
        result = self.run_make("all:\n\t@true\n", "missing-target")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("No rule to make target", result.stderr)
        result = self.run_make("invalid make syntax\n")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("missing separator", result.stderr)
        result = self.run_make('all:\n\t@printf "ERROR: native diagnostic" >&2\n')
        self.assertEqual(result.returncode, 0)
        self.assertEqual(result.stderr, "ERROR: native diagnostic")

    def test_arguments_and_color_controls_are_preserved(self):
        result = self.run_make(
            'all:\n\t@printf "%s" "$(MESSAGE)"\n'
            '\t@printf "COLOR=%s NO_COLOR=%s\\n" "$$COLOR" "$$NO_COLOR" >&2\n',
            "MESSAGE=two words", "COLOR=always", "NO_COLOR=1",
        )
        self.assertEqual(result.returncode, 0)
        self.assertEqual(result.stdout, "two words")
        self.assertEqual(result.stderr, "COLOR=always NO_COLOR=1\n")

    def test_opt_in_shell_function_filters_only_in_repository_root(self):
        scripts = self.root / "scripts"
        scripts.mkdir()
        for name in ("make.sh", "make-shell.sh"):
            shutil.copyfile(ROOT / "scripts" / name, scripts / name)
        self.makefile.write_text('all:\n\t@printf "[FAIL] fixture\\n" >&2; exit 7\n')
        for directory, filtered in ((self.root, True), (scripts, False)):
            with self.subTest(directory=directory):
                result = subprocess.run(
                    ["bash", "-c",
                     'source "$1"; cd "$2"; make --no-print-directory -f "$3"',
                     "test", str(scripts / "make-shell.sh"), str(directory),
                     str(self.makefile)],
                    cwd=self.root, env=self.environment, text=True,
                    capture_output=True, check=False,
                )
                self.assertEqual(result.returncode, 2)
                self.assertIn("[FAIL] fixture", result.stderr)
                self.assertEqual(
                    re.search(r"make(?:\[\d+\])?: \*\*\*", result.stderr) is None,
                    filtered,
                )

    def test_interactive_startup_loads_user_config_and_filter(self):
        scripts = self.root / "scripts"
        scripts.mkdir()
        for name in ("make.sh", "make-shell.sh", "bashrc.sh"):
            shutil.copyfile(ROOT / "scripts" / name, scripts / name)
        home = self.root / "home"
        home.mkdir()
        (home / ".bashrc").write_text("export USER_CONFIG_LOADED=yes\n")
        self.makefile.write_text(
            'all:\n\t@printf "user config: %s\\n" "$$USER_CONFIG_LOADED" >&2; '
            'printf "[FAIL] fixture\\n" >&2; exit 7\n'
        )
        result = subprocess.run(
            ["bash", "--rcfile", str(scripts / "bashrc.sh"), "-ic",
             'make --no-print-directory -f fixture.mk'],
            cwd=self.root, env=dict(self.environment, HOME=str(home)),
            text=True, capture_output=True, check=False,
        )
        self.assertEqual(result.returncode, 2)
        self.assertIn("user config: yes", result.stderr)
        self.assertIn("[FAIL] fixture", result.stderr)
        self.assertNotIn("make: ***", result.stderr)

    def test_stderr_is_streamed_before_completion(self):
        self.makefile.write_text('all:\n\t@printf "ready\\n" >&2; sleep 30\n')
        process = subprocess.Popen(
            ["bash", str(LAUNCHER), "--no-print-directory", "-f", str(self.makefile)],
            cwd=self.root, env=self.environment, stdout=subprocess.PIPE,
            stderr=subprocess.PIPE, start_new_session=True,
        )
        try:
            self.assertTrue(select.select([process.stderr], [], [], 5)[0])
            self.assertEqual(process.stderr.readline(), b"ready\n")
            os.killpg(process.pid, signal.SIGINT)
            process.communicate(timeout=5)
            self.assertNotEqual(process.returncode, 0)
        finally:
            if process.poll() is None:
                os.killpg(process.pid, signal.SIGKILL)
                process.communicate()


if __name__ == "__main__":
    unittest.main()
