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

    def test_fish_function_filters_root_and_preserves_arguments_and_status(self):
        scripts = self.root / "scripts"
        scripts.mkdir()
        for name in ("make.sh", "make-shell.fish"):
            shutil.copyfile(ROOT / "scripts" / name, scripts / name)
        self.makefile.write_text(
            'all:\n\t@printf "%s\\n" "$(MESSAGE)"\n'
            '\t@printf "[FAIL] fixture\\n" >&2; exit 7\n'
        )
        for directory, filtered in ((self.root, True), (scripts, False)):
            with self.subTest(directory=directory):
                result = subprocess.run(
                    ["fish", "--no-config", "-c",
                     'source $argv[1]; cd $argv[2]; make --no-print-directory -f $argv[3] "MESSAGE=two words"',
                     str(scripts / "make-shell.fish"), str(directory), str(self.makefile)],
                    cwd=self.root, env=self.environment, capture_output=True, text=True, check=False,
                )
                self.assertEqual(result.returncode, 2, result.stderr)
                self.assertEqual(result.stdout, "two words\n")
                self.assertIn("[FAIL] fixture", result.stderr)
                self.assertEqual(
                    re.search(r"make(?:\[\d+\])?: \*\*\*", result.stderr) is None, filtered,
                )

    def test_fish_init_command_preserves_user_config_and_filter(self):
        home = self.root / "home"
        config = home / ".config/fish"
        config.mkdir(parents=True)
        (config / "config.fish").write_text("set -gx USER_CONFIG_LOADED yes\n")
        scripts = self.root / "scripts"
        scripts.mkdir()
        for name in ("make.sh", "make-shell.fish"):
            shutil.copyfile(ROOT / "scripts" / name, scripts / name)
        self.makefile.write_text(
            'all:\n\t@printf "user config: %s\\n" "$$USER_CONFIG_LOADED" >&2; '
            'printf "[FAIL] fixture\\n" >&2; exit 7\n'
        )
        result = subprocess.run(
            ["fish", "--init-command", f"source {scripts / 'make-shell.fish'}",
             "-c", "make --no-print-directory -f fixture.mk"],
            cwd=self.root, env=dict(self.environment, HOME=str(home), XDG_CONFIG_HOME=str(home / ".config")),
            capture_output=True, text=True, check=False,
        )
        self.assertEqual(result.returncode, 2, result.stderr)
        self.assertIn("user config: yes", result.stderr)
        self.assertIn("[FAIL] fixture", result.stderr)
        self.assertNotRegex(result.stderr, r"make(?:\[\d+\])?: \*\*\*")

    def test_host_and_ci_launchers_filter_both_sides_of_forwarding(self):
        scripts = self.root / "scripts"
        scripts.mkdir()
        bin_path = self.root / "bin"
        bin_path.mkdir()
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        for name in ("make.sh", "command-output.sh", "compose-context.sh"):
            shutil.copyfile(ROOT / "scripts" / name, scripts / name)
        docker = bin_path / "docker"
        docker.write_text(
            '#!/bin/bash\nprintf "%s\\n" "$*" >> "$DOCKER_CALLS"\n'
            'if [[ "$*" == *" ps --all --format "* ]]; then printf "%s\\n" "${SERVICE_STATUS-php|running|}"; exit 0; fi\n'
            'if [[ "$*" == *" exec -T "* ]]; then\n'
            '  while [ "$1" != php ]; do shift; done\n'
            '  shift\n'
            '  exec "$@" INSIDE_DOCKER_CONTAINER=1\n'
            'fi\n'
        )
        docker.chmod(0o755)
        php = bin_path / "php"
        php.write_text('#!/bin/bash\nprintf "payload\\n"; printf "native failure\\n" >&2; exit 7\n')
        php.chmod(0o755)
        for workflow in ("", "CI"):
            calls = self.root / "docker-calls"
            calls.write_text("")
            environment = dict(
                self.environment, PATH=f"{bin_path}:{os.environ['PATH']}",
                DOCKER_CALLS=str(calls), GITHUB_WORKFLOW=workflow,
            )
            if not workflow:
                environment.pop("GITHUB_WORKFLOW")
            result = subprocess.run(
                ["bash", str(scripts / "make.sh"), "--no-print-directory", "APPLICATION_CONFIG=fixture",
                 "INSIDE_DOCKER_CONTAINER=0", "check-requirements"],
                cwd=self.root, env=environment, capture_output=True, text=True, check=False,
            )
            self.assertEqual(result.returncode, 2, result.stderr)
            self.assertEqual(result.stdout, "payload\n")
            self.assertIn("native failure", result.stderr)
            self.assertIn("[FAIL] Application runtime requirements (exit 7)", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            self.assertNotRegex(result.stderr, r"make(?:\[\d+\])?: \*\*\*")
            self.assertIn("php bash scripts/make.sh check-requirements", calls.read_text())
            if workflow:
                self.assertIn("-f docker/ci-compose.yaml", calls.read_text())
            for status in ("php|exited|", "nginx|running|"):
                calls.write_text("")
                result = subprocess.run(
                    ["bash", str(scripts / "make.sh"), "--no-print-directory",
                     "APPLICATION_CONFIG=fixture", "INSIDE_DOCKER_CONTAINER=0", "check-requirements"],
                    cwd=self.root, env=dict(environment, SERVICE_STATUS=status),
                    capture_output=True, text=True, check=False,
                )
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("[FAIL] PHP Compose service", result.stderr)
                self.assertNotIn(" exec -T ", calls.read_text())
                self.assertNotIn("[OK]", result.stderr)

    def test_forwarded_report_contracts_with_collisions_colors_and_multiple_goals(self):
        scripts = self.root / "scripts"
        scripts.mkdir()
        bin_path = self.root / "bin"
        bin_path.mkdir()
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        for name in ("make.sh", "command-output.sh", "compose-context.sh",
                     "report-output.sh", "make-help.sh"):
            shutil.copyfile(ROOT / "scripts" / name, scripts / name)
        docker = bin_path / "docker"
        docker.write_text(
            '#!/bin/bash\nprintf "%s\\n" "$*" >> "$DOCKER_CALLS"\n'
            'if [[ "$*" == *" ps --all --format "* ]]; then printf "php|running|\\n"; exit 0; fi\n'
            'if [[ "$*" == *" exec -T "* ]]; then\n'
            '  while [ "$1" != php ]; do shift; done\n'
            '  shift\n'
            '  exec "$@" INSIDE_DOCKER_CONTAINER=1\n'
            'fi\n'
        )
        docker.chmod(0o755)
        php = bin_path / "php"
        php.write_text(
            '#!/bin/bash\n'
            'if [[ "$*" == *--log-json=* ]]; then\n'
            '  if [ "${OMIT_REPORT:-0}" != 1 ]; then printf "{}\\n" > build/phploc.json; fi\n'
            '  printf \'{"native":"report"}\\n\'\n'
            'elif [[ "$*" == *php-coveralls* ]]; then\n'
            '  printf "native client: no upload in fixture\\n"\n'
            'fi\n'
        )
        php.chmod(0o755)
        # These command-name files must never suppress routing or raw recipes.
        for name in ("phploc", "report-coveralls", "help"):
            (self.root / name).touch()
        calls = self.root / "docker-calls"
        for workflow in ("", "CI"):
            for color, no_color, colored in (
                ("always", "", True), ("never", "", False), ("always", "1", False),
            ):
                for omitted, keep_going in ((False, False), (True, False), (True, True)):
                    with self.subTest(workflow=workflow, color=color, no_color=no_color,
                                      omitted=omitted, keep_going=keep_going):
                        calls.write_text("")
                        report = self.root / "build/phploc.json"
                        if report.exists():
                            report.unlink()
                        environment = dict(
                            self.environment, PATH=f"{bin_path}:{os.environ['PATH']}",
                            DOCKER_CALLS=str(calls), COLOR=color, NO_COLOR=no_color,
                            OMIT_REPORT="1" if omitted else "0",
                        )
                        if workflow:
                            environment["GITHUB_WORKFLOW"] = workflow
                        else:
                            environment.pop("GITHUB_WORKFLOW", None)
                        command = [
                            "bash", str(scripts / "make.sh"), "--no-print-directory",
                            "APPLICATION_CONFIG=fixture", "INSIDE_DOCKER_CONTAINER=0",
                            "phploc", "report-coveralls", "help", "GROUP=reports",
                        ]
                        if keep_going:
                            command.append("--keep-going")
                        result = subprocess.run(
                            command, cwd=self.root, env=environment, capture_output=True,
                            text=True, check=False,
                        )
                        self.assertEqual(result.returncode, 2 if omitted else 0, result.stderr)
                        self.assertIn('{"native":"report"}\n', result.stdout)
                        self.assertNotIn("\033", result.stdout.splitlines()[0])
                        self.assertEqual("\033" in result.stderr, colored)
                        self.assertNotRegex(result.stderr, r"make(?:\[\d+\])?: \*\*\*")
                        if omitted:
                            self.assertIn("Required generated report is missing or empty:", result.stderr)
                            self.assertNotIn("[INFO] JSON report:", result.stderr)
                            self.assertNotIn("[OK] Generate PHPLOC report", result.stderr)
                            self.assertNotIn("[OK]\033[0m Generate PHPLOC report", result.stderr)
                        else:
                            self.assertEqual(report.read_text(), "{}\n")
                            self.assertIn("[INFO]", result.stderr)
                        continued = not omitted or keep_going
                        self.assertEqual("native client:" in result.stdout, continued)
                        self.assertEqual("Reports and uploads" in result.stdout, continued)
                        if continued:
                            self.assertIn("Coveralls client completed", result.stderr)
                            self.assertEqual(result.stderr.count("Coveralls client completed"), 1)
                            self.assertNotIn("Linting and static analysis", result.stdout)
                        invocations = calls.read_text()
                        self.assertEqual(invocations.count(" exec -T "), 2 if continued else 1)
                        self.assertNotIn("php bash scripts/make.sh help", invocations)
                        self.assertEqual("-f docker/ci-compose.yaml" in invocations, bool(workflow))

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
