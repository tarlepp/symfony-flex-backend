#!/usr/bin/env python3
"""Exercise quality recipes without mutating the application or running analyzers."""

import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
TARGETS = (
    "run-tests", "run-tests-php", "run-tests-phpdbg", "infection", "phpcs", "ecs",
    "ecs-fix", "phpinsights", "phplint", "php-parallel-lint", "psalm",
    "psalm-shepherd", "psalm-github", "phpstan", "phpstan-github",
    "lint-configuration", "lint-yaml", "lint-markdown", "composer-normalize",
    "composer-validate", "composer-require-checker", "composer-unused",
)


class QualityOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-quality-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        for directory in ("scripts", "bin", "vendor/bin", "tools/02_phpstan/vendor/phpstan/phpstan"):
            (self.root / directory).mkdir(parents=True)
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        shutil.copyfile(ROOT / "scripts/command-output.sh", self.root / "scripts/command-output.sh")
        shutil.copyfile(ROOT / "scripts/make.sh", self.root / "scripts/make.sh")
        shutil.copyfile(ROOT / "scripts/markdown-output.sh", self.root / "scripts/markdown-output.sh")
        fixture = """#!/usr/bin/env bash
printf '%s %s\\n' "$0" "$*" >> "$CALLS"
if [ "$(basename "$0")" = "${FAIL_COMMAND:-}" ]; then
    printf 'native failure diagnostic\\n' >&2
    exit 7
fi
if [ "$(basename "$0")" = sleep ]; then exit 0; fi
printf 'native progress\\n'
printf '::notice file=src/Example.php,line=1::native annotation\\n' >&2
for argument in "$@"; do
    case "$argument" in
        --report=*) printf '{}\\n' > "${argument#--report=}" ;;
        build/logs/clover.xml) printf '<coverage/>\\n' > "$argument" ;;
        build/logs/junit.xml) printf '<testsuites/>\\n' > "$argument" ;;
    esac
done
"""
        for name in (
            "bin/php", "bin/phpdbg", "bin/python3", "bin/composer", "bin/npx", "bin/sleep", "bin/console",
            "vendor/bin/phpunit", "vendor/bin/infection", "vendor/bin/parallel-lint",
            "vendor/bin/phpstan", "tools/02_phpstan/vendor/phpstan/phpstan/phpstan",
        ):
            file = self.root / name
            file.write_text(fixture)
            file.chmod(0o755)
        docker = self.root / "bin/docker"
        docker.write_text("#!/usr/bin/env bash\nexit 0\n")
        docker.chmod(0o755)
        # Bash login-shell PATH initialization is unrelated to the recipe contract.
        bash = self.root / "bin/bash"
        bash.write_text('#!/bin/sh\nif [ "$1" = -lc ]; then shift; exec /bin/bash -c "$@"; fi\nexec /bin/bash "$@"\n')
        bash.chmod(0o755)

    def run_target(self, target, *arguments, **overrides):
        calls = self.root / "calls"
        calls.write_text("")
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(calls), COLOR="never", NO_COLOR="", FAIL_COMMAND="",
        )
        environment.update(overrides)
        result = subprocess.run(
            ["bash", "scripts/make.sh", "--no-print-directory", "APPLICATION_CONFIG=fixture.json",
             "INSIDE_DOCKER_CONTAINER=1", target, *arguments],
            cwd=self.root, env=environment, text=True, capture_output=True, check=False,
        )
        return result, calls.read_text()

    def test_every_target_has_one_final_success_after_native_output(self):
        for target in TARGETS:
            with self.subTest(target=target):
                label = "Markdown documentation" if target == "lint-markdown" else target
                result, calls = self.run_target(target)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stderr.count(f"[RUN] {label}\n"), 1)
                self.assertEqual(result.stderr.count(f"[OK] {label}\n"), 1)
                self.assertTrue(result.stderr.endswith(f"\n\n[OK] {label}\n"))
                self.assertIn("native progress", result.stdout)
                self.assertIn("::notice file=src/Example.php", result.stderr)
                self.assertNotIn("\033", result.stderr)
                self.assertTrue(calls)

    def test_preparation_and_analysis_failures_never_report_success(self):
        for target, command in (
            ("run-tests-php", "console"), ("run-tests-phpdbg", "phpdbg"),
            ("infection", "console"), ("phpcs", "php"), ("ecs", "php"),
            ("php-parallel-lint", "parallel-lint"), ("psalm-github", "console"),
            ("phpstan-github", "phpstan"), ("lint-yaml", "console"),
            ("lint-markdown", "npx"), ("composer-validate", "composer"),
        ):
            with self.subTest(target=target, command=command):
                result, _ = self.run_target(target, FAIL_COMMAND=command)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("native failure diagnostic", result.stderr)
                label = "Markdown documentation" if target == "lint-markdown" else target
                self.assertIn(f"[FAIL] {label}", result.stderr)
                self.assertNotIn("make[1]: ***", result.stderr)
                self.assertNotIn("[OK]", result.stderr)

    def test_runner_selection_report_paths_and_github_flags(self):
        for target in ("run-tests-php", "run-tests-phpdbg"):
            result, calls = self.run_target(target)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("--coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml", calls)
            self.assertTrue((self.root / "build/logs/clover.xml").is_file())
            self.assertTrue((self.root / "build/logs/junit.xml").is_file())
            self.assertIn("[INFO] Test reports: build/logs/clover.xml and build/logs/junit.xml", result.stderr)
            self.assertNotIn("[INFO]", result.stdout)
        _, calls = self.run_target("run-tests", "PHPDBG=")
        self.assertNotIn("phpdbg -qrr", calls)
        _, calls = self.run_target("run-tests")
        self.assertIn("phpdbg -qrr", calls)
        for target, flag in (("psalm-github", "--output-format=github"),
                             ("phpstan-github", "--error-format=github")):
            result, calls = self.run_target(target)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn(flag, calls)
        self.assertTrue((self.root / "build/psalm.json").is_file())
        for target in ("psalm", "psalm-shepherd", "psalm-github"):
            result, _ = self.run_target(target)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn("JSON report: build/psalm.json", result.stderr)
            self.assertNotIn("[INFO]", result.stdout)
            if target != "psalm":
                self.assertIn("Shepherd submission status is reported by Psalm", result.stderr)

    def test_mixed_goals_and_color_preferences(self):
        result, _ = self.run_target("ecs", "check-skills", COLOR="always")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("\033[32m[OK]\033[0m ecs", result.stderr)
        self.assertIn("[OK]\033[0m Skill metadata and documentation links", result.stderr)
        result, _ = self.run_target("ecs", COLOR="always", NO_COLOR="1")
        self.assertEqual(result.returncode, 0)
        self.assertNotIn("\033", result.stderr)

    def test_multiple_goals_stop_on_failure_and_keep_going_has_no_false_success(self):
        result, calls = self.run_target("composer-validate", "ecs", FAIL_COMMAND="composer")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL] composer-validate", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        self.assertNotIn("vendor/bin/ecs", calls)
        result, calls = self.run_target("composer-validate", "ecs", "--keep-going", FAIL_COMMAND="composer")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL] composer-validate", result.stderr)
        self.assertNotIn("[OK] composer-validate", result.stderr)
        self.assertIn("[OK] ecs", result.stderr)
        self.assertIn("vendor/bin/ecs", calls)
        self.assertLess(result.stderr.index("[FAIL] composer-validate"), result.stderr.index("[RUN] ecs"))
        result, _ = self.run_target("ecs", "composer-validate", FAIL_COMMAND="composer")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[OK] ecs", result.stderr)
        self.assertNotIn("[OK]", result.stderr.split("[FAIL] composer-validate", 1)[1])

    def test_dry_run_executes_no_fixture_commands(self):
        result, calls = self.run_target("run-tests-php", "-n")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(calls, "")
        self.assertNotIn("[OK]", result.stderr)

    def test_application_recipes_do_not_own_container_forwarding(self):
        makefile = (ROOT / "Makefile").read_text()
        self.assertNotIn("docker compose exec php make", makefile)
        self.assertNotIn("$(WARNING_DOCKER)", makefile)
        self.assertNotIn("$(NOTICE_HOST)", makefile)
        self.assertNotIn("$(ALL_DONE)", makefile)
        for target in TARGETS + ("configuration", "check-action-updates", "check-security",
                                 "project-stats", "generate-jwt-keys"):
            body = makefile.split(f"\n{target}: ## ", 1)[1].split("\n\n", 1)[0]
            self.assertNotIn("INSIDE_DOCKER_CONTAINER", body, target)
            self.assertNotIn("RUNNING_ALL_CONTAINERS", body, target)

    def test_markdown_diagnostic_prefix_preserves_streams_and_status(self):
        diagnostic = "CLAUDE.md:254:81 error MD013/line-length Line length [Expected: 80; Actual: 154]"
        for color, no_color, prefix in (
            ("always", "", "\033[31m[ERROR]\033[0m "),
            ("never", "", "[ERROR] "),
            ("always", "1", "[ERROR] "),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = subprocess.run(
                    ["bash", str(ROOT / "scripts/markdown-output.sh"), "bash", "-c",
                     'printf "%s\\n" "$1"; printf "%s\\n" "$1" >&2; exit 1',
                     "fixture", diagnostic],
                    env=dict(os.environ, COLOR=color, NO_COLOR=no_color),
                    text=True, capture_output=True, check=False,
                )
                self.assertEqual(result.returncode, 1)
                self.assertEqual(result.stdout, "\n" + prefix + diagnostic + "\n")
                self.assertEqual(result.stderr, "\n" + prefix + diagnostic + "\n")

    def prepare_mutation_fixtures(self):
        fixture = (self.root / "bin/php").read_text()
        for name in ("cp", "sed", "chmod", "openssl", "rm"):
            file = self.root / "bin" / name
            file.write_text(fixture)
            file.chmod(0o755)
        jq = self.root / "bin/jq"
        jq.write_text("#!/bin/sh\nprintf '%s\\n' fixture-value\n")
        jq.chmod(0o755)
        ssl = self.root / "docker/nginx/ssl"
        ssl.mkdir(parents=True)
        shutil.copyfile(ROOT / "docker/nginx/ssl/create-keys.sh", ssl / "create-keys.sh")
        (ssl / "create-keys.sh").chmod(0o755)
        metrics = self.root / "vendor/bin/phpmetrics"
        metrics.write_text(fixture)
        metrics.chmod(0o755)
        logs = self.root / "build/logs"
        logs.mkdir(parents=True)
        (logs / "junit.xml").write_text("<testsuites/>")

    def test_mutation_warnings_precede_completion_only_success(self):
        self.prepare_mutation_fixtures()
        targets = (
            ("install", "Install application dependencies", "composer"),
            ("install-tools", "Install tool dependencies", "php"),
            ("update", "Update application dependencies", "php"),
            ("update-tools", "Update tool dependencies", "php"),
            ("cache-clear", "Clear application cache", "console"),
            ("cache-warmup", "Rebuild application cache", "console"),
            ("local-configuration", "Create local configuration", "cp"),
            ("generate-jwt-keys", "Generate JWT key pair", "openssl"),
            ("generate-ssl-cert", "Generate local TLS certificates", "openssl"),
            ("clear", "Remove application and tool dependencies", "rm"),
            ("phpmetrics", "Generate PhpMetrics report", "phpmetrics"),
        )
        for target, label, command in targets:
            with self.subTest(target=target):
                result, calls = self.run_target(target)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn("[WARN]", result.stderr)
                self.assertTrue(result.stderr.endswith(f"\n\n[OK] {label}\n"), result.stderr)
                self.assertLess(result.stderr.index("[WARN]"), result.stderr.index("native annotation"))
                result, calls = self.run_target(target, FAIL_COMMAND=command)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(f"[FAIL] {label}", result.stderr)
                self.assertNotIn("[OK]", result.stderr)
                if target == "generate-ssl-cert":
                    self.assertEqual(calls.count("openssl "), 1)

    def test_cache_warmup_without_console_is_an_explicit_skip(self):
        result, _ = self.run_target("cache-warmup", "CONSOLE=")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("Cache warmup skipped", result.stdout)
        self.assertIn("[SKIP] Rebuild application cache", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        result, _ = self.run_target("cache-warmup", "install", "CONSOLE=")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("[SKIP] Rebuild application cache", result.stderr)
        self.assertIn("[OK] Install application dependencies", result.stderr)

    def test_phpmetrics_missing_junit_runs_tests_and_stops_on_failure(self):
        self.prepare_mutation_fixtures()
        (self.root / "build/logs/junit.xml").unlink()
        result, calls = self.run_target("phpmetrics", FAIL_COMMAND="console")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("build/logs/junit.xml not found", result.stderr)
        self.assertNotIn("clover.xml not found", result.stderr)
        self.assertNotIn("vendor/bin/phpmetrics", calls)
        self.assertNotIn("[OK]", result.stderr)
        result, calls = self.run_target("phpmetrics")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("--junit=build/logs/junit.xml --report-html=build/phpmetrics", calls)
        (self.root / "build/logs/junit.xml").unlink()
        for name in ("phpdbg", "php"):
            (self.root / "bin" / name).write_text("#!/bin/sh\nexit 0\n")
        result, calls = self.run_target("phpmetrics")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Required report build/logs/junit.xml was not generated", result.stderr)
        self.assertNotIn("vendor/bin/phpmetrics", calls)
        self.assertNotIn("[OK]", result.stderr)

    def test_mutation_dry_run_and_overwrite_failure(self):
        self.prepare_mutation_fixtures()
        result, calls = self.run_target("--dry-run", "local-configuration")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(calls, "")
        self.assertNotIn("[OK]", result.stderr)
        result, calls = self.run_target("local-configuration", FAIL_COMMAND="cp", COLOR="always")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("\033[33m[WARN]\033[0m", result.stderr)
        self.assertIn("\033[31m[FAIL]\033[0m", result.stderr)
        self.assertEqual(calls.count("cp "), 1)
        self.assertNotIn("sed ", calls)
        self.assertNotIn("[OK]", result.stderr)

    def test_clear_tools_is_limited_to_tool_vendor_directories(self):
        tool = self.root / "tools/group with spaces/vendor"
        nested = tool / "package/vendor"
        unrelated = self.root / "not-tools/vendor"
        nested.mkdir(parents=True)
        unrelated.mkdir(parents=True)
        result, _ = self.run_target("clear-tools")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(tool.exists())
        self.assertTrue(unrelated.exists())
        self.assertTrue((self.root / "vendor").exists())
        self.assertIn("[WARN]", result.stderr)
        self.assertTrue(result.stderr.endswith("\n\n[OK] Remove tool dependencies\n"))

    def test_ci_configuration_guard_precedes_mutation(self):
        self.prepare_mutation_fixtures()
        result, calls = self.run_target("configure-ci", GITHUB_ACTIONS="false")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("restricted to GitHub Actions", result.stderr)
        self.assertNotIn("cp ", calls)
        self.assertNotIn("[OK]", result.stderr)
        result, calls = self.run_target("configure-ci", GITHUB_ACTIONS="true")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("Overwriting .env and .env.test", result.stderr)
        self.assertIn("cp .env.gh-actions .env", calls)
        self.assertIn("[OK] Configure CI environment and JWT keys", result.stderr)


if __name__ == "__main__":
    unittest.main()
