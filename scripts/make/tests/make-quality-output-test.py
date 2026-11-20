#!/usr/bin/env python3
"""Exercise quality recipes without mutating the application or running analyzers."""

import os
from pathlib import Path
import pty
import re
import select
import shutil
import subprocess
import sys
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[3]
TARGETS = (
    "run-tests", "run-tests-php", "run-tests-phpdbg", "infection", "phpcs", "ecs",
    "ecs-fix", "phpinsights", "phplint", "php-parallel-lint", "psalm",
    "psalm-shepherd", "psalm-github", "phpstan", "phpstan-github",
    "lint-configuration", "lint-yaml", "lint-markdown", "lint-python",
    "typecheck-python", "composer-normalize",
    "composer-validate", "composer-require-checker", "composer-unused",
)


class QualityOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-quality-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        for directory in (
            "scripts/output", "scripts/make", "bin", "vendor/bin",
            "tools/02_phpstan/vendor/phpstan/phpstan",
        ):
            (self.root / directory).mkdir(parents=True)
        nvm_directory = self.root / "home/.nvm"
        nvm_directory.mkdir(parents=True)
        (nvm_directory / "nvm.sh").write_text(":")
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        shutil.copyfile(ROOT / "scripts/output/command-output.sh", self.root / "scripts/output/command-output.sh")
        shutil.copyfile(ROOT / "scripts/output/native-output.sh", self.root / "scripts/output/native-output.sh")
        shutil.copyfile(ROOT / "scripts/output/native-output.py", self.root / "scripts/output/native-output.py")
        shutil.copyfile(ROOT / "scripts/output/phpmetrics-output.py", self.root / "scripts/output/phpmetrics-output.py")
        shutil.copyfile(ROOT / "scripts/output/report-output.sh", self.root / "scripts/output/report-output.sh")
        shutil.copyfile(ROOT / "scripts/make/make.sh", self.root / "scripts/make/make.sh")
        shutil.copyfile(ROOT / "scripts/output/markdown-output.sh", self.root / "scripts/output/markdown-output.sh")
        fixture = """#!/usr/bin/env bash
if [ "$(basename "$0")" = python3 ] && [[ "${1:-}" = scripts/output/native-output.py || "${1:-}" = scripts/output/phpmetrics-output.py ]]; then
    if [ "${1:-}" = scripts/output/phpmetrics-output.py ] && [ "${FAIL_FORMATTER:-0}" = 1 ]; then
        printf 'formatter failure\\n' >&2
        exit 7
    fi
    exec "$REAL_PYTHON" "$@"
fi
printf '%s %s\\n' "$0" "$*" >> "$CALLS"
if [ "${CHECK_FALLBACK_XDEBUG:-0}" = 1 ] && {
    [ "$(basename "$0")" = phpdbg ] || [ "$(basename "$0")" = phpunit ] || [ "$(basename "$0")" = console ] ||
    [[ "$(basename "$0")" = php && "$*" == *phpunit* ]];
}; then
    printf '%s %s\\n' "$(basename "$0")" "${XDEBUG_MODE:-}" >> "$XDEBUG_RECORD"
    if [ "${XDEBUG_MODE:-}" != coverage ]; then
        printf 'Unexpected Xdebug fallback mode\\n' >&2
        exit 8
    fi
fi
if [ "${CHECK_METRICS_XDEBUG:-0}" = 1 ] && {
    [ "$(basename "$0")" = phpmetrics ] || [ "${1:-}" = ./vendor/bin/phpmetrics ];
} && [ "${XDEBUG_MODE:-}" != off ]; then
    printf 'Xdebug: [Step Debug] unexpected connection attempt\\n' >&2
    exit 8
fi
if [ "$(basename "$0")" = "${FAIL_COMMAND:-}" ]; then
    printf 'native failure diagnostic\\n' >&2
    exit 7
fi
if [ "$(basename "$0")" = sleep ]; then exit 0; fi
color=false
for argument in "$@"; do
    case "$argument" in --ansi|--colors|--colors=always) color=true ;; esac
done
if [ "$color" = true ]; then printf '\\033[32mnative progress\\033[0m\\n'; else printf 'native progress\\n'; fi
if [ "$(basename "$0")" = phpmetrics ] && [ "${METRICS_SAMPLE:-0}" = 1 ]; then
    printf 'Complexity\\n    Average complexity     3.57\\n'
    printf '\\033[32mDone\\033[0m\\n'
fi
printf '::notice file=src/Example.php,line=1::native annotation\\n' >&2
if [[ "$*" == *--shepherd* ]] && [ -n "${SHEPHERD_DIAGNOSTIC:-}" ]; then
    printf '%s\\n' "$SHEPHERD_DIAGNOSTIC" >&2
fi
for argument in "$@"; do
    if [ "${OMIT_REPORTS:-0}" = 1 ]; then continue; fi
    case "$argument" in
        --report=*) printf '{}\\n' > "${argument#--report=}" ;;
        --report-html=*) mkdir -p "${argument#--report-html=}"; printf '<html/>\\n' > "${argument#--report-html=}/index.html" ;;
        build/logs/clover.xml) printf '<coverage/>\\n' > "$argument" ;;
        build/logs/junit.xml) printf '<testsuites/>\\n' > "$argument" ;;
    esac
done
"""
        for name in (
            "bin/php", "bin/phpdbg", "bin/python3", "bin/composer", "bin/npx", "bin/ruff", "bin/sleep", "bin/console",
            "bin/pyright",
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
            REAL_PYTHON=sys.executable, HOME=str(self.root / "home"),
        )
        environment.update(overrides)
        result = subprocess.run(
            ["bash", "scripts/make/make.sh", "--no-print-directory", "APPLICATION_CONFIG=fixture.json",
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
                completion = ("Psalm analysis completed; local report: build/psalm.json; Shepherd upload success is not verified"
                              if target in ("psalm-shepherd", "psalm-github") else label)
                self.assertEqual(result.stderr.count(f"[OK] {completion}\n"), 1)
                self.assertTrue(result.stderr.endswith(f"\n\n[OK] {completion}\n"))
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
            ("lint-markdown", "npx"), ("lint-python", "ruff"),
            ("typecheck-python", "pyright"),
            ("composer-validate", "composer"),
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

    def test_missing_or_empty_generated_reports_prevent_success(self):
        for target in ("run-tests-php", "run-tests-phpdbg", "psalm", "psalm-shepherd", "psalm-github"):
            for empty in (False, True):
                with self.subTest(target=target, empty=empty):
                    reports = self.root / "build"
                    if reports.exists():
                        shutil.rmtree(reports)
                    if empty:
                        (reports / "logs").mkdir(parents=True)
                        for path in ("psalm.json", "logs/clover.xml", "logs/junit.xml"):
                            (reports / path).touch()
                    result, _ = self.run_target(target, OMIT_REPORTS="1")
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn("Required generated report is missing or empty:", result.stderr)
                    self.assertNotIn("[OK]", result.stderr)
                    self.assertNotIn("[INFO]", result.stderr)

    def test_shepherd_diagnostics_do_not_turn_analysis_success_into_upload_claim(self):
        for diagnostic in ("Shepherd error: server returned 500",
                           "No curl found, cannot send data to shepherd server.",
                           "results sent to shepherd.dev", ""):
            for target in ("psalm-shepherd", "psalm-github"):
                result, _ = self.run_target(target, SHEPHERD_DIAGNOSTIC=diagnostic)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn("[OK] Psalm analysis completed", result.stderr)
                self.assertIn("Shepherd upload success is not verified", result.stderr)
                if diagnostic:
                    self.assertIn(diagnostic, result.stderr)
        result, _ = self.run_target("psalm-shepherd", "ecs")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(result.stderr.endswith("[OK] ecs\n"), result.stderr)

    def test_mixed_goals_and_color_preferences(self):
        result, _ = self.run_target("ecs", "check-skills", COLOR="always")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("\033[32m[OK]\033[0m ecs", result.stderr)
        self.assertIn("[OK]\033[0m Skill metadata and documentation links", result.stderr)
        result, _ = self.run_target("ecs", COLOR="always", NO_COLOR="1")
        self.assertEqual(result.returncode, 0)
        self.assertNotIn("\033", result.stderr)

    def test_native_color_flags_follow_shared_policy_and_ci_annotations_stay_plain(self):
        targets = {
            "phpcs": ("--colors", "--no-colors"),
            "php-parallel-lint": ("--colors", "--no-colors"),
            "run-tests-php": ("--colors=always", "--colors=never"),
            "run-tests-phpdbg": ("--colors=always", "--colors=never"),
            "ecs": ("--ansi", "--no-ansi"),
            "ecs-fix": ("--ansi", "--no-ansi"),
            "phpstan": ("--ansi", "--no-ansi"),
            "phplint": ("--ansi", "--no-ansi"),
            "phpinsights": ("--ansi", "--no-ansi"),
            "composer-validate": ("--ansi", "--no-ansi"),
            "infection": ("--ansi", "--no-ansi"),
        }
        for color, no_color, enabled in (
            ("always", "", True), ("never", "", False),
            ("always", "1", False), ("auto", "", False),
        ):
            for target, (on, off) in targets.items():
                with self.subTest(target=target, color=color, no_color=no_color):
                    result, calls = self.run_target(target, COLOR=color, NO_COLOR=no_color)
                    self.assertEqual(result.returncode, 0, result.stderr)
                    self.assertIn(on if enabled else off, calls)
                    self.assertNotIn(off if enabled else on, calls)
                    self.assertEqual("\033" in result.stdout, enabled)
                    self.assertIn("::notice file=src/Example.php,line=1::native annotation", result.stderr)
            for target in ("psalm", "psalm-shepherd"):
                result, calls = self.run_target(target, COLOR=color, NO_COLOR=no_color)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("--monochrome" in calls, not enabled)
            for target, plain in (("psalm-github", "--monochrome"),
                                  ("phpstan-github", "--no-ansi")):
                result, calls = self.run_target(target, COLOR=color, NO_COLOR=no_color, CI="true")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn(plain, calls)
                analysis = [line for line in calls.splitlines() if "--format=github" in line
                            or "--output-format=github" in line or "--error-format=github" in line]
                self.assertEqual(len(analysis), 1)
                self.assertNotIn("--ansi", analysis[0])
                self.assertIn("::notice file=src/Example.php,line=1::native annotation", result.stderr)
        for arguments, environment, expected in (
            (("COLOR=always",), {"COLOR": "never"}, "--colors"),
            (("COLOR=never",), {"COLOR": "always"}, "--no-colors"),
            (("COLOR=always", "NO_COLOR=1"), {"COLOR": "always"}, "--no-colors"),
        ):
            result, calls = self.run_target("phpcs", *arguments, **environment)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn(expected, calls)

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

    def test_registered_commands_ignore_same_named_files_in_raw_recipe_dry_runs(self):
        targets = re.findall(r"^\$\(call REGISTER_TARGET,([^,]+),", (ROOT / "Makefile").read_text(), re.M)
        for target in targets:
            (self.root / target).touch()
        for target in targets:
            with self.subTest(target=target):
                result, calls = self.run_target(target, "-n", "COMMAND_OUTPUT_RUN=1")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertTrue(result.stdout.strip())
                self.assertNotIn("up to date", result.stdout)
                self.assertNotIn("Nothing to be done", result.stdout)
                self.assertEqual(calls, "")

    def test_file_collisions_do_not_suppress_wrapped_or_direct_failures(self):
        for target in ("ecs", "check-skills"):
            (self.root / target).touch()
            command = "php" if target == "ecs" else "python3"
            result, calls = self.run_target(target, FAIL_COMMAND=command)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("native failure diagnostic", result.stderr)
            self.assertIn("[FAIL]", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
            self.assertTrue(calls)

    def test_python_lint_is_read_only_and_uses_ruff_defaults(self):
        result, calls = self.run_target("lint-python")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("ruff check .", calls)
        self.assertNotIn("--fix", calls)

    def test_python_type_check_is_read_only_and_uses_pyright(self):
        result, calls = self.run_target("typecheck-python")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("pyright", calls)
        self.assertNotIn("--createstub", calls)

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
            ("always", "", "\033[31m[FAIL]\033[0m "),
            ("never", "", "[FAIL] "),
            ("always", "1", "[FAIL] "),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = subprocess.run(
                    ["bash", str(ROOT / "scripts/output/markdown-output.sh"), "bash", "-c",
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
            content = fixture
            if name == "sed":
                content = content.replace(
                    "#!/usr/bin/env bash\n",
                    '#!/usr/bin/env bash\nif [ "$1" = -u ]; then exec /usr/bin/sed "$@"; fi\n',
                    1,
                )
            file.write_text(content)
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
        self.assertIn("[SKIP] Cache warmup skipped", result.stderr)
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
        self.assertIn("Required generated report is missing or empty: build/logs/junit.xml", result.stderr)
        self.assertNotIn("vendor/bin/phpmetrics", calls)
        self.assertNotIn("[OK]", result.stderr)

    def test_phpmetrics_test_fallback_preserves_coverage_without_debugger(self):
        self.prepare_mutation_fixtures()
        record = self.root / "xdebug-record"
        for runner, arguments in (("phpdbg", ()), ("phpunit", ("PHPDBG=",))):
            with self.subTest(runner=runner):
                (self.root / "build/logs/junit.xml").unlink()
                record.write_text("")
                result, calls = self.run_target(
                    "phpmetrics", "XDEBUG_MODE=debug", *arguments,
                    CHECK_FALLBACK_XDEBUG="1", XDEBUG_RECORD=str(record), CHECK_METRICS_XDEBUG="1",
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn("build/logs/junit.xml not found", result.stderr)
                self.assertIn("--junit=build/logs/junit.xml", calls)
                records = record.read_text().splitlines()
                self.assertTrue(any(line.startswith("console ") for line in records), records)
                self.assertTrue(any(line.startswith(f"{runner} ") for line in records), records)
                self.assertTrue(all(line.endswith(" coverage") for line in records), records)
                self.assertNotIn("Xdebug:", result.stderr)

    def test_phpmetrics_color_and_xdebug_preserve_report_and_failures(self):
        self.prepare_mutation_fixtures()
        plain, _ = self.run_target("phpmetrics", COLOR="never", METRICS_SAMPLE="1",
                                   XDEBUG_MODE="debug", CHECK_METRICS_XDEBUG="1")
        self.assertEqual(plain.returncode, 0, plain.stderr)
        report = (self.root / "build/phpmetrics/index.html").read_bytes()
        for color, no_color, expected in (
            ("always", "", True), ("never", "", False),
            ("always", "1", False), ("auto", "", False),
        ):
            with self.subTest(color=color, no_color=no_color):
                result, calls = self.run_target(
                    "phpmetrics", COLOR=color, NO_COLOR=no_color, TERM="xterm",
                    METRICS_SAMPLE="1", XDEBUG_MODE="debug", CHECK_METRICS_XDEBUG="1",
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[1;34mComplexity\x1b[0m" in result.stdout, expected)
                self.assertEqual("\x1b[34m3.57\x1b[0m" in result.stdout, expected)
                self.assertEqual("\x1b[32mDone\x1b[0m" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)
                self.assertEqual((self.root / "build/phpmetrics/index.html").read_bytes(), report)
                self.assertIn("phpmetrics --version", calls)
                self.assertIn("--junit=build/logs/junit.xml --report-html=build/phpmetrics", calls)
                self.assertNotIn("Xdebug:", result.stderr)
                self.assertIn("native annotation", result.stderr)

        for term, no_color, expected in (("xterm", "", True), ("dumb", "", False), ("xterm", "1", False)):
            with self.subTest(term=term, no_color=no_color):
                environment = dict(
                    os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
                    CALLS=str(self.root / "calls"), COLOR="auto", NO_COLOR=no_color, TERM=term,
                    METRICS_SAMPLE="1", XDEBUG_MODE="debug", CHECK_METRICS_XDEBUG="1",
                    FAIL_COMMAND="", REAL_PYTHON=sys.executable,
                )
                environment.pop("NATIVE_COLOR", None)
                master, slave = pty.openpty()
                try:
                    result = subprocess.run(
                        ["bash", "scripts/make/make.sh", "--no-print-directory", "APPLICATION_CONFIG=fixture.json",
                         "INSIDE_DOCKER_CONTAINER=1", "phpmetrics"],
                        cwd=self.root, env=environment, stdout=slave, stderr=subprocess.PIPE, check=False,
                    )
                    os.close(slave)
                    slave = -1
                    output = os.read(master, 65536).decode().replace("\r\n", "\n")
                finally:
                    os.close(master)
                    if slave != -1:
                        os.close(slave)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[1;34mComplexity\x1b[0m" in output, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", output), plain.stdout)

        for command in ("php", "phpmetrics"):
            for color in ("always", "never"):
                with self.subTest(command=command, color=color):
                    result, _ = self.run_target(
                        "phpmetrics", COLOR=color, XDEBUG_MODE="debug",
                        CHECK_METRICS_XDEBUG="1", FAIL_COMMAND=command,
                    )
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn("native failure diagnostic", result.stderr)
                    self.assertNotIn("Xdebug:", result.stderr)
                    self.assertNotIn("HTML report: build/phpmetrics", result.stderr)
                    self.assertNotIn("[OK] Generate PhpMetrics report", result.stderr)

        result, _ = self.run_target("phpmetrics", COLOR="always", FAIL_FORMATTER="1")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("formatter failure", result.stderr)
        self.assertNotIn("HTML report: build/phpmetrics", result.stderr)
        self.assertNotIn("[OK] Generate PhpMetrics report", result.stderr)

    def test_phpmetrics_formatter_streams_partial_lines_and_preserves_native_ansi(self):
        process = subprocess.Popen(
            [sys.executable, str(ROOT / "scripts/output/phpmetrics-output.py")],
            stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
        )
        try:
            process.stdin.write(b"Complex")
            process.stdin.flush()
            self.assertTrue(select.select([process.stdout], [], [], 5)[0], "partial output was buffered")
            first = os.read(process.stdout.fileno(), 4096)
            self.assertEqual(first, b"\x1b[1;34mComplex")
            process.stdin.write(b"ity\n    Average 3.57\n\x1b[32mDone\x1b[0m")
            process.stdin.close()
            process.stdin = None
            rest, stderr = process.communicate(timeout=5)
            self.assertEqual(process.returncode, 0, stderr)
            output = first + rest
            self.assertIn(b"\x1b[34m3.57\x1b[0m", output)
            self.assertIn(b"\x1b[32mDone\x1b[0m", output)
            self.assertEqual(
                    re.sub(rb"\x1b\[[0-9;]*m", b"", output),
                    b"Complexity\n    Average 3.57\nDone",
            )
        finally:
            if process.poll() is None:
                    process.kill()
                    process.wait()

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
