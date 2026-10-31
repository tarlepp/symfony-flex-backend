#!/usr/bin/env python3
"""Verify lifecycle output using isolated fixtures and mocked Docker only."""

import os
from pathlib import Path
import pty
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]


class LifecycleOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-lifecycle-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        (self.root / "scripts").mkdir()
        (self.root / "bin").mkdir()
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        for name in ("command-output.sh", "make.sh", "docker-maintenance.sh", "docker-images.sh",
                     "compose-context.sh"):
            shutil.copyfile(ROOT / "scripts" / name, self.root / "scripts" / name)
            (self.root / "scripts" / name).chmod(0o755)
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
    printf 'Name: fixture\\nDriver: docker\\nPlatforms: linux/amd64, linux/arm64\\n'
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

    def run_command(self, command, answer=None, **overrides):
        self.calls.write_text("")
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(self.calls), COLOR="never", NO_COLOR="", RUNNING="0",
            DOCKER_EXIT="0", FAIL_QUERY="0", RESOURCES="",
            COMPOSER_CACHE_DIR=str(self.root / "composer-cache"),
        )
        environment.update(overrides)
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
                os.close(slave)
        return result, self.calls.read_text()

    def make(self, target, *arguments, **overrides):
        return self.run_command([
            "bash", "scripts/make.sh", "APPLICATION_CONFIG=fixture.json",
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
        for target, invocation in targets.items():
            with self.subTest(target=target):
                result, calls = self.make(target)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn(invocation + "\n", calls)
                self.assertEqual(result.stderr.count("[OK]"), 1)
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
            self.assertIn("already running", result.stderr)
            self.assertNotIn("compose up", calls)

    def test_sessions_and_logs_use_direct_tty_commands_without_final_success(self):
        for target, invocation in (
            ("bash", "compose exec php bash --rcfile /app/scripts/bashrc.sh -i"),
            ("fish", "compose exec php fish --init-command source /app/scripts/make-shell.fish"),
            ("logs", "compose logs --follow php nginx mariadb"),
        ):
            result, calls = self.make(target, RUNNING="5")
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn(invocation, calls)
            self.assertNotIn("exec -T", calls)
            self.assertIn("[RUN]", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            self.assertIn("native progress", result.stdout)
            result, _ = self.make(target, RUNNING="5", DOCKER_EXIT="7")
            self.assertNotEqual(result.returncode, 0)
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
        result, calls = self.run_command(["bash", "scripts/docker-maintenance.sh", "prune"], answer="n\n")
        self.assertEqual(result.returncode, 0)
        self.assertIn("[SKIP]", result.stderr)
        self.assertEqual(calls, "")
        result, calls = self.run_command(["bash", "scripts/docker-maintenance.sh", "prune"], answer="y\n")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("system prune --all --volumes --force", calls)
        result, calls = self.run_command(
            ["bash", "scripts/docker-maintenance.sh", "prune"],
            answer="y\n", RESOURCES="id-one\n", DOCKER_EXIT="7",
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn("system prune", calls)
        self.assertNotIn("[OK]", result.stderr)

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
                ["bash", "scripts/docker-images.sh", action], answer="n\n",
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
                    ["bash", "scripts/docker-images.sh", action], answer="y\n",
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
            ["bash", "scripts/docker-images.sh", "push"], answer="y\n",
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
        result, calls = self.make("daemon", SERVICE_STATUS="adminer|running|")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("service 'adminer' is already running", result.stderr)
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
            (environment_path / tool).symlink_to(shutil.which(tool))
        result, _ = self.run_command(
            ["/bin/bash", "scripts/compose-context.sh", "php", "docker", "compose"],
            PATH=str(environment_path),
        )
        self.assertEqual(result.returncode, 127)
        self.assertIn("Docker command is unavailable", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_help_and_dry_run_do_not_probe_docker(self):
        shutil.copyfile(ROOT / "scripts/make-help.sh", self.root / "scripts/make-help.sh")
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
