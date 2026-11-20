#!/usr/bin/env python3
"""Test Docker entrypoint startup and Make lifecycle command contracts offline.

Usage:
  From the repository root on the Docker host or in the PHP development container:
    make test-command-output
  To run only this suite from the repository root inside the container:
    python3 scripts/make/tests/make-lifecycle-output-test.py

Requirements:
  Python 3, GNU Make, Bash, awk, and Linux pseudo-terminal support.

Details:
  The suite runs copied entrypoints against command stubs and runs Make against
  a temporary project with a fake Docker executable. Tests check command order,
  arguments, diagnostics, and failures without starting services, pushing
  images, or pruning real Docker resources.

Callers:
  Scripts:
    - `scripts/tests/command-output-contract-test.sh` loads this suite through
      `scripts/tests/run-workflow-tests.py`.
"""

import os
from pathlib import Path
import pty
import re
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[3]


class EntrypointTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="entrypoint-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        (self.root / "bin").mkdir()
        self.bashrc = self.root / "bashrc"
        self.bashrc.write_text("existing shell configuration\n")
        self.calls = self.root / "calls"

        # Rewrite absolute runtime paths only in isolated copies, never the originals.
        for name in ("docker-entrypoint-dev.sh", "docker-entrypoint.sh"):
            content = (ROOT / name).read_text()
            for original, replacement in (
                ("/sbin/ip", str(self.root / "bin/ip")),
                ("/home/dev/.bashrc", str(self.bashrc)),
                ("/shared/app", str(self.root / "shared")),
                ("/app", str(self.root / "app")),
            ):
                content = content.replace(original, replacement)
            (self.root / name).write_text(content)

        mock = """#!/bin/bash
name=${0##*/}
printf '%s' "$name" >> "$CALLS"
printf ' <%s>' "$@" >> "$CALLS"
printf '\\n' >> "$CALLS"
if [ "$name $*" = "${FAIL_CALL:-}" ]; then
    printf 'fixture startup failure\\n' >&2
    exit 7
fi
case "$name" in
    make|composer|console|mkdir|chmod|chown|find|cp)
        if [ "$name $*" = 'console completion bash' ]; then
            printf 'fixture completion\\n'
        fi
        if [ "$name" = composer ]; then
            printf 'environment <%s> <%s> <%s>\\n' \
                "${DOCKER_IP:-}" "${XDEBUG_SESSION:-}" "${COMPOSER_MEMORY_LIMIT:-}" >> "$CALLS"
        fi
        ;;
    ip)
        [ "$*" = route ] || exit 64
        printf 'default via 192.0.2.1 dev eth0\\n'
        ;;
    php-fpm|payload)
        printf 'payload pid=%s\\n' "$$"
        exit "${PAYLOAD_EXIT:-0}"
        ;;
    *) printf 'Unexpected startup command: %s\\n' "$name" >&2; exit 64 ;;
esac
"""
        for name in ("make", "composer", "console", "mkdir", "chmod", "chown",
                     "find", "cp", "ip", "php-fpm", "payload"):
            command = self.root / "bin" / name
            command.write_text(mock)
            command.chmod(0o755)
        awk = shutil.which("awk")
        assert awk is not None, "awk is required for this test"
        (self.root / "bin/awk").symlink_to(awk)

    def run_entrypoint(self, script, *arguments, **overrides):
        """Return the exit status, output streams, call log, and original PID."""
        self.calls.write_text("")
        environment = dict(os.environ, PATH=str(self.root / "bin"), CALLS=str(self.calls))
        for name in ("DOCKER_IP", "XDEBUG_SESSION", "COMPOSER_MEMORY_LIMIT", "FAIL_CALL", "PAYLOAD_EXIT"):
            environment.pop(name, None)
        environment.update(overrides)
        process = subprocess.Popen(
            ["/bin/bash", script, *arguments], cwd=self.root, env=environment,
            stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True,
        )
        stdout, stderr = process.communicate(timeout=10)
        return process.returncode, stdout, stderr, self.calls.read_text().splitlines(), process.pid

    def development_calls(self):
        return [
            "make <lint-configuration>",
            "ip <route>",
            "composer <install> <--optimize-autoloader>",
            "environment <192.0.2.1> <PHPSTORM> <-1>",
            "composer <audit> <--abandoned=report>",
            "environment <192.0.2.1> <PHPSTORM> <>",
            "make <generate-jwt-keys>",
            "console <doctrine:database:create> <--no-interaction> <--if-not-exists>",
            "console <doctrine:migrations:migrate> <--no-interaction> <--allow-no-migration> <--all-or-nothing>",
            "console <completion> <bash>",
        ]

    def production_calls(self):
        return [
            "make <lint-configuration>",
            f"mkdir <-p> <{self.root}/app/var>",
            "make <generate-jwt-keys>",
            f"chmod <644> <{self.root}/app/config/jwt/private.pem>",
            "console <doctrine:database:create> <--if-not-exists> <--no-interaction> <--no-ansi>",
            "console <doctrine:migrations:migrate> <--no-interaction> <--no-ansi> <--allow-no-migration>",
            "console <assets:install> <--symlink> <--relative> <--no-interaction> <--no-ansi> <--env> <prod>",
            f"chown <-R> <www-data:www-data> <{self.root}/app/var>",
            f"mkdir <-p> <{self.root}/shared>",
            f"find <{self.root}/shared> <-mindepth> <1> <-delete>",
            f"cp <-a> <{self.root}/app/.> <{self.root}/shared/>",
        ]

    def test_development_order_environment_completion_and_exec(self):
        status, stdout, stderr, calls, pid = self.run_entrypoint(
            "docker-entrypoint-dev.sh", "payload", "argument with spaces", "", PAYLOAD_EXIT="23",
        )
        self.assertEqual(status, 23, stderr)
        self.assertEqual(calls, self.development_calls() + ["payload <argument with spaces> <>"])
        # A matching PID proves the entrypoint replaced itself with the payload.
        self.assertEqual(stdout, f"payload pid={pid}\n")
        self.assertEqual(self.bashrc.read_text(), "existing shell configuration\nfixture completion\n")

    def test_production_order_permissions_sync_and_exec(self):
        status, stdout, stderr, calls, pid = self.run_entrypoint(
            "docker-entrypoint.sh", "php-fpm", "-F", PAYLOAD_EXIT="23",
        )
        self.assertEqual(status, 23, stderr)
        self.assertEqual(calls, self.production_calls() + ["php-fpm <-F>"])
        self.assertEqual(stdout, f"payload pid={pid}\n")
        self.assertEqual(self.bashrc.read_text(), "existing shell configuration\n")

    def test_production_non_fpm_command_skips_setup(self):
        status, stdout, stderr, calls, pid = self.run_entrypoint(
            "docker-entrypoint.sh", "payload", "argument with spaces", "",
        )
        self.assertEqual(status, 0, stderr)
        self.assertEqual(calls, ["payload <argument with spaces> <>"])
        self.assertEqual(stdout, f"payload pid={pid}\n")

    def test_startup_failures_stop_before_later_steps_and_exec(self):
        for script, expected in (
            ("docker-entrypoint-dev.sh", self.development_calls()),
            ("docker-entrypoint.sh", self.production_calls()),
        ):
            for index, call in enumerate(expected):
                if call.startswith(("environment ", "ip ")):
                    continue
                with self.subTest(script=script, call=call):
                    failure = call.replace(" <", " ").replace(">", "")
                    status, _, stderr, calls, _ = self.run_entrypoint(
                        script, "php-fpm", FAIL_CALL=failure,
                    )
                    self.assertEqual(status, 7, stderr)
                    self.assertIn("fixture startup failure", stderr)
                    self.assertEqual(calls, expected[:index + 1])


class LifecycleOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-lifecycle-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        for directory in ("scripts/make", "scripts/docker", "scripts/output"):
            (self.root / directory).mkdir(parents=True)
        (self.root / "bin").mkdir()
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        for path in (
            "output/command-output.sh", "make/make.sh", "docker/docker-maintenance.sh",
            "docker/docker-images.sh", "make/compose-context.sh",
        ):
            shutil.copyfile(ROOT / "scripts" / path, self.root / "scripts" / path)
            (self.root / "scripts" / path).chmod(0o755)
        shutil.copyfile(
            ROOT / "scripts/output/command-progress.py",
            self.root / "scripts/output/command-progress.py",
        )
        (self.root / "Dockerfile").write_text("FROM scratch AS production\n")
        docker = self.root / "bin/docker"
        docker.write_text("""#!/usr/bin/env bash
printf '%s\\n' "$*" >> "$CALLS"
if [ -n "${COLOR_CALLS:-}" ]; then printf '%s\\n' "$COMPOSE_ANSI" >> "$COLOR_CALLS"; fi
if [ "$1" = info ]; then exit "${DAEMON_EXIT:-0}"; fi
if [[ "$*" == *" version" && "$1" = compose ]]; then exit "${COMPOSE_EXIT:-0}"; fi
if [[ "$*" == *" ps --all --format "* ]]; then
    if [ "${STATUS_EXIT:-0}" != 0 ]; then echo 'native status failure' >&2; exit "$STATUS_EXIT"; fi
    if [ -n "${SERVICE_STATUS:-}" ]; then printf '%s\\n' "$SERVICE_STATUS"; exit 0; fi
    if [ "${RUNNING:-0}" != 0 ]; then printf 'php|running|\\n'; fi
    exit 0
fi
if [ "$1" = ps ] && [ "${2:-}" = -f ]; then
    for ((i=0; i<${RUNNING:-0}; i++)); do echo symfony-backend; done
    exit 0
fi
if [ "$1" = ps ] || [ "$1" = images ]; then
    if [ "${FAIL_QUERY:-0}" != 0 ]; then
        echo 'native listing failure' >&2; exit "$FAIL_QUERY"
    fi
    printf '%s' "${RESOURCES:-}"
    exit 0
fi
if [ "$1 $2" = 'buildx version' ]; then exit 0; fi
if [ "$1 $2" = 'buildx inspect' ]; then
    if [ "${3:-}" != --bootstrap ] && [ "${BUILDER_MISSING:-0}" = 1 ]; then exit 1; fi
    printf 'Name:          fixture\\nDriver:        docker\\nPlatforms:\\t linux/amd64, linux/arm64\\n'
    if [ "${3:-}" = --bootstrap ]; then printf 'Name: second\\nPlatforms: ignored\\n'; fi
    if [ "${3:-}" = --bootstrap ] && [ "${DOCKER_EXIT:-0}" != 0 ]; then
        echo 'native builder failure' >&2
    fi
    if [ "${3:-}" = --bootstrap ]; then exit "${DOCKER_EXIT:-0}"; fi
    exit 0
fi
printf 'native progress\\n'
printf 'native Docker diagnostic\\n' >&2
if [ -n "${FAIL_MATCH:-}" ] && [[ "$*" == *"$FAIL_MATCH"* ]]; then exit 7; fi
exit "${DOCKER_EXIT:-0}"
""")
        docker.chmod(0o755)
        self.calls = self.root / "calls"

    def environment(self, **overrides):
        """Reset inherited Make flags and color settings before applying overrides."""
        environment = dict(os.environ)
        for name in ("NATIVE_COLOR", "MAKEFLAGS", "MFLAGS", "MAKELEVEL", "MAKE_TERMOUT", "MAKE_TERMERR"):
            environment.pop(name, None)
        environment.update(
            PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(self.calls), COLOR="never", NO_COLOR="", RUNNING="0",
            DOCKER_EXIT="0", FAIL_QUERY="0", RESOURCES="",
            COMPOSER_CACHE_DIR=str(self.root / "composer-cache"),
        )
        environment.update(overrides)
        return environment

    def run_command(self, command, answer=None, **overrides):
        """Capture both streams and the call log, using a TTY for prompt answers."""
        self.calls.write_text("")
        environment = self.environment(**overrides)
        master = slave = None
        if answer is not None:
            master, slave = pty.openpty()
            os.write(master, answer.encode())
        try:
            result = subprocess.run(
                command, cwd=self.root, env=environment, text=True,
                stdin=slave if slave is not None else subprocess.DEVNULL,
                stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=False, timeout=15,
            )
        finally:
            if master is not None:
                os.close(master)
            if slave is not None:
                os.close(slave)
        return result, self.calls.read_text()

    def run_with_stdout_terminal(self, command, **overrides):
        """Give only stdout a terminal so automatic color can be tested independently."""
        self.calls.write_text("")
        environment = self.environment(COLOR="auto", **overrides)
        master, slave = pty.openpty()
        try:
            process = subprocess.Popen(
                command, cwd=self.root, env=environment, stdin=subprocess.DEVNULL,
                stdout=slave, stderr=subprocess.PIPE, text=True,
            )
            os.close(slave)
            _, stderr = process.communicate(timeout=15)
            stdout = os.read(master, 4096).decode()
        finally:
            os.close(master)
        result = subprocess.CompletedProcess(command, process.returncode, stdout, stderr)
        return result, self.calls.read_text()

    def make(self, target, *arguments, **overrides):
        """Force host-side Make routing even when the suite runs in a container."""
        return self.run_command([
            "bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
            "INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0", target, *arguments,
        ], **overrides)

    def test_compose_color_environment_follows_shared_controls(self):
        color_calls = self.root / "color-calls"
        for color, no_color, expected in (
            ("always", "", "always"), ("never", "", "never"),
            ("always", "1", "never"), ("auto", "", "never"),
        ):
            color_calls.write_text("")
            result, _ = self.make("daemon", COLOR=color, NO_COLOR=no_color,
                                  COLOR_CALLS=str(color_calls))
            self.assertEqual(result.returncode, 0, result.stderr)
            values = color_calls.read_text().splitlines()
            self.assertTrue(values)
            self.assertEqual(set(values), {expected})

    def test_make_color_overrides_reach_native_policy(self):
        color_calls = self.root / "color-calls"
        for arguments, environment, expected in (
            (("NO_COLOR=1",), {"COLOR": "always", "NO_COLOR": ""}, "never"),
            (("COLOR=never",), {"COLOR": "always", "NO_COLOR": ""}, "never"),
            (("COLOR=always", "NO_COLOR="), {"COLOR": "never", "NO_COLOR": "1"}, "always"),
        ):
            color_calls.write_text("")
            result, _ = self.make(
                "daemon", *arguments, COLOR_CALLS=str(color_calls), **environment,
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(set(color_calls.read_text().splitlines()), {expected})

    def test_auto_native_color_uses_make_stdout_terminal(self):
        color_calls = self.root / "color-calls"
        color_calls.write_text("")
        result, _ = self.run_with_stdout_terminal(
            ["bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
             "INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0", "daemon"],
            COLOR_CALLS=str(color_calls), TERM="xterm",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(set(color_calls.read_text().splitlines()), {"always"})

        color_calls.write_text("")
        master, slave = pty.openpty()
        try:
            result = subprocess.run(
                ["bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
                 "INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0", "daemon"],
                cwd=self.root,
                env=self.environment(COLOR_CALLS=str(color_calls), TERM="xterm"),
                stdin=subprocess.DEVNULL, stdout=subprocess.PIPE, stderr=slave,
                text=True, capture_output=False, check=False, timeout=15,
            )
            os.close(slave)
            os.read(master, 4096)
        finally:
            os.close(master)
        self.assertEqual(result.returncode, 0)
        self.assertEqual(set(color_calls.read_text().splitlines()), {"never"})

    def test_lifecycle_success_and_failure_preserve_compose_arguments(self):
        targets = {
            "daemon": "compose up --detach",
            "daemon-build": "compose up --build --detach",
            "daemon-prod": "compose -f compose.yaml -f compose.prod.yaml up --build --detach --wait",
            "start": "compose up",
            "start-build": "compose up --build",
            "start-prod": "compose -f compose.yaml -f compose.prod.yaml up --build",
            "stop": "compose down",
            "ci-start": "compose -f compose.yaml -f docker/ci-compose.yaml up --build --detach --no-deps php",
            "ci-stop": "compose -f compose.yaml -f docker/ci-compose.yaml down",
        }
        completions = {
            "daemon": "Development containers started; readiness was not verified",
            "daemon-build": "Development containers built and started; readiness was not verified",
            "daemon-prod": "Local production services passed Compose running/health checks; HTTP was not verified",
            "start": "Development foreground session ended; readiness was not verified",
            "start-build": "Development build and foreground session ended; readiness was not verified",
            "start-prod": "Local production foreground session ended",
            "stop": "Application containers stopped and removed; named volumes retained",
            "ci-start": "CI PHP service started; application initialization and readiness were not verified",
            "ci-stop": "CI containers stopped and removed; named volumes retained",
        }
        for target, invocation in targets.items():
            with self.subTest(target=target):
                result, calls = self.make(target)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn(invocation + "\n", calls)
                self.assertEqual(result.stderr.count("[OK]"), 1)
                self.assertTrue(result.stderr.endswith(f"[OK] {completions[target]}\n"), result.stderr)
                self.assertLess(result.stderr.index("native Docker diagnostic"), result.stderr.index("[OK]"))
                self.assertLess(result.stderr.index("[WARN]"), result.stderr.index("native Docker diagnostic"))
                self.assertIn("native progress", result.stdout)
                if target in ("daemon", "daemon-build", "ci-start"):
                    self.assertIn("not verified", result.stderr)
                if target == "daemon-prod":
                    self.assertIn("HTTP was not verified", result.stderr)
                result, _ = self.make(target, DOCKER_EXIT="7")
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("[FAIL]", result.stderr)
                self.assertNotIn("[OK]", result.stderr)
                self.assertNotIn("make: ***", result.stderr)

    def test_lifecycle_cancelled_status_is_not_a_failure_or_success(self):
        for target in ("start", "start-build", "start-prod", "daemon", "daemon-build", "daemon-prod",
                       "stop", "ci-start", "ci-stop"):
            for status in ("130", "143"):
                result, _ = self.make(target, DOCKER_EXIT=status)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(f"cancelled (exit {status})", result.stderr)
                self.assertNotIn("[OK]", result.stderr)
                self.assertNotIn("[FAIL]", result.stderr)

    def test_ci_prebuilt_image_is_never_rebuilt_or_pulled(self):
        for status in ("0", "7"):
            with self.subTest(status=status):
                result, calls = self.make("ci-start", "CI_PREBUILT=true", DOCKER_EXIT=status)
                self.assertIn(
                    "compose -f compose.yaml -f docker/ci-compose.yaml up "
                    "--no-build --pull never --detach --no-deps php\n", calls,
                )
                self.assertNotIn("--build", calls)
                self.assertEqual(result.returncode == 0, status == "0", result.stderr)
                self.assertEqual("[OK]" in result.stderr, status == "0")

    def test_ci_prebuilt_invalid_value_fails_before_docker(self):
        result, calls = self.make("ci-start", "CI_PREBUILT=false")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("CI_PREBUILT must be empty or true", result.stderr)
        self.assertEqual(calls, "")
        self.assertNotIn("[OK]", result.stderr)

    def test_context_guards_fail_without_mutation(self):
        targets = (
            "start", "start-build", "start-prod", "daemon", "daemon-build", "daemon-prod",
            "stop", "ci-start", "ci-stop", "bash", "fish", "logs", "docker-prune",
            "docker-kill-containers", "docker-remove-containers", "docker-remove-images",
            "docker-image-digest", "docker-image-verify-pins", "docker-image-check-updates",
            "docker-builder-platforms", "docker-build-platform", "docker-build-multi-platform",
            "docker-setup-qemu",
        )
        for target in targets:
            with self.subTest(target=target):
                result, calls = self.make(target, "INSIDE_DOCKER_CONTAINER=1", "INSIDE_CONTAINER_RUNTIME=1")
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("Docker host", result.stderr)
                self.assertNotIn("[OK]", result.stderr)
                self.assertNotIn("compose ", calls)
                self.assertNotIn("buildx ", calls)
        for target in ("start", "start-build", "start-prod", "daemon", "daemon-build", "daemon-prod"):
            result, calls = self.make(target, RUNNING="1")
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("already running; startup cancelled", result.stderr)
            self.assertIn(
                "run 'make stop' on the Docker host, then rerun your original command",
                result.stderr,
            )
            self.assertIn("To keep services running and follow application logs instead", result.stderr)
            self.assertIn("run 'make logs' on the Docker host", result.stderr)
            self.assertIn("Ctrl-C only stops following logs", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            self.assertNotIn(" up", calls)
            self.assertNotIn(" down", calls)
            self.assertNotIn("logs --follow", calls)

    def test_sessions_and_logs_use_direct_tty_commands_without_final_success(self):
        streams = {
            "bash": ("Opening Bash in the PHP container", "compose exec php bash --rcfile /app/scripts/make/bashrc.sh -i"),
            "fish": ("Opening Fish in the PHP container", "compose exec php fish --init-command source /app/scripts/make/make-shell.fish"),
            "logs": ("Following PHP nginx and MariaDB logs; Ctrl-C stops following", "compose logs --follow php nginx mariadb"),
        }
        for target, (label, invocation) in streams.items():
            with self.subTest(target=target):
                result, calls = self.make(target, RUNNING="5")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn(invocation + "\n", calls)
                self.assertNotIn("exec -T", calls)
                self.assertIn(f"[RUN] {label}\n", result.stderr)
                self.assertLess(
                    result.stderr.index(f"[RUN] {label}"),
                    result.stderr.index("native Docker diagnostic"),
                )
                self.assertNotIn("[OK]", result.stderr)
                self.assertIn("native progress", result.stdout)
                for status in ("7", "130", "143"):
                    result, calls = self.make(target, RUNNING="5", DOCKER_EXIT=status)
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn(invocation + "\n", calls)
                    self.assertIn(f"[RUN] {label}\n", result.stderr)
                    self.assertIn("native Docker diagnostic", result.stderr)
                    self.assertNotIn("[OK]", result.stderr)
                    self.assertNotIn("[FAIL]", result.stderr)
                    self.assertNotIn("[WARN]", result.stderr)
            result, calls = self.make(target, RUNNING="0")
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("[FAIL]", result.stderr)
            self.assertNotIn("compose exec", calls)
            self.assertNotIn("logs --follow", calls)

    def test_maintenance_empty_skip_failures_and_confirmation(self):
        for target, operation in (
            ("docker-kill-containers", "kill"),
            ("docker-remove-containers", "rm"),
            ("docker-remove-images", "rmi"),
        ):
            result, _ = self.make(target)
            self.assertEqual(result.returncode, 0)
            self.assertIn("[SKIP]", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            result, calls = self.make(target, RESOURCES="id-one\nid-two\n")
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn(f"{operation} id-one id-two\n", calls)
            self.assertIn("unrelated projects", result.stderr)
            self.assertEqual(result.stderr.count("[OK]"), 1)
            result, _ = self.make(target, FAIL_QUERY="7")
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("Could not list", result.stderr)
            result, _ = self.make(target, RESOURCES="id-one\n", DOCKER_EXIT="7")
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("[FAIL]", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
        result, calls = self.run_command(["bash", "scripts/docker/docker-maintenance.sh", "prune"], answer="n\n")
        self.assertEqual(result.returncode, 0)
        self.assertIn("[SKIP]", result.stderr)
        self.assertEqual(calls, "")
        result, calls = self.run_command(["bash", "scripts/docker/docker-maintenance.sh", "prune"], answer="y\n")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("system prune --all --volumes --force", calls)
        result, calls = self.run_command(
            ["bash", "scripts/docker/docker-maintenance.sh", "prune"],
            answer="y\n", RESOURCES="id-one\n", DOCKER_EXIT="7",
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn("system prune", calls)
        self.assertNotIn("[OK]", result.stderr)

    def test_builder_platform_colors_preserve_output_and_failure(self):
        plain, _ = self.make("docker-builder-platforms", COLOR="never")
        self.assertEqual(plain.returncode, 0, plain.stderr)
        self.assertEqual(
            plain.stdout,
            "Buildx builder\nCurrent builder and first node\n\n"
            "Name:       fixture\nDriver:     docker\nPlatforms:  linux/amd64, linux/arm64\n"
            "\nField descriptions\n"
            "  Name       Current buildx builder.\n"
            "  Driver     Backend used to run the builder.\n"
            "  Platforms  Build targets reported for the first builder node.\n",
        )
        for color, no_color, expected in (
            ("always", "", True), ("never", "", False),
            ("always", "1", False), ("auto", "", False),
        ):
            with self.subTest(color=color, no_color=no_color):
                result, calls = self.make("docker-builder-platforms", COLOR=color, NO_COLOR=no_color, TERM="xterm")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[34mPlatforms:\x1b[0m" in result.stdout, expected)
                self.assertEqual("\x1b[1;34mBuildx builder\x1b[0m" in result.stdout, expected)
                self.assertEqual("\x1b[1;34mField descriptions\x1b[0m" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)
                self.assertIn("buildx inspect --bootstrap", calls)

        command = ["bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
                   "INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0", "docker-builder-platforms"]
        for term, no_color, expected in (("xterm", "", True), ("dumb", "", False), ("xterm", "1", False)):
            with self.subTest(term=term, no_color=no_color):
                result, _ = self.run_with_stdout_terminal(command, TERM=term, NO_COLOR=no_color)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[34mPlatforms:\x1b[0m" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout).replace("\r\n", "\n"), plain.stdout)

        for overrides, expected in (
            ({"COLOR": "always"}, True), ({"COLOR": "always", "NO_COLOR": "1"}, False),
            ({"COLOR": "always", "NATIVE_COLOR": "never"}, False),
            ({"COLOR": "never", "NATIVE_COLOR": "always"}, True),
        ):
            with self.subTest(direct=overrides):
                result, _ = self.run_command(["bash", "scripts/docker/docker-images.sh", "platforms"], **overrides)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[34mPlatforms:\x1b[0m" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)

        result, _ = self.make("docker-builder-platforms", COLOR="always", DOCKER_EXIT="7")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("native builder failure", result.stderr)
        self.assertIn("[FAIL]", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        self.assertNotIn("Field descriptions", result.stdout)

    def test_build_helpers_warn_and_never_claim_success_before_completion(self):
        result, calls = self.make(
            "docker-build-platform", "PLATFORM=linux/amd64", "TAG=fixture:local", "TARGET=production",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("--load", calls)
        self.assertIn("runtime was not verified", result.stderr)
        self.assertLess(result.stderr.index("native Docker diagnostic"), result.stderr.index("[OK]"))
        result, _ = self.make(
            "docker-build-platform", "PLATFORM=linux/amd64", "TAG=fixture:local", DOCKER_EXIT="7",
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL]", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        result, _ = self.make("docker-builder-platforms", DOCKER_EXIT="7")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL]", result.stderr)
        for action in ("setup-qemu", "push"):
            result, calls = self.run_command(
                ["bash", "scripts/docker/docker-images.sh", action], answer="n\n",
                DOCKERFILE="Dockerfile", TARGET="production", TAG="fixture:remote",
                BUILDER="fixture", PLATFORMS="linux/amd64,linux/arm64"
                if action == "push" else "arm64",
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("[SKIP]", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            self.assertNotIn("buildx build", calls)
            self.assertNotIn("run --privileged", calls)
            for status in ("0", "7"):
                result, calls = self.run_command(
                    ["bash", "scripts/docker/docker-images.sh", action], answer="y\n",
                    DOCKERFILE="Dockerfile", TARGET="production", TAG="fixture:remote",
                    BUILDER="fixture", PLATFORMS="linux/amd64,linux/arm64"
                    if action == "push" else "arm64", DOCKER_EXIT=status,
                )
                self.assertEqual(result.returncode, int(status), result.stderr)
                self.assertIn("--push" if action == "push" else "run --privileged --rm", calls)
                self.assertIn("[OK]" if status == "0" else "[FAIL]", result.stderr)
                if status == "7":
                    self.assertNotIn("[OK]", result.stderr)
                else:
                    self.assertLess(result.stderr.index("native Docker diagnostic"), result.stderr.index("[OK]"))
        result, calls = self.run_command(
            ["bash", "scripts/docker/docker-images.sh", "push"], answer="y\n",
            DOCKERFILE="Dockerfile", TARGET="production", TAG="fixture:remote",
            BUILDER="fixture", PLATFORMS="linux/amd64,linux/arm64",
            BUILDER_MISSING="1", FAIL_MATCH="buildx create",
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL] Create buildx builder", result.stderr)
        self.assertNotIn("buildx build", calls)
        self.assertNotIn("[OK]", result.stderr)

    def test_dry_run_does_not_execute_lifecycle_commands(self):
        for target in ("daemon", "start", "stop", "ci-start", "docker-prune", "bash", "logs"):
            result, calls = self.make("--dry-run", target, RUNNING="5" if target in ("bash", "logs") else "0")
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertNotIn("compose ", calls)
            self.assertNotIn("system prune", calls)
            self.assertNotIn("[OK]", result.stderr)

    def test_targeted_service_checks_keep_troubleshooting_available(self):
        for health in ("", "healthy", "starting", "unhealthy"):
            result, calls = self.make("bash", SERVICE_STATUS=f"php|running|{health}")
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("compose exec php bash", calls)
            self.assertIn("ps --all --format {{.Service}}|{{.State}}|{{.Health}} php\n", calls)
            if health in ("starting", "unhealthy"):
                self.assertIn("[WARN] PHP service health", result.stderr)
        result, calls = self.make("logs", SERVICE_STATUS="php|exited|\nnginx|exited|")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("logs --follow php nginx mariadb", calls)
        for state in ("exited", "paused", "restarting"):
            result, calls = self.make("fish", SERVICE_STATUS=f"php|{state}|")
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("stopped or not running", result.stderr)
            self.assertNotIn("compose exec php fish", calls)
        result, _ = self.make("bash", SERVICE_STATUS="nginx|running|")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("PHP Compose service is absent", result.stderr)
        result, calls = self.make("daemon", SERVICE_STATUS="php|exited|\nnginx|exited|")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("compose up --detach", calls)
        result, calls = self.make("start", SERVICE_STATUS="adminer|running|")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("service 'adminer' is already running", result.stderr)
        self.assertIn("run 'make stop'", result.stderr)
        self.assertIn("run 'make logs'", result.stderr)
        self.assertNotIn("compose up", calls)

    def test_context_failures_stop_before_native_operations(self):
        for variable, message in (
            ("DAEMON_EXIT", "Docker daemon is unavailable"),
            ("COMPOSE_EXIT", "Docker Compose is unavailable"),
            ("STATUS_EXIT", "Could not inspect Compose services"),
        ):
            for target in ("bash", "logs", "daemon"):
                result, calls = self.make(target, **{variable: "7"})
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(message, result.stderr)
                self.assertNotIn("[OK]", result.stderr)
                self.assertNotIn("compose up", calls)
                self.assertNotIn("compose exec php", calls)
                self.assertNotIn("logs --follow", calls)
        (self.root / "bin/docker").unlink()
        environment_path = self.root / "only-bash"
        environment_path.mkdir()
        for tool in ("dirname", "bash"):
            executable = shutil.which(tool)
            assert executable is not None, f"{tool} is required for this test"
            (environment_path / tool).symlink_to(executable)
        result, _ = self.run_command(
            ["/bin/bash", "scripts/make/compose-context.sh", "php", "docker", "compose"],
            PATH=str(environment_path),
        )
        self.assertEqual(result.returncode, 127)
        self.assertIn("Docker command is unavailable", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_help_and_dry_run_do_not_probe_docker(self):
        shutil.copyfile(ROOT / "scripts/make/make-help.sh", self.root / "scripts/make/make-help.sh")
        result, calls = self.make("help")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(calls, "")
        result, calls = self.make("--dry-run", "bash", "daemon")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(calls, "")

    def test_shell_context_and_execution_use_same_ci_compose(self):
        result, calls = self.make("fish", SERVICE_STATUS="php|running|", GITHUB_WORKFLOW="CI")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("compose -f compose.yaml -f docker/ci-compose.yaml ps", calls)
        self.assertIn("compose -f compose.yaml -f docker/ci-compose.yaml exec php fish", calls)


if __name__ == "__main__":
    unittest.main()
