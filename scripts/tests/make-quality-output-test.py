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
            ["make", "--no-print-directory", "APPLICATION_CONFIG=fixture.json",
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

    def test_mixed_goals_and_color_preferences(self):
        result, _ = self.run_target("ecs", "check-skills", COLOR="always")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("\033[32m[OK]\033[0m ecs", result.stderr)
        self.assertIn("[OK]\033[0m Skill metadata and documentation links", result.stderr)
        result, _ = self.run_target("ecs", COLOR="always", NO_COLOR="1")
        self.assertEqual(result.returncode, 0)
        self.assertNotIn("\033", result.stderr)

    def test_dry_run_executes_no_fixture_commands(self):
        result, calls = self.run_target("run-tests-php", "-n")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(calls, "")
        self.assertNotIn("[OK]", result.stderr)

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


if __name__ == "__main__":
    unittest.main()
