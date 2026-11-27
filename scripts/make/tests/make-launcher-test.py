#!/usr/bin/env python3
"""Test Make launcher diagnostics, streaming, shell hooks, and forwarding."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make test-command-output
#   To run only this suite from the repository root inside the container:
#     python3 scripts/make/tests/make-launcher-test.py
#
# Requirements:
#   Python 3, GNU Make, Bash, Fish, and Linux pseudo-terminal support.
#   The live Bash completion test requires ble.sh and is skipped if it is absent.
#
# Details:
#   The suite runs real Make against temporary recipes or a copied Makefile.
#   Forwarding cases stub Docker so both launcher layers run locally without
#   starting another container.
#
# Callers:
#   Scripts:
#     - `scripts/tests/command-output-contract-test.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py`.

import os
from pathlib import Path
import pty
import re
import select
import shutil
import signal
import subprocess
import sys
import tempfile
import termios
import time
import unittest


ROOT = Path(__file__).resolve().parents[3]
LAUNCHER = ROOT / "scripts/make/make.sh"
OUTPUT = ROOT / "scripts/output/command-output.sh"


class MakeLauncherTest(unittest.TestCase):
    def test_completion_inventory(self):
        result = subprocess.run(
            ["bash", "scripts/make/make-help.sh", "--targets"],
            cwd=ROOT, capture_output=True, text=True, check=True,
        )
        entries = dict(line.split("\t", 1) for line in result.stdout.splitlines())

        self.assertIn("ecs", entries)
        self.assertTrue(entries["ecs-fix"].startswith("[OVERWRITE] "))
        self.assertNotIn("help", entries)

    @unittest.skipUnless(Path("/usr/local/share/blesh/ble.sh").is_file(), "Requires rebuilt dev image")
    def test_bash_displays_make_matches_without_tab(self):
        pid, terminal = pty.fork()

        if pid == 0:
            termios.tcsetwinsize(0, (24, 80))
            os.chdir(ROOT)
            os.execvpe("bash", ["bash", "--rcfile", "scripts/make/bashrc.sh", "-i"],
                       dict(self.environment, TERM="xterm-256color", INPUTRC="/dev/null",
                            HISTFILE="/dev/null", XDG_CACHE_HOME=str(self.root),
                            SYMFONY_BASH_LIVE_COMPLETION="1"))

        def expect(pattern, timeout=10):
            output = b""
            deadline = time.monotonic() + timeout

            while time.monotonic() < deadline:
                if not select.select([terminal], [], [], max(0, deadline - time.monotonic()))[0]:
                    break

                output += os.read(terminal, 8192)
                plain = re.sub(rb"\x1b(?:\[[0-9;]*m|\(B)", b"", output)

                if re.search(pattern, plain):
                    return

            self.fail(f"Expected {pattern!r} in terminal output: {output!r}")

        try:
            # A command round trip avoids depending on the user's prompt format.
            os.write(terminal, b"printf 'ready:%s\\n' MENU\n")
            expect(rb"ready:MENU\r?\n")
            os.write(terminal, b"make ec")
            expect(rb"ecs +ecs-fix")
        finally:
            try:
                os.killpg(pid, signal.SIGKILL)
            except ProcessLookupError:
                pass

            os.close(terminal)
            os.waitpid(pid, 0)

    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-launcher-")

        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.makefile = self.root / "fixture.mk"
        self.environment = dict(os.environ, COLOR="never", NO_COLOR="")
        # Do not let an outer Make invocation change the fixture's options.
        self.environment.pop("MAKEFLAGS", None)
        self.environment.pop("MFLAGS", None)

    def run_make(self, recipe, *arguments):
        """Run the real launcher against a scratch Makefile and capture both streams."""
        self.makefile.write_text(recipe)

        return subprocess.run(
            ["bash", str(LAUNCHER), "--no-print-directory", "-f",
             str(self.makefile), *arguments],
            cwd=self.root, env=self.environment, text=True,
            capture_output=True, check=False,
        )

    def copy_scripts(self, *paths):
        for path in paths:
            destination = self.root / "scripts" / path

            destination.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(ROOT / "scripts" / path, destination)

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

    def test_progress_policy_is_resolved_before_stderr_filtering(self):
        recipe = 'all:\n\t@printf "PROGRESS=%s\\n" "$$PROGRESS" >&2\n'

        self.environment.pop("PROGRESS", None)
        self.environment.pop("CI", None)

        for overrides, expected in (
            ({}, "always"), ({"CI": "true"}, "never"), ({"NO_COLOR": "1"}, "never"),
            ({"TERM": "dumb"}, "never"), ({"PROGRESS": "never"}, "never"),
        ):
            with self.subTest(overrides=overrides):
                self.makefile.write_text(recipe)
                master, slave = pty.openpty()
                process = subprocess.Popen(
                    ["bash", str(LAUNCHER), "--no-print-directory", "-f", str(self.makefile)],
                    cwd=self.root, env={**self.environment, "TERM": "xterm", **overrides},
                    stdout=subprocess.PIPE, stderr=slave,
                )

                os.close(slave)
                output = b""

                while True:
                    try:
                        chunk = os.read(master, 4096)
                    except OSError:
                        break

                    if not chunk:
                        break

                    output += chunk

                os.close(master)
                process.communicate(timeout=10)
                self.assertEqual(process.returncode, 0)
                self.assertEqual(output, f"PROGRESS={expected}\r\n".encode())

        result = self.run_make(recipe)

        self.assertEqual(result.stderr, "PROGRESS=never\n")
        result = self.run_make(recipe, "PROGRESS=always")

        self.assertEqual(result.stderr, "PROGRESS=always\n")

    def test_shared_terminal_flag_is_recorded_before_stderr_filtering(self):
        self.makefile.write_text('all:\n\t@printf "shared=%s\\n" "$$OUTPUT_PROGRESS_SHARED" >&2\n')
        self.environment.pop("OUTPUT_PROGRESS_SHARED", None)

        for stdout_terminal, inherited, expected in (
            (True, None, "1"), (False, None, "0"), (False, "1", "1"), (True, "0", "0"),
        ):
            with self.subTest(stdout_terminal=stdout_terminal, inherited=inherited):
                environment = dict(self.environment, TERM="xterm")

                if inherited is not None:
                    environment["OUTPUT_PROGRESS_SHARED"] = inherited

                master, slave = pty.openpty()
                process = subprocess.Popen(
                    ["bash", str(LAUNCHER), "--no-print-directory", "-f", str(self.makefile)],
                    cwd=self.root, env=environment, stderr=slave,
                    stdout=slave if stdout_terminal else subprocess.PIPE,
                )

                os.close(slave)
                output = b""

                while True:
                    try:
                        chunk = os.read(master, 4096)
                    except OSError:
                        break

                    if not chunk:
                        break

                    output += chunk

                os.close(master)
                process.communicate(timeout=10)
                self.assertEqual(process.returncode, 0)
                self.assertEqual(output, f"shared={expected}\r\n".encode())

    def test_opt_in_shell_function_filters_only_in_repository_root(self):
        scripts = self.root / "scripts"

        self.copy_scripts("make/make.sh", "make/make-shell.sh")
        self.makefile.write_text('all:\n\t@printf "[FAIL] fixture\\n" >&2; exit 7\n')

        for directory, filtered in ((self.root, True), (scripts, False)):
            with self.subTest(directory=directory):
                result = subprocess.run(
                    ["bash", "-c",
                     'source "$1"; cd "$2"; make --no-print-directory -f "$3"',
                     "test", str(scripts / "make/make-shell.sh"), str(directory),
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

        self.copy_scripts("make/make.sh", "make/make-shell.sh", "make/bashrc.sh")
        home = self.root / "home"

        home.mkdir()
        (home / ".bashrc").write_text("export USER_CONFIG_LOADED=yes\n")
        self.makefile.write_text(
            'all:\n\t@printf "user config: %s\\n" "$$USER_CONFIG_LOADED" >&2; '
            'printf "[FAIL] fixture\\n" >&2; exit 7\n'
        )
        result = subprocess.run(
            ["bash", "--rcfile", str(scripts / "make/bashrc.sh"), "-ic",
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

        self.copy_scripts("make/make.sh", "make/make-shell.fish")
        self.makefile.write_text(
            'all:\n\t@printf "%s\\n" "$(MESSAGE)"\n'
            '\t@printf "[FAIL] fixture\\n" >&2; exit 7\n'
        )

        for directory, filtered in ((self.root, True), (scripts, False)):
            with self.subTest(directory=directory):
                result = subprocess.run(
                    ["fish", "--no-config", "-c",
                     'source $argv[1]; cd $argv[2]; make --no-print-directory -f $argv[3] "MESSAGE=two words"',
                     str(scripts / "make/make-shell.fish"), str(directory), str(self.makefile)],
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

        self.copy_scripts("make/make.sh", "make/make-shell.fish")
        self.makefile.write_text(
            'all:\n\t@printf "user config: %s\\n" "$$USER_CONFIG_LOADED" >&2; '
            'printf "[FAIL] fixture\\n" >&2; exit 7\n'
        )
        result = subprocess.run(
            ["fish", "--init-command", f"source {scripts / 'make/make-shell.fish'}",
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
        bin_path = self.root / "bin"

        bin_path.mkdir()
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        self.copy_scripts(
            "make/make.sh", "make/compose-context.sh",
            "output/command-output.sh", "output/native-output.sh", "output/native-output.py",
        )
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
                ["bash", str(scripts / "make/make.sh"), "--no-print-directory", "APPLICATION_CONFIG=fixture",
                 "INSIDE_DOCKER_CONTAINER=0", "check-requirements"],
                cwd=self.root, env=environment, capture_output=True, text=True, check=False,
            )

            self.assertEqual(result.returncode, 2, result.stderr)
            self.assertEqual(result.stdout, "payload\n")
            self.assertIn("native failure", result.stderr)
            self.assertIn("[FAIL] Application runtime requirements (exit 7)", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            self.assertNotRegex(result.stderr, r"make(?:\[\d+\])?: \*\*\*")
            self.assertIn("php bash scripts/make/make.sh check-requirements", calls.read_text())

            if workflow:
                self.assertIn("-f docker/ci-compose.yaml", calls.read_text())

            for status in ("php|exited|", "nginx|running|"):
                calls.write_text("")
                result = subprocess.run(
                    ["bash", str(scripts / "make/make.sh"), "--no-print-directory",
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
        bin_path = self.root / "bin"

        bin_path.mkdir()
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        self.copy_scripts(
            "make/make.sh", "make/compose-context.sh", "make/make-help.sh",
            "output/command-output.sh", "output/report-output.sh",
        )
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
                            "bash", str(scripts / "make/make.sh"), "--no-print-directory",
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
                        self.assertNotIn("php bash scripts/make/make.sh help", invocations)
                        self.assertEqual("-f docker/ci-compose.yaml" in invocations, bool(workflow))

    def test_every_registered_target_filters_recipe_errors_and_remains_nonzero(self):
        targets = re.findall(r"^\$\(call REGISTER_TARGET,([^,]+),",
                             (ROOT / "Makefile").read_text(), re.M)

        self.assertTrue(targets)
        recipe = ".PHONY: " + " ".join(targets) + "\n" + "".join(
            f'{target}:\n\t@printf "[FAIL] {target}: native diagnostic\\\\n" >&2; exit 64\n'
            for target in targets
        )

        for target in targets:
            with self.subTest(target=target):
                result = self.run_make(recipe, target)

                self.assertEqual(result.returncode, 2)
                self.assertEqual(result.stdout, "")
                self.assertEqual(result.stderr, f"[FAIL] {target}: native diagnostic\n")

        result = self.run_make(recipe, "--keep-going", *targets)

        self.assertEqual(result.returncode, 2)
        self.assertEqual(result.stderr.count("[FAIL]"), len(targets))
        self.assertNotRegex(result.stderr, r"make(?:\[\d+\])?: \*\*\* \[")

    def test_default_development_shell_configs_load_repository_filter(self):
        self.copy_scripts("make/make.sh", "make/make-shell.sh", "make/make-shell.fish")
        self.makefile.write_text('all:\n\t@printf "[FAIL] native diagnostic\\n" >&2; exit 64\n')
        home = self.root / "home"
        fish_config = home / ".config/fish"

        fish_config.mkdir(parents=True)
        bash_hook = self.root / "make-hook.sh"

        bash_hook.write_text((ROOT / "docker/bash/make.sh").read_text().replace("/app/", str(self.root) + "/"))
        dockerfile = (ROOT / "Dockerfile").read_text()

        self.assertIn("echo 'source /app/docker/bash/make.sh' >> /home/dev/.bashrc", dockerfile)
        (home / ".bashrc").write_text(f'export USER_CONFIG_LOADED=yes\nsource "{bash_hook}"\n')
        fish_content = (ROOT / "docker/fish/config.fish").read_text()
        # Existing completions/thefuck are unrelated external startup dependencies.
        (fish_config / "config.fish").write_text(
            "set -gx USER_CONFIG_LOADED yes\n" +
            ("if test -f" + fish_content.split("if test -f", 1)[1])
            .replace("/app/", str(self.root) + "/")
        )

        for command in (
            ["bash", "-ic", 'printf "%s\\n" "$USER_CONFIG_LOADED"; make --no-print-directory -f fixture.mk'],
            ["fish", "-c", 'printf "%s\\n" "$USER_CONFIG_LOADED"; make --no-print-directory -f fixture.mk'],
        ):
            result = subprocess.run(
                command, cwd=self.root,
                env=dict(self.environment, HOME=str(home), XDG_CONFIG_HOME=str(home / ".config")),
                capture_output=True, text=True, check=False,
            )

            self.assertEqual(result.returncode, 2, result.stderr)
            self.assertEqual(result.stdout, "yes\n")
            self.assertIn("[FAIL] native diagnostic", result.stderr)
            self.assertNotRegex(result.stderr, r"make(?:\[\d+\])?: \*\*\*")

    def test_partial_stderr_lines_are_streamed_before_completion(self):
        # Keep the recipe alive after writing so the read proves output is streamed.
        self.makefile.write_text('all:\n\t@printf "Answer: " >&2; sleep 30\n')
        process = subprocess.Popen(
            ["bash", str(LAUNCHER), "--no-print-directory", "-f", str(self.makefile)],
            cwd=self.root, env=self.environment, stdout=subprocess.PIPE,
            stderr=subprocess.PIPE, start_new_session=True,
        )

        try:
            assert process.stderr is not None
            self.assertTrue(select.select([process.stderr], [], [], 5)[0])
            self.assertEqual(os.read(process.stderr.fileno(), 64), b"Answer: ")
        finally:
            os.killpg(process.pid, signal.SIGKILL)
            process.communicate()

    def test_error_lines_split_by_pauses_are_still_filtered(self):
        result = self.run_make(
            'all:\n\t@{ printf "ma" >&2; sleep 0.2; printf "ke[1]: *** [x] Er" >&2; sleep 0.2; '
            'printf "ror 2\\nmake sure\\n" >&2; printf "Prompt: " >&2; sleep 0.2; '
            'printf "make: *** [y] Error 1\\nrest" >&2; }\n'
        )

        self.assertEqual(result.returncode, 0)
        self.assertEqual(result.stderr, "make sure\nPrompt: make: *** [y] Error 1\nrest")

    def test_stderr_is_streamed_before_completion(self):
        self.makefile.write_text('all:\n\t@printf "ready\\n" >&2; sleep 30\n')

        for ignore_sigint in (False, True):
            with self.subTest(ignore_sigint=ignore_sigint):
                command = [
                    "bash", str(LAUNCHER), "--no-print-directory", "-f", str(self.makefile),
                ]

                if ignore_sigint:
                    command = [
                        sys.executable, "-c",
                        "import os, signal, sys; "
                        "signal.signal(signal.SIGINT, signal.SIG_IGN); "
                        "os.execvp(sys.argv[1], sys.argv[1:])",
                        *command,
                    ]

                process = subprocess.Popen(
                    command, cwd=self.root, env=self.environment, stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE, start_new_session=True,
                )

                try:
                    self.assertTrue(select.select([process.stderr], [], [], 5)[0])
                    self.assertEqual(os.read(process.stderr.fileno(), 64), b"ready\n")
                    self.assertIsNone(process.poll())
                    # Background runners can pass ignored SIGINT through exec.
                    os.killpg(process.pid, signal.SIGTERM)
                    process.communicate(timeout=5)
                    self.assertNotEqual(process.returncode, 0)
                finally:
                    # Descendants may retain the pipes after the launcher exits.
                    try:
                        os.killpg(process.pid, signal.SIGKILL)
                    except ProcessLookupError:
                        pass

                    process.communicate(timeout=5)


if __name__ == "__main__":
    unittest.main()
