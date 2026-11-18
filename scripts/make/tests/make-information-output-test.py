#!/usr/bin/env python3
"""Check information streams, help inventory and local report preparation offline."""

import json
import os
from pathlib import Path
import pty
import re
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[3]


class InformationOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-information-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        for directory in (
            "scripts/output", "scripts/make", "scripts/reports", "scripts/docker",
            "scripts/ci", "bin", "vendor/bin", "build/logs", "src", "tests",
            "public", "migrations",
        ):
            (self.root / directory).mkdir(parents=True)
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        for path in (
            "output/command-output.sh", "output/command-progress.py",
            "output/native-output.sh", "output/native-output.py",
            "output/report-output.sh", "make/make.sh", "make/make-help.sh",
            "reports/prepare-sonar-reports.sh", "reports/project-stats.sh",
            "reports/project-stats.py", "reports/tool-versions.sh",
            "docker/docker-images.sh", "ci/ci-detect-changes.sh",
        ):
            destination = self.root / "scripts" / path
            shutil.copyfile(ROOT / "scripts" / path, destination)
            destination.chmod(0o755)
        fixture = """#!/usr/bin/env bash
printf '%s %s\\n' "$0" "$*" >> "$CALLS"
if [ "${CHECK_VERSION_XDEBUG:-0}" = 1 ]; then
    case "$(basename "$0")" in
        php|console|composer)
            if [ "${XDEBUG_MODE:-}" != off ]; then
                echo 'Xdebug: [Step Debug] unexpected connection attempt' >&2
                exit 8
            fi ;;
    esac
fi
if [ "${VERSION_DIAGNOSTICS:-0}" = 1 ]; then
    case "$(basename "$0")" in
        console)
            echo '[2099-01-01T00:00:00+00:00] deprecation.INFO: Unrelated package deprecated an API' >&2
            echo '[2099-02-02T00:00:00+00:00] deprecation.WARNING: Another deprecation' >&2
            echo '{"message":"Different deprecation","channel":"deprecation","level":200}' >&2
            echo '[2099-01-01T00:00:00+00:00] app.WARNING: Keep this warning' >&2 ;;
        composer)
            if [[ " $* " == *" --ansi "* ]]; then
                printf '\\033[32mPHP\\033[39m version \\033[33m9.9.9\\033[39m (/different/php)\\n' >&2
            fi
            echo '[2099-01-01T00:00:00+00:00] deprecation.INFO: Unrelated package deprecated an API' >&2
            echo '{"message":"Different deprecation","channel":"deprecation","level":200}' >&2
            echo 'PHP version 9.9.9 (/different/php)' >&2
            echo 'Run the "some-other-command" command to get more detailed diagnostics output.' >&2
            echo 'Composer warning: Keep this warning' >&2 ;;
    esac
fi
if [ "$(basename "$0")" = "${FAIL_COMMAND:-}" ]; then
    echo 'native failure diagnostic' >&2
    exit 7
fi
case "$(basename "$0")" in
    jq)
        if [[ "$*" == *dependencies* || "$*" == *--slurpfile* ]]; then exec /usr/bin/jq "$@"; fi
        printf '{"fixture": true}\\n' ;;
    python3)
        if [ "${1:-}" != --version ] && [[ "$*" != "-m pip "* ]]; then
            exec /usr/bin/python3 "$@"
        fi
        printf 'Python fixture version\\n' ;;
    composer)
        if [[ "$*" == *--format=json* ]]; then
            printf '{"dependencies":{"first":{"license":["MIT"]},"second":{"license":["MIT"]},"third":{"license":["MIT","Apache-2.0"]},"fourth":{"license":[]}}}\\n'
        else
            printf 'native Composer version\\n'
        fi ;;
    *) printf 'native information\\n' ;;
esac
if [[ "$*" == *php-coveralls* ]]; then
    printf '{}\\n' > build/logs/coveralls-upload.json
    printf 'Dump submitting json file: build/logs/coveralls-upload.json\\n'
    if [ "${UPLOAD_FAIL:-0}" = 1 ]; then
        echo 'Client error occurred. status: 422 Unprocessable Entity' >&2
        exit 1
    fi
    echo 'Finish submitting. status: 200 OK'
fi
if [[ "$*" == *--log-json=* ]]; then
    if [ "${OMIT_REPORTS:-0}" != 1 ]; then printf '{}\\n' > build/phploc.json; fi
    if [ "${PHPLOC_SAMPLE:-0}" = 1 ]; then
        printf 'Size\\n  Lines of Code (LOC)   42\\n  Comment Lines of Code  12 (28.57%%)\\n'
        printf 'Cyclomatic Complexity\\n  Average Complexity per Class  3.51\\nDependencies\\nStructure\\n'
    fi
fi
"""
        for name in ("bin/php", "bin/php-fpm", "bin/console", "bin/composer", "bin/jq",
                     "bin/python3", "bin/node", "bin/npm"):
            file = self.root / name
            file.write_text(fixture)
            file.chmod(0o755)
        docker = self.root / "bin/docker"
        docker.write_text("""#!/bin/sh
if [ "$1 $2 $3" = 'buildx imagetools inspect' ]; then
    if [ "${DOCKER_FAIL:-0}" = 1 ]; then
        echo 'fixture registry unavailable' >&2
        exit 7
    fi
    case "$*" in
        *--raw*) printf '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64"}}]}\\n' ;;
        *) printf 'sha256:fixture\\n' ;;
    esac
fi
if [ "$1 $2" = 'buildx inspect' ]; then
    printf 'Name: fixture\\nDriver: docker\\nPlatforms: linux/amd64, linux/arm64\\n'
fi
exit 0
""")
        docker.chmod(0o755)
        self.calls = self.root / "calls"
        (self.root / "vendor/composer").mkdir()
        (self.root / "composer.json").write_text(json.dumps({
            "require": {"example/runtime": "*"},
            "require-dev": {"phpunit/phpunit": "*"},
        }))
        (self.root / "vendor/composer/installed.json").write_text(json.dumps({
            "packages": [
                {"name": "example/runtime", "version": "2.3.4",
                 "description": "Runtime dependency introduction"},
                {"name": "symfony/framework-bundle", "version": "v8.1.11",
                 "description": "Symfony framework"},
                {"name": "phpunit/phpunit", "version": "13.4.6",
                 "description": "Development test framework"},
                {"name": "example/transitive", "version": "1.0.0",
                 "description": "Transitive dependency"},
            ]
        }))
        self.clover = self.root / "build/logs/clover.xml"
        self.junit = self.root / "build/logs/junit.xml"
        self.clover.write_text('<coverage><file name="/app/src/Example.php"/></coverage>\n')
        self.junit.write_text('<testsuites><testcase file="/app/tests/ExampleTest.php"/></testsuites>\n')
        (self.root / "src/Example.php").write_text("<?php\n")

    def make(self, target, *arguments, **overrides):
        self.calls.write_text("")
        environment = dict(
            os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
            CALLS=str(self.calls), COLOR="never", NO_COLOR="", FAIL_COMMAND="", UPLOAD_FAIL="0",
            NVM_DIR=str(self.root / "missing-nvm"),
        )
        environment.update(overrides)
        result = subprocess.run(
            ["bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
             "INSIDE_DOCKER_CONTAINER=1", target, *arguments],
            cwd=self.root, env=environment, capture_output=True, text=True, check=False,
        )
        return result

    def init_stats_repository(self):
        subprocess.run(["git", "init", "-q"], cwd=self.root, check=True, capture_output=True)
        subprocess.run(
            ["git", "add", "--", "src/Example.php"],
            cwd=self.root, check=True, capture_output=True,
        )

    def test_help_lists_every_documented_target_once_with_purpose_and_risk(self):
        (self.root / "help").touch()
        result = self.make("help")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stderr, "[RUN] Command help\n\n[OK] Command help\n")
        expected = re.findall(r"^([a-zA-Z0-9-]+):.*## ", (ROOT / "Makefile").read_text(), re.M)
        actual = re.findall(r"^  ([a-zA-Z0-9-]+) +", result.stdout, re.M)
        self.assertCountEqual(actual, expected)
        self.assertEqual(len(actual), len(set(actual)))
        for title in (
            "Docker lifecycle", "Docker images", "Setup, dependencies", "Application tests",
            "Dependency, security", "Linting and static analysis", "Configuration and information",
            "Reports and uploads",
        ):
            self.assertIn(title, result.stdout)
        self.assertRegex(result.stdout, r"docker-prune +\[DESTRUCTIVE\]")
        self.assertRegex(result.stdout, r"generate-jwt-keys +\[OVERWRITE\]")
        self.assertNotIn("\033", result.stdout)
        result = self.make("help", COLOR="always")
        self.assertIn("\033[34m", result.stdout)
        self.assertIn("\033[33m[DESTRUCTIVE]", result.stdout)
        result = self.make("help", COLOR="always", NO_COLOR="1")
        self.assertNotIn("\033", result.stdout)
        result = self.make("help", "INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertCountEqual(re.findall(r"^  ([a-zA-Z0-9-]+) +", result.stdout, re.M), expected)

    def test_change_preview_defaults_overrides_cleanup_and_failure(self):
        detector = self.root / "scripts/ci/ci-detect-changes.sh"
        detector.write_text(
            '#!/bin/bash\n'
            'printf "%s|%s|%s|%s|%s\\n" "$BASE_SHA" "$HEAD_SHA" "$EVENT_NAME" '
            '"$COLOR" "$GITHUB_OUTPUT"\n'
            'printf "php=true\\n" >> "$GITHUB_OUTPUT"\n'
            'exit "${DETECT_EXIT:-0}"\n'
        )
        for arguments, expected, exit_code in (
            ((), "HEAD^|HEAD|pull_request|never|", 0),
            (("BASE_SHA=master", "HEAD_SHA=topic", "EVENT_NAME=push", "COLOR=always"),
             "master|topic|push|always|", 0),
            (("COLOR=always", "NO_COLOR=1"), "HEAD^|HEAD|pull_request|never|", 0),
            ((), "HEAD^|HEAD|pull_request|never|", 7),
        ):
            with self.subTest(arguments=arguments, exit_code=exit_code):
                result = self.make(
                    "ci-detect-changes", *arguments, DETECT_EXIT=str(exit_code),
                )
                self.assertEqual(result.returncode == 0, exit_code == 0, result.stderr)
                self.assertTrue(result.stdout.startswith(expected), result.stdout)
                output = Path(result.stdout.strip().split("|")[-1])
                self.assertFalse(output.exists(), "Temporary output must be removed")
                if exit_code:
                    self.assertIn("[FAIL] Preview CI validation selection (exit 2)", result.stderr)

    def test_info_displays_message_without_executing_it(self):
        for color, no_color, prefix in (
            ("always", "", "\033[34m[INFO]\033[0m"),
            ("never", "", "[INFO]"),
            ("always", "1", "[INFO]"),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = self.make("info", "info_msg=exit 7", COLOR=color, NO_COLOR=no_color)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout, "")
                self.assertIn(f"{prefix} exit 7\n", result.stderr)
                self.assertRegex(result.stderr, r"\n\n(?:\x1b\[32m)?\[OK\]")
                self.assertEqual(self.calls.read_text(), "")
        message = """Say "hello"; `exit 7`; 'single quotes' *"""
        result = self.make("info", f"info_msg={message}")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn(f"[INFO] {message}\n", result.stderr)

    def test_configuration_lint_uses_shared_statuses_and_real_php(self):
        php = self.root / "bin/php"
        php.unlink()
        php.symlink_to(shutil.which("php"))
        configuration = self.root / "fixture.json"
        for content, status in (('{"valid":true}', 0), ("{broken", 1), (None, 1)):
            for color, no_color, enabled in (
                ("always", "", True), ("never", "", False), ("always", "1", False),
            ):
                with self.subTest(content=content, color=color, no_color=no_color):
                    if content is None:
                        configuration.unlink(missing_ok=True)
                    else:
                        configuration.write_text(content)
                    result = self.make("lint-configuration", COLOR=color, NO_COLOR=no_color)
                    self.assertEqual(result.returncode, 0 if status == 0 else 2, result.stderr)
                    label = "INFO" if status == 0 else "FAIL"
                    code = 34 if status == 0 else 31
                    prefix = f"\033[{code}m[{label}]\033[0m" if enabled else f"[{label}]"
                    self.assertIn(prefix, result.stderr)
                    if status:
                        self.assertIn("[FAIL]", result.stderr)
                        self.assertNotIn("[OK]", result.stderr)
                        self.assertNotIn("[INFO]", result.stderr)
                    else:
                        self.assertIn("[OK]", result.stderr)
                    self.assertNotRegex(result.stderr, r"make(?:\[[0-9]+\])?: \*\*\*")

    def test_metadata_drives_help_routing_and_completion_labels(self):
        makefile = self.root / "Makefile"
        original = makefile.read_text()
        makefile.write_text(original.replace(
            "$(call REGISTER_TARGET,phpcs,quality,wrapped)",
            "$(call REGISTER_TARGET,phpcs,reports,wrapped,OVERWRITE)",
        ))
        result = self.make("help")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertRegex(result.stdout.split("Reports and uploads\n", 1)[1],
                         r"phpcs +\[OVERWRITE\]")
        registration = "$(call REGISTER_TARGET,configuration,information,wrapped,,Read application configuration)"
        makefile.write_text(original.replace(registration,
                                            "$(call REGISTER_TARGET,configuration,information,wrapped,,Custom label)"))
        result = self.make("configuration")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("[RUN] Custom label", result.stderr)
        self.assertIn("[OK] Custom label", result.stderr)
        self.assertNotIn("Read application configuration", result.stderr)
        makefile.write_text(original.replace(registration,
                                            "$(call REGISTER_TARGET,configuration,information,direct)"))
        result = self.make("configuration")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertNotIn("[RUN]", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        makefile.write_text(original.replace(registration,
                                            "$(call REGISTER_TARGET,configuration,information,host)"))
        result = self.make("configuration", "INSIDE_DOCKER_CONTAINER=0")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout), {"fixture": True})
        self.assertNotIn("compose-context", result.stderr)

    def test_help_orders_groups_and_risk_levels_for_routine_use(self):
        result = self.make("help")
        self.assertEqual(result.returncode, 0, result.stderr)
        titles = (
            "Configuration and information", "Application tests and tooling regressions",
            "Linting and static analysis", "Dependency, security and license checks",
            "Reports and uploads", "Setup, dependencies and application maintenance",
            "Docker lifecycle and interactive sessions (host)",
            "Docker images and host maintenance (host)",
        )
        positions = [result.stdout.index(title + "\n") for title in titles]
        self.assertEqual(positions, sorted(positions))
        for index, title in enumerate(titles):
            section = result.stdout.split(title + "\n", 1)[1]
            if index + 1 < len(titles):
                section = section.split(titles[index + 1] + "\n", 1)[0]
            entries = re.findall(r"^  ([a-zA-Z0-9-]+) +(.*)$", section, re.M)
            keys = [(2 if description.startswith("[DESTRUCTIVE]") else
                     1 if description.startswith("[OVERWRITE]") else 0, name)
                    for name, description in entries]
            self.assertEqual(keys, sorted(keys), title)
        self.assertIn("make help GROUP=<group>", result.stdout)

    def test_makefile_registrations_and_recipes_follow_help_order(self):
        result = self.make("help")
        self.assertEqual(result.returncode, 0, result.stderr)
        listed = re.findall(r"^  ([a-zA-Z0-9-]+) +", result.stdout, re.M)
        makefile = (ROOT / "Makefile").read_text()
        registered = re.findall(r"^\$\(call REGISTER_TARGET,([^,]+),", makefile, re.M)
        recipes = re.findall(r"^([a-zA-Z0-9-]+):(?:.*## .*|)\n", makefile, re.M)
        self.assertEqual(registered, ["help", "info", *listed])
        self.assertEqual(recipes, registered)
        titles = (
            "Configuration and information", "Application tests and tooling regressions",
            "Linting and static analysis", "Dependency, security and license checks",
            "Reports and uploads", "Setup, dependencies and application maintenance",
            "Docker lifecycle and interactive sessions (host)",
            "Docker images and host maintenance (host)",
        )
        for title in titles:
            self.assertEqual(makefile.count(f"# {title}\n"), 2, title)

    def test_help_filters_each_group_and_rejects_invalid_selection(self):
        registrations = re.findall(
            r"^\$\(call REGISTER_TARGET,([^,]+),([^,]+),",
            (ROOT / "Makefile").read_text(), re.M,
        )
        for group in ("information", "tests", "quality", "checks", "reports",
                      "setup", "lifecycle", "docker"):
            with self.subTest(group=group):
                result = self.make("help", f"GROUP={group}", "INSIDE_DOCKER_CONTAINER=0")
                self.assertEqual(result.returncode, 0, result.stderr)
                actual = re.findall(r"^  ([a-zA-Z0-9-]+) +", result.stdout, re.M)
                self.assertCountEqual(actual, [name for name, category in registrations
                                              if category == group])
                self.assertEqual(self.calls.read_text(), "")
        default = self.make("help")
        explicit = self.make("help", "GROUP=all")
        self.assertEqual(default.stdout, explicit.stdout)
        result = self.make("help", "GROUP=invalid")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("[FAIL] Unknown help group: invalid", result.stderr)
        self.assertIn("GROUP=all|information", result.stderr)
        self.assertNotIn("Available commands", result.stdout)
        self.assertNotIn("[OK]", result.stderr)
        result = self.make("help", "GROUP=quality", "-n")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertNotIn("Available commands", result.stdout)
        self.assertEqual(self.calls.read_text(), "")

    def test_incomplete_or_invalid_metadata_fails_explicitly(self):
        makefile = self.root / "Makefile"
        original = makefile.read_text()
        registration = "$(call REGISTER_TARGET,phpcs,quality,wrapped)"
        for replacement, error in (
            ("", "Missing help metadata: phpcs"),
            ("$(call REGISTER_TARGET,phpcs,unknown,wrapped)", "Invalid help metadata: phpcs"),
            ("$(call REGISTER_TARGET,phpcs,quality,wrapped,unknown)", "Invalid help metadata: phpcs"),
            ("$(call REGISTER_TARGET,phpcs,quality,unknown)", "Invalid execution metadata: phpcs"),
            (registration + "\n" + registration, "Duplicate target metadata: phpcs"),
            ("$(call REGISTER_TARGET,missing,quality,wrapped)\n" + registration,
             "Missing target description: missing"),
        ):
            with self.subTest(replacement=replacement):
                makefile.write_text(original.replace(registration, replacement))
                result = self.make("help")
                self.assertNotEqual(result.returncode, 0)
                self.assertIn(error, result.stderr)
                self.assertNotIn("[OK]", result.stderr)

    def test_information_stdout_has_no_status_or_ansi_pollution(self):
        self.init_stats_repository()
        for target in ("configuration", "project-stats", "application-info", "tool-versions", "check-licenses"):
            with self.subTest(target=target):
                (self.root / target).touch()
                result = self.make(target, COLOR="always")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertNotRegex(result.stdout, r"\[(RUN|OK|INFO|FAIL)\]")
                if target not in ("tool-versions", "project-stats", "check-licenses"):
                    self.assertNotIn("\x1b", result.stdout)
                self.assertEqual(result.stderr.count("[OK]"), 1)
                if target == "configuration":
                    self.assertEqual(json.loads(result.stdout), {"fixture": True})
                if target == "check-licenses":
                    plain = re.sub(r"\x1b\[[0-9;]*m", "", result.stdout)
                    self.assertIn("\x1b[34m", result.stdout)
                    self.assertEqual(
                        {line.strip() for line in plain.splitlines()},
                        {"2 MIT", "1 MIT OR Apache-2.0", "1 UNSPECIFIED"},
                    )
                if target == "project-stats":
                    plain = re.sub(r"\x1b\[[0-9;]*m", "", result.stdout)
                    self.assertIn("\x1b[", result.stdout)
                    self.assertTrue(plain.startswith("Project file statistics"))
                    self.assertRegex(plain, r"(?m)^Total\s+1\s+1$")

    def test_license_summary_disables_xdebug_without_hiding_composer_errors(self):
        result = self.make("check-licenses", XDEBUG_MODE="debug", CHECK_VERSION_XDEBUG="1")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertNotIn("Xdebug:", result.stderr)
        self.assertIn("MIT", result.stdout)

        result = self.make(
            "check-licenses", XDEBUG_MODE="debug", CHECK_VERSION_XDEBUG="1", FAIL_COMMAND="composer",
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("native failure diagnostic", result.stderr)
        self.assertNotIn("Xdebug:", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_license_summary_colors_follow_native_policy(self):
        plain = self.make("check-licenses", COLOR="never")
        for color, no_color, expected in (
            ("always", "", True), ("never", "", False),
            ("always", "1", False), ("auto", "", False),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = self.make("check-licenses", COLOR=color, NO_COLOR=no_color, TERM="xterm")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)
                self.assertNotIn("\x1b", plain.stdout)

        for term, no_color, expected in (("xterm", "", True), ("dumb", "", False), ("xterm", "1", False)):
            with self.subTest(term=term, no_color=no_color):
                environment = dict(
                    os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
                    CALLS=str(self.calls), COLOR="auto", NO_COLOR=no_color, TERM=term,
                    FAIL_COMMAND="", NVM_DIR=str(self.root / "missing-nvm"),
                )
                environment.pop("NATIVE_COLOR", None)
                master, slave = pty.openpty()
                try:
                    result = subprocess.run(
                        ["bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
                         "INSIDE_DOCKER_CONTAINER=1", "check-licenses"],
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
                self.assertEqual("\x1b[" in output, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", output), plain.stdout)

    def test_project_stats_color_respects_native_policy(self):
        self.init_stats_repository()
        plain = self.make("project-stats", COLOR="never")
        forced = self.make("project-stats", COLOR="always")
        disabled = self.make("project-stats", COLOR="always", NO_COLOR="1")
        automatic = self.make("project-stats", COLOR="auto", TERM="xterm")
        for result in (plain, forced, disabled, automatic):
            self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("\x1b[1;34mProject file statistics\x1b[0m", forced.stdout)
        self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", forced.stdout), plain.stdout)
        self.assertEqual(disabled.stdout, plain.stdout)
        self.assertEqual(automatic.stdout, plain.stdout)

        environment = dict(os.environ, COLOR="auto", NO_COLOR="", TERM="xterm")
        environment.pop("NATIVE_COLOR", None)
        for overrides, expect_color in (
            ({}, True),
            ({"TERM": "dumb"}, False),
            ({"NO_COLOR": "1"}, False),
        ):
            with self.subTest(overrides=overrides):
                master, slave = pty.openpty()
                try:
                    result = subprocess.run(
                        ["bash", "scripts/reports/project-stats.sh"], cwd=self.root,
                        env=dict(environment, **overrides),
                        stdout=slave, stderr=subprocess.PIPE, check=False,
                    )
                    os.close(slave)
                    slave = -1
                    terminal_output = os.read(master, 65536).decode()
                finally:
                    os.close(master)
                    if slave != -1:
                        os.close(slave)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[" in terminal_output, expect_color)
                self.assertEqual(
                    re.sub(r"\x1b\[[0-9;]*m", "", terminal_output).replace("\r\n", "\n"),
                    plain.stdout,
                )

        for native, no_color, expect_color in (
            ("always", "", True), ("never", "", False), ("always", "1", False),
        ):
            with self.subTest(native=native, no_color=no_color):
                result = subprocess.run(
                    ["bash", "scripts/reports/project-stats.sh"], cwd=self.root,
                    env=dict(environment, COLOR="never", NATIVE_COLOR=native, NO_COLOR=no_color),
                    capture_output=True, text=True, check=False,
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[" in result.stdout, expect_color)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)

    def test_project_stats_counts_tracked_working_tree_files_and_text_lines(self):
        self.init_stats_repository()
        (self.root / "docs").mkdir()
        (self.root / "docs/guide.MD").write_bytes(b"one\ntwo\npartial")
        (self.root / "docs/a name.md").write_bytes(b"three\n" * 1000)
        (self.root / "docs/image.PNG").write_bytes(b"not text\n" * 8000 + b"\0binary\n")
        (self.root / "Makefile.fixture").write_bytes(b"make\n")
        (self.root / "LICENSE").write_bytes(b"license\n")
        (self.root / ".example").write_bytes(b"config\n")
        (self.root / "untracked.sh").write_bytes(b"ignored\n")
        (self.root / "docs/link.md").symlink_to("guide.MD")
        for name in (
            "composer.lock", "docs/symfony.lock", "docs/package-lock.json",
            "docs/npm-shrinkwrap.json", "docs/pnpm-lock.yaml", "docs/bun.lockb",
            "docs/app.min.js", "docs/app.min.css", "docs/app.js.map", "docs/app.css.map",
            "config/reference.php", "docker/nginx/ssl/rootCA.key", "docker/nginx/ssl/rootCA.pem",
            "docker/nginx/ssl/tls.crt", "docker/nginx/ssl/tls.csr", "docker/nginx/ssl/tls.key",
        ):
            file = self.root / name
            file.parent.mkdir(parents=True, exist_ok=True)
            file.write_bytes(b"generated\n" * 100)
        (self.root / "docs/LockedUser.php").write_bytes(b"source\n")
        (self.root / "docs/app.js").write_bytes(b"source\n")
        subprocess.run(
            ["git", "add", "--", "docs", "Makefile.fixture", "LICENSE", ".example",
             "composer.lock", "config", "docker"],
            cwd=self.root, check=True, capture_output=True,
        )
        (self.root / "composer.lock").unlink()
        (self.root / "src/Example.php").write_bytes(b"modified\nworking tree\n")

        result = self.make("project-stats")
        self.assertEqual(result.returncode, 0, result.stderr)
        lines = result.stdout.splitlines()
        self.assertEqual(lines[:3], [
            "Project file statistics", "Tracked working-tree files (excluding generated artifacts)", "",
        ])
        self.assertEqual(lines[3], "File type       Files  Text lines")
        self.assertEqual(lines[4], "--------------  -----  ----------")
        summary_index = lines.index("Summary")
        table = lines[4:lines.index("Project areas") - 1]
        self.assertEqual(table[-2], lines[4])
        self.assertTrue(all(len(line) == len(lines[3]) for line in table))
        self.assertEqual(lines[summary_index:], [
            "Summary",
            "  Included files: 10",
            "  File types:    7",
            "  Text lines:    1,009",
            "",
            "  Most files:",
            "    .md: 3 files",
            "    .php: 2 files",
            "    (no extension): 1 file",
            "",
            "  Most text lines:",
            "    .md: 1,002 lines",
            "    .php: 3 lines",
            "    (no extension): 1 line",
        ])
        rows = {}
        for line in table:
            match = re.fullmatch(r"(.+?)\s+([\d,]+)\s+([\d,]+)", line)
            if match:
                rows[match[1]] = tuple(int(value.replace(",", "")) for value in match.groups()[1:])
        self.assertIn("1,002", result.stdout)
        self.assertEqual(rows, {
            ".example": (1, 1),
            ".fixture": (1, 1),
            ".js": (1, 1),
            ".md": (3, 1002),
            ".php": (2, 3),
            ".png": (1, 0),
            "(no extension)": (1, 1),
            "Total": (10, 1009),
        })
        self.assertNotIn(".sh", rows)
        self.assertRegex(result.stdout, r"(?m)^  Source\s+1\s+2$")
        self.assertRegex(result.stdout, r"(?m)^  Other\s+9\s+1,007$")
        self.assertIn("  Lockfiles:           6\n", result.stdout)
        self.assertIn("  Generated artifacts: 10\n", result.stdout)
        self.assertIn("  Files:      0 tests / 1 source\n", result.stdout)
        self.assertIn("  Text lines: 0 tests / 2 source\n", result.stdout)

    def test_project_stats_pr_changes_compare_merge_base_and_exclude_generated(self):
        subprocess.run(["git", "init", "-q"], cwd=self.root, check=True)
        def git(*args):
            return subprocess.run(
                ["git", "-c", "user.name=Fixture", "-c", "user.email=fixture@example.invalid", *args],
                cwd=self.root, text=True, capture_output=True, check=True,
            ).stdout.strip()

        (self.root / "src/old.php").write_text("one\ntwo\n")
        (self.root / "src/delete.php").write_text("deleted\n")
        (self.root / "src/moved.php").write_text("moved\n")
        (self.root / "composer.lock").write_text("old\n")
        git("add", "src/old.php", "src/delete.php", "src/moved.php", "composer.lock")
        git("commit", "-qm", "base")
        original = git("rev-parse", "HEAD")
        (self.root / "src/old.php").write_text("one\nchanged\n")
        (self.root / "src/delete.php").unlink()
        (self.root / "src/moved.php").rename(self.root / "tests/moved.php")
        (self.root / "tests/new.php").write_text("test\n")
        (self.root / "tests/binary.dat").write_bytes(b"binary\0data")
        (self.root / "composer.lock").write_text("new\n")
        git("add", "-A", "--", "src/old.php", "src/delete.php", "src/moved.php", "tests", "composer.lock")
        git("commit", "-qm", "feature")
        head = git("rev-parse", "HEAD")
        git("switch", "--detach", original)
        (self.root / "src/base-only.php").write_text("base only\n")
        git("add", "src/base-only.php")
        git("commit", "-qm", "base advanced")
        base = git("rev-parse", "HEAD")
        command = ["python3", "scripts/reports/project-stats.py", "--base", base, "--head", head]
        result = subprocess.run(command, cwd=self.root, text=True, capture_output=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertRegex(result.stdout, r"(?m)^Source\s+0\s+2\s+1\s+1\s+3\s+0$")
        self.assertRegex(result.stdout, r"(?m)^Tests\s+3\s+0\s+0\s+2\s+0\s+1$")
        self.assertRegex(result.stdout, r"(?m)^Other\s+0\s+0\s+0\s+0\s+0\s+0$")
        self.assertRegex(result.stdout, r"(?m)^Total\s+3\s+2\s+1\s+3\s+3\s+1$")
        self.assertIn("Renames count as an addition and a deletion", result.stdout)
        invalid = subprocess.run(
            ["python3", "scripts/reports/project-stats.py", "--base", "invalid", "--head", head],
            cwd=self.root, text=True, capture_output=True, check=False,
        )
        self.assertNotEqual(invalid.returncode, 0)
        self.assertIn("Both revisions must be full commit IDs", invalid.stderr)

    def test_project_stats_supports_empty_index_and_reports_missing_git_repository(self):
        result = self.make("project-stats")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Project statistics failed:", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

        subprocess.run(["git", "init", "-q"], cwd=self.root, check=True, capture_output=True)
        result = self.make("project-stats")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertRegex(result.stdout, r"(?m)^Total\s+0\s+0$")
        self.assertTrue(result.stdout.endswith(
            "Summary\n  Included files: 0\n  File types:    0\n  Text lines:    0\n"
            "\n  Most files:\n    None\n\n  Most text lines:\n    None\n"
        ))

        (self.root / "image.png").write_bytes(b"binary\0\n")
        subprocess.run(["git", "add", "--", "image.png"], cwd=self.root, check=True, capture_output=True)
        result = self.make("project-stats")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(result.stdout.endswith(
            "\n  Most files:\n    .png: 1 file\n\n  Most text lines:\n    None\n"
        ))

    def test_information_pipeline_and_process_failures_propagate(self):
        for target, command in (
            ("configuration", "jq"), ("application-info", "console"),
            ("tool-versions", "php"), ("check-licenses", "composer"), ("check-licenses", "jq"),
        ):
            result = self.make(target, FAIL_COMMAND=command)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("native failure diagnostic", result.stderr)
            self.assertIn("[FAIL]", result.stderr)
            self.assertNotIn("[OK]", result.stderr)
        self.init_stats_repository()
        (self.root / "src").rename(self.root / "missing-src")
        result = self.make("project-stats")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Project statistics failed:", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_tool_versions_reports_installed_packages_and_runtime_inventory(self):
        tool = self.root / "tools/fixture"
        metadata = tool / "vendor/composer"
        metadata.mkdir(parents=True)
        (tool / "composer.json").write_text(json.dumps({
            "require-dev": {"example/analyzer": "9.0.0"}
        }))
        (tool / "composer.lock").write_text("not installed metadata")
        (metadata / "installed.json").write_text(json.dumps({"packages": [
            {"name": "example/analyzer", "version": "1.2.3"},
            {"name": "squizlabs/php_codesniffer", "version": "3.4.5"},
            {"name": "cmgmyr/phploc", "version": "8.0.6"},
            {"name": "example/transitive-library", "version": "2.0.0"},
            {"name": "roave/security-advisories", "version": "dev-latest"},
        ]}))

        result = self.make("tool-versions")

        self.assertEqual(result.returncode, 0, result.stderr)
        for heading in (
            "Platform versions", "Project main dependencies",
            "Installed PHP development packages",
            "Python tooling", "Node.js tooling", "Shell and supporting tools",
            "Optional security and workflow validators on PATH",
        ):
            self.assertIn(heading, result.stdout)
        self.assertIn("example/analyzer: 1.2.3", result.stdout)
        self.assertIn("squizlabs/php_codesniffer: 3.4.5", result.stdout)
        self.assertIn("cmgmyr/phploc: 8.0.6", result.stdout)
        self.assertNotIn("9.0.0", result.stdout)
        self.assertNotIn("example/transitive-library", result.stdout)
        self.assertNotIn("roave/security-advisories", result.stdout)
        self.assertNotIn("Workflow validator downloads and npx caches", result.stdout)
        self.assertTrue(result.stdout.startswith(
            "\nDevelopment environment tool inventory (current container)\n"))
        platform, dependencies = result.stdout.split("\nProject main dependencies\n", 1)
        self.assertIn("Node.js: native information", platform)
        self.assertIn("Python fixture version", platform)
        self.assertNotIn("native Composer version", platform)
        self.assertIn("OS: ", platform)
        self.assertIn("Architecture: ", platform)
        self.assertIn("Symfony: v8.1.11", platform)
        runtime = dependencies.split("\nTooling\n")[0]
        self.assertIn("example/runtime: 2.3.4 - Runtime dependency introduction", runtime)
        self.assertNotIn("phpunit/phpunit", runtime)
        self.assertNotIn("example/transitive", runtime)
        self.assertNotIn("native Composer version", runtime)
        development = dependencies.split("\n  Installed PHP development packages\n")[1]
        project, isolated = development.split(
            "    Isolated tool packages (tools/*/composer.json)\n", 1)
        self.assertIn("    Project packages (composer.json require-dev)\n", project)
        self.assertIn("      phpunit/phpunit: 13.4.6", project)
        self.assertNotIn("example/analyzer", project)
        self.assertNotIn("example/runtime", project)
        self.assertNotIn("phpunit/phpunit", isolated)
        self.assertEqual(result.stdout.count("Node.js: "), 1)
        self.assertIn("      tools/fixture\n        example/analyzer: 1.2.3", result.stdout)
        self.assertEqual(
            [line for line in result.stdout.splitlines() if line and not line.startswith(" ")],
            ["Development environment tool inventory (current container)",
             "Platform versions", "PHP runtime extensions and debugging",
             "Project main dependencies", "Tooling"],
        )
        tooling = result.stdout.split("\nTooling\n", 1)[1]
        for heading in ("Installed PHP development packages", "Python tooling",
                        "Node.js tooling", "Shell and supporting tools",
                        "Optional security and workflow validators on PATH"):
            self.assertIn(f"\n  {heading}\n", tooling)
        for line in result.stdout.splitlines():
            if line.strip() and line not in (
                "Development environment tool inventory (current container)",
                "Platform versions", "Project main dependencies", "Tooling",
                "PHP runtime extensions and debugging",
                "Installed PHP development packages", "Python tooling",
                "Node.js tooling", "Shell and supporting tools",
                "Optional security and workflow validators on PATH",
            ):
                self.assertTrue(line.startswith("  "), line)
        calls = self.calls.read_text()
        for arguments in (
            "php --version", "php-fpm --version",
            "composer --version --no-ansi", "python3 --version", "python3 -m pip --version",
            "python3 -m pip list --disable-pip-version-check --format=columns",
            "node --version", "npm --version", "npm list --global --depth=0 --offline",
        ):
            self.assertIn(arguments, calls)
        self.assertNotRegex(calls, r"\b(install|update|npx)\b")

    def test_stack_inventory_queries_running_services_and_preserves_failures(self):
        docker = self.root / "bin/docker"
        docker.write_text("""#!/bin/bash
printf '%s\\n' "$*" >> "$CALLS"
case "$*" in
    'version --format '*) echo '  Docker client: fixture / Engine: fixture' ;;
    'compose version --short') echo 'fixture-compose' ;;
    'compose ps --services --status running') printf '%s\\n' "${SERVICES:-nginx}" ;;
    'compose exec -T nginx nginx -v') echo 'nginx version: fixture' >&2 ;;
    'compose exec -T mariadb mariadbd --version')
        echo 'MariaDB diagnostic' >&2
        exit 7 ;;
    *) exit 99 ;;
esac
""")
        for services, status in (("nginx", 0), ("nginx\nmariadb", 7)):
            result = subprocess.run(
                ["bash", "scripts/reports/tool-versions.sh", "--stack", "docker", "compose"],
                cwd=self.root, capture_output=True, text=True, check=False,
                env=dict(os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
                         CALLS=str(self.calls), COLOR="never", SERVICES=services),
            )
            self.assertEqual(result.returncode, status, result.stderr)
            self.assertIn("Docker client: fixture / Engine: fixture", result.stdout)
            self.assertIn("Docker Compose: fixture-compose", result.stdout)
            self.assertIn("nginx version: fixture", result.stdout)
            if status == 0:
                self.assertIn("mariadb: service not running", result.stdout)
            else:
                self.assertIn("MariaDB diagnostic", result.stderr)
        self.assertNotRegex(self.calls.read_text(), r"\b(up|start|run)\b")

    def test_inventory_rejects_host_execution_before_querying_tools(self):
        script = self.root / "scripts/reports/tool-versions.sh"
        script.write_text(script.read_text().replace(
            "[ ! -f /.dockerenv ]", "[ ! -f /fixture-no-container-marker ]"))

        for arguments, expected in (
            ([], "make tool-versions"),
            (["--production"], "docker compose -f compose.yaml -f compose.prod.yaml exec"),
        ):
            with self.subTest(arguments=arguments):
                result = subprocess.run(
                    ["bash", "scripts/reports/tool-versions.sh", *arguments],
                    cwd=self.root, capture_output=True, text=True, check=False,
                )
                self.assertEqual(result.returncode, 1)
                self.assertIn("not on the Docker host", result.stderr)
                self.assertIn(expected, result.stderr)
                self.assertNotIn("command not found", result.stderr)
                self.assertEqual(result.stdout, "")
                self.assertFalse(self.calls.exists())

    def test_production_inventory_uses_only_runtime_tools(self):
        for name in ("node", "npm", "python3", "composer"):
            (self.root / "bin" / name).write_text("#!/bin/sh\nexit 99\n")

        result = subprocess.run(
            ["bash", "scripts/reports/tool-versions.sh", "--production"],
            cwd=self.root, capture_output=True, text=True, check=False,
            env=dict(os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
                     CALLS=str(self.calls), COLOR="never", FAIL_COMMAND=""),
        )

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("Production runtime inventory (current container)", result.stdout)
        self.assertIn("Loaded PHP extensions", result.stdout)
        self.assertIn("example/runtime: 2.3.4 - Runtime dependency introduction",
                      result.stdout)
        self.assertNotIn("Tooling", result.stdout)
        self.assertNotIn("phpunit/phpunit", result.stdout)
        self.assertNotRegex(self.calls.read_text(), r"phpunit|console|pip|npm|node")

        failed = subprocess.run(
            ["bash", "scripts/reports/tool-versions.sh", "--invalid"],
            cwd=self.root, capture_output=True, text=True, check=False,
        )
        self.assertEqual(failed.returncode, 64)
        self.assertIn("Usage:", failed.stderr)

    def test_tool_versions_disables_debugger_without_hiding_diagnostics(self):
        result = self.make("tool-versions", XDEBUG_MODE="debug", CHECK_VERSION_XDEBUG="1")

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertNotIn("Xdebug: [Step Debug]", result.stdout + result.stderr)

        failed = self.make("tool-versions", XDEBUG_MODE="debug",
                           CHECK_VERSION_XDEBUG="1", FAIL_COMMAND="composer")

        self.assertNotEqual(failed.returncode, 0)
        self.assertIn("native failure diagnostic", failed.stderr)
        self.assertNotIn("[OK]", failed.stderr)

    def test_tool_versions_filters_diagnostic_categories_not_specific_messages(self):
        for failed_command in ("", "composer"):
            with self.subTest(failed_command=failed_command):
                result = self.make("tool-versions", COLOR="always", VERSION_DIAGNOSTICS="1",
                                   FAIL_COMMAND=failed_command)

                self.assertEqual(result.returncode == 0, not failed_command,
                                 result.stderr)
                self.assertNotIn("deprecation", result.stderr)
                self.assertNotIn("get more detailed diagnostics", result.stderr)
                self.assertNotIn("PHP version 9.9.9", result.stderr)
                self.assertIn("Composer warning: Keep this warning", result.stderr)
                if failed_command:
                    self.assertIn("native failure diagnostic", result.stderr)
                    self.assertNotIn("[OK]", result.stderr)

    def test_tool_versions_colors_respect_shared_controls(self):
        thefuck = self.root / "bin/thefuck"
        thefuck.write_text("#!/bin/sh\nprintf 'The Fuck fixture version\\n' >&2\n")
        thefuck.chmod(0o755)
        tool = self.root / "tools/fixture"
        metadata = tool / "vendor/composer"
        metadata.mkdir(parents=True)
        (tool / "composer.json").write_text(json.dumps({
            "require-dev": {"example/analyzer": "*"}
        }))
        (metadata / "installed.json").write_text(json.dumps({"packages": [
            {"name": "example/analyzer", "version": "1.2.3"},
        ]}))

        plain = self.make("tool-versions", COLOR="never")
        colored = self.make("tool-versions", COLOR="always")
        no_color = self.make("tool-versions", COLOR="always", NO_COLOR="1")

        for result in (plain, colored, no_color):
            self.assertEqual(result.returncode, 0, result.stderr)

        self.assertIn("\033[34mPlatform versions\033[0m", colored.stdout)
        self.assertIn("\033[34mProject main dependencies\033[0m", colored.stdout)
        self.assertIn("\033[32mnative information\033[0m", colored.stdout)
        self.assertIn("  \033[32mnative information\033[0m", colored.stdout)
        for label in ("Node.js", "npm"):
            self.assertIn(f"  \033[32m{label}: native information\033[0m",
                          colored.stdout)
        self.assertIn("  \033[32mthefuck: The Fuck fixture version\033[0m",
                      colored.stdout)
        self.assertIn("    \033[32mexample/analyzer: 1.2.3\033[0m", colored.stdout)
        self.assertIn("  \033[34mInstalled packages\033[0m", colored.stdout)
        self.assertIn("  \033[34mGlobal packages\033[0m", colored.stdout)
        for line in colored.stdout.splitlines():
            if line.startswith("  ") and line.strip():
                self.assertRegex(line, r"^ +\x1b\[(32|33|34)m.+\x1b\[0m$")
        self.assertNotIn("\033", plain.stdout)
        self.assertNotIn("\033", no_color.stdout)
        self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", colored.stdout), plain.stdout)

    def test_tool_versions_handles_missing_optional_tools_and_packages_explicitly(self):
        tool = self.root / "tools/missing"
        tool.mkdir(parents=True)
        (tool / "composer.json").write_text("{}")
        for name in ("node", "npm"):
            (self.root / "bin" / name).unlink()
        for name in ("bash", "basename", "dirname", "sed", "awk", "uname"):
            (self.root / "bin" / name).symlink_to(shutil.which(name))

        result = subprocess.run(
            ["/bin/bash", "scripts/reports/tool-versions.sh"],
            cwd=self.root, capture_output=True, text=True, check=False,
            env=dict(os.environ, PATH=str(self.root / "bin"),
                     NVM_DIR=str(self.root / "missing-nvm"), CALLS=str(self.calls),
                     FAIL_COMMAND=""),
        )

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("tools/missing: dependencies not installed", result.stdout)
        for label in ("phpdbg", "Node.js", "npm", "thefuck", "ShellCheck"):
            self.assertIn(f"{label}: not installed or not on PATH", result.stdout)

    def test_tool_versions_propagates_runtime_and_metadata_failures(self):
        for command in ("python3", "node", "npm"):
            with self.subTest(command=command):
                result = self.make("tool-versions", FAIL_COMMAND=command)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("native failure diagnostic", result.stderr)
                self.assertNotIn("[OK]", result.stderr)

        tool = self.root / "tools/broken"
        metadata = tool / "vendor/composer"
        metadata.mkdir(parents=True)
        (tool / "composer.json").write_text("{}")
        (metadata / "installed.json").write_text("invalid json")

        result = self.make("tool-versions")

        self.assertNotEqual(result.returncode, 0)
        self.assertIn("parse error", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_tool_versions_loads_existing_nvm_for_noninteractive_node(self):
        runtime = self.root / "node-runtime"
        runtime.mkdir()
        for name in ("node", "npm"):
            (self.root / "bin" / name).rename(runtime / name)
        for name in ("bash", "basename", "dirname", "sed", "awk", "uname"):
            (self.root / "bin" / name).symlink_to(shutil.which(name))
        nvm = self.root / "nvm"
        nvm.mkdir()
        (nvm / "nvm.sh").write_text(
            f'export PATH="{runtime}:$PATH"\n'
            'nvm() {\n'
            '    [ "$#" -eq 1 ] && [ "$1" = --version ] || return 64\n'
            '    printf "fixture-nvm-version\\n"\n'
            '}\n'
        )

        result = subprocess.run(
            ["/bin/bash", "scripts/reports/tool-versions.sh"],
            cwd=self.root, capture_output=True, text=True, check=False,
            env=dict(os.environ, PATH=str(self.root / "bin"),
                     NVM_DIR=str(nvm), CALLS=str(self.calls), FAIL_COMMAND=""),
        )

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("Node.js: native information", result.stdout)
        self.assertIn("npm: native information", result.stdout)
        self.assertIn("nvm: fixture-nvm-version", result.stdout)
        self.assertIn("npm list --global --depth=0 --offline", self.calls.read_text())

    def test_image_digest_and_platform_stdout_excludes_status_banners(self):
        jq = self.root / "bin/jq"
        jq.write_text('#!/bin/sh\nexec /usr/bin/jq "$@"\n')
        jq.chmod(0o755)
        for target in ("docker-image-digest", "docker-builder-platforms"):
            result = self.make(target, "INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0",
                               "IMAGE=fixture:latest")
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertNotRegex(result.stdout, r"\[(RUN|OK|INFO|FAIL)\]|\x1b")
            self.assertIn("[RUN]", result.stderr)
            self.assertIn("[OK]", result.stderr)
            if target == "docker-image-digest":
                self.assertIn("Digest:     sha256:fixture", result.stdout)
                self.assertIn("FROM fixture:latest@sha256:fixture", result.stdout)
            else:
                self.assertIn("Platforms:  linux/amd64, linux/arm64", result.stdout)
                self.assertIn("Build targets reported for the first builder node.", result.stdout)

    def write_builder_fixture(self):
        docker = self.root / "bin/docker"
        docker.write_text("""#!/bin/sh
case "$*" in
    'buildx version') exit 0 ;;
    'buildx inspect --bootstrap')
        printf 'Name: fixture\\nDriver: docker\\nUnrelated: ignored\\n'
        printf 'Name: first-node\\nPlatforms: linux/amd64, linux/arm64\\n'
        printf 'Name: second-node\\nDriver: ignored\\nPlatforms: linux/s390x\\n'
        if [ "${BUILDER_EXIT:-0}" != 0 ]; then
            echo 'fixture builder unavailable' >&2
            exit "$BUILDER_EXIT"
        fi
        ;;
    *) echo "Unexpected Docker command: $*" >&2; exit 64 ;;
esac
""")
        docker.chmod(0o755)

    def test_builder_platforms_keep_first_fields_and_color_policy(self):
        self.write_builder_fixture()
        expected = (
            "Buildx builder\nCurrent builder and first node\n\n"
            "Name:       fixture\nDriver:     docker\n"
            "Platforms:  linux/amd64, linux/arm64\n\n"
            "Field descriptions\n"
            "  Name       Current buildx builder.\n"
            "  Driver     Backend used to run the builder.\n"
            "  Platforms  Build targets reported for the first builder node.\n"
        )
        for color, no_color, native_color, progress, colored in (
            ("never", "", "", "never", False),
            ("always", "", "", "never", True),
            ("always", "1", "", "never", False),
            ("never", "", "always", "never", True),
            ("always", "", "never", "never", False),
            ("never", "1", "always", "never", False),
            ("never", "", "", "always", False),
            ("always", "", "", "always", True),
        ):
            with self.subTest(color=color, no_color=no_color, native_color=native_color):
                environment = dict(
                    os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
                    COLOR=color, NO_COLOR=no_color, PROGRESS=progress,
                )
                environment.pop("NATIVE_COLOR", None)
                if native_color:
                    environment["NATIVE_COLOR"] = native_color
                result = subprocess.run(
                    ["bash", "scripts/docker/docker-images.sh", "platforms"], cwd=self.root,
                    env=environment, capture_output=True, text=True, check=False,
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), expected)
                self.assertEqual("\x1b[34mName:\x1b[0m" in result.stdout, colored)
                self.assertIn("[OK]", result.stderr)

    def test_builder_platforms_propagate_docker_and_formatter_failures(self):
        self.write_builder_fixture()
        for command, status, message in (
            ("docker", 7, "fixture builder unavailable"),
            ("awk", 9, "fixture formatter failed"),
        ):
            with self.subTest(command=command):
                if command == "awk":
                    awk = self.root / "bin/awk"
                    awk.write_text("#!/bin/sh\necho 'fixture formatter failed' >&2\nexit 9\n")
                    awk.chmod(0o755)
                environment = dict(
                    os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}",
                    COLOR="never", NO_COLOR="1", PROGRESS="never",
                    BUILDER_EXIT=str(status if command == "docker" else 0),
                )
                result = subprocess.run(
                    ["bash", "scripts/docker/docker-images.sh", "platforms"], cwd=self.root,
                    env=environment, capture_output=True, text=True, check=False,
                )
                self.assertEqual(result.returncode, status, result.stderr)
                self.assertIn(message, result.stderr)
                self.assertIn(f"[FAIL] Inspect buildx builder platforms (exit {status})", result.stderr)
                self.assertNotIn("[OK]", result.stderr)
                self.assertNotIn("Field descriptions", result.stdout)
                if command == "docker":
                    self.assertIn("Name:       fixture", result.stdout)

    def test_builder_platforms_make_failure_does_not_claim_success(self):
        self.write_builder_fixture()
        result = self.make(
            "docker-builder-platforms", "INSIDE_DOCKER_CONTAINER=0",
            "INSIDE_CONTAINER_RUNTIME=0", BUILDER_EXIT="7",
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("fixture builder unavailable", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        self.assertNotIn("Field descriptions", result.stdout)

    def test_image_digest_color_keeps_pin_copyable(self):
        jq = self.root / "bin/jq"
        jq.write_text('#!/bin/sh\nexec /usr/bin/jq "$@"\n')
        jq.chmod(0o755)
        arguments = ("INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0", "IMAGE=fixture:latest")
        plain = self.make("docker-image-digest", *arguments, COLOR="never")
        self.assertEqual(plain.returncode, 0, plain.stderr)
        pin = "    FROM fixture:latest@sha256:fixture\n"
        self.assertIn(pin, plain.stdout)
        for color, no_color, expected in (
            ("always", "", True), ("never", "", False),
            ("always", "1", False), ("auto", "", False),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = self.make("docker-image-digest", *arguments, COLOR=color, NO_COLOR=no_color, TERM="xterm")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[34mDigest:\x1b[0m" in result.stdout, expected)
                self.assertEqual("\x1b[1;34mPin reference:\x1b[0m" in result.stdout, expected)
                self.assertIn(pin, result.stdout)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)

        for term, no_color, expected in (("xterm", "", True), ("dumb", "", False), ("xterm", "1", False)):
            with self.subTest(term=term, no_color=no_color):
                environment = dict(
                    os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}", CALLS=str(self.calls),
                    COLOR="auto", NO_COLOR=no_color, TERM=term, PROGRESS="never",
                )
                environment.pop("NATIVE_COLOR", None)
                master, slave = pty.openpty()
                try:
                    result = subprocess.run(
                        ["bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
                         "INSIDE_DOCKER_CONTAINER=0", "INSIDE_CONTAINER_RUNTIME=0",
                         "docker-image-digest", "IMAGE=fixture:latest"],
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
                self.assertEqual("\x1b[34mDigest:\x1b[0m" in output, expected)
                self.assertIn(pin, output)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", output), plain.stdout)

        for overrides, expected in (
            ({"COLOR": "always"}, True), ({"COLOR": "always", "NO_COLOR": "1"}, False),
            ({"COLOR": "always", "NATIVE_COLOR": "never"}, False),
            ({"COLOR": "never", "NATIVE_COLOR": "always"}, True),
        ):
            with self.subTest(direct=overrides):
                environment = dict(
                    os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}", CALLS=str(self.calls),
                    IMAGE="fixture:latest", COLOR="never", NO_COLOR="", PROGRESS="never",
                )
                environment.pop("NATIVE_COLOR", None)
                environment.update(overrides)
                result = subprocess.run(
                    ["bash", "scripts/docker/docker-images.sh", "digest"], cwd=self.root, env=environment,
                    capture_output=True, text=True, check=False,
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[34mDigest:\x1b[0m" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)

        failed = self.make("docker-image-digest", *arguments, COLOR="always", DOCKER_FAIL="1")
        self.assertNotEqual(failed.returncode, 0)
        self.assertIn("fixture registry unavailable", failed.stderr)
        self.assertNotIn("Pin reference:", failed.stdout)
        self.assertNotIn("[OK]", failed.stderr)

    def test_sonar_paths_are_relative_and_source_reports_remain_unchanged(self):
        originals = (self.clover.read_bytes(), self.junit.read_bytes())
        result = self.make("prepare-sonar-reports")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, "")
        self.assertIn('name="src/Example.php"', (self.root / "build/sonar/clover.xml").read_text())
        self.assertIn('file="tests/ExampleTest.php"', (self.root / "build/sonar/junit.xml").read_text())
        self.assertEqual((self.clover.read_bytes(), self.junit.read_bytes()), originals)
        self.assertIn("build/sonar/clover.xml and build/sonar/junit.xml", result.stderr)
        self.assertIn("no upload was performed", result.stderr)
        self.assertEqual(result.stderr.count("[OK]"), 1)
        self.assertEqual(list((self.root / "build/sonar").glob(".prepare-*")), [])

    def test_missing_reports_and_conversion_failures_do_not_claim_creation(self):
        self.junit.unlink()
        result = self.make("prepare-sonar-reports")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("missing or empty: build/logs/junit.xml", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        self.assertFalse((self.root / "build/sonar").exists())
        self.junit.write_text("")
        result = self.make("prepare-sonar-reports")
        self.assertNotEqual(result.returncode, 0)
        self.junit.write_text("<testsuites/>")
        output = self.root / "build/sonar"
        output.mkdir()
        for name in ("clover.xml", "junit.xml"):
            (output / name).write_text("existing")
        sed = self.root / "bin/sed"
        sed.write_text("""#!/bin/sh
case "$*" in
    *junit.xml*) echo 'native conversion failure' >&2; exit 7 ;;
    *) exec /bin/sed "$@" ;;
esac
""")
        sed.chmod(0o755)
        result = self.make("prepare-sonar-reports")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("native conversion failure", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        for name in ("clover.xml", "junit.xml"):
            self.assertEqual((output / name).read_text(), "existing")
        self.assertEqual(list(output.glob(".prepare-*")), [])

    def test_generated_coveralls_payload_does_not_mean_upload_success(self):
        result = self.make("report-coveralls")
        self.assertEqual(result.returncode, 0)
        self.assertIn("Finish submitting. status: 200 OK", result.stdout)
        self.assertIn("[OK] Coveralls client completed; consult native HTTP status for upload confirmation", result.stderr)
        result = self.make("report-coveralls", UPLOAD_FAIL="1")
        self.assertNotEqual(result.returncode, 0)
        self.assertTrue((self.root / "build/logs/coveralls-upload.json").is_file())
        self.assertIn("Dump submitting json file:", result.stdout)
        self.assertIn("422 Unprocessable Entity", result.stderr)
        self.assertIn("[FAIL] Submit coverage to Coveralls", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_phploc_console_color_preserves_report_and_failures(self):
        plain = self.make("phploc", COLOR="never", PHPLOC_SAMPLE="1")
        self.assertEqual(plain.returncode, 0, plain.stderr)
        report = (self.root / "build/phploc.json").read_bytes()
        for color, no_color, expected in (
            ("always", "", True), ("never", "", False),
            ("always", "1", False), ("auto", "", False),
        ):
            with self.subTest(color=color, no_color=no_color):
                result = self.make("phploc", COLOR=color, NO_COLOR=no_color, TERM="xterm", PHPLOC_SAMPLE="1")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual("\x1b[1;34mSize\x1b[0m" in result.stdout, expected)
                for heading in ("Cyclomatic Complexity", "Dependencies", "Structure"):
                    self.assertEqual(f"\x1b[1;34m{heading}\x1b[0m" in result.stdout, expected)
                self.assertEqual("\x1b[34m12 (28.57%)\x1b[0m" in result.stdout, expected)
                self.assertEqual("\x1b[34m3.51\x1b[0m" in result.stdout, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", result.stdout), plain.stdout)
                self.assertEqual((self.root / "build/phploc.json").read_bytes(), report)

        for term, no_color, expected in (("xterm", "", True), ("dumb", "", False), ("xterm", "1", False)):
            with self.subTest(term=term, no_color=no_color):
                environment = dict(
                    os.environ, PATH=f"{self.root / 'bin'}:{os.environ['PATH']}", CALLS=str(self.calls),
                    COLOR="auto", NO_COLOR=no_color, TERM=term, PHPLOC_SAMPLE="1",
                    FAIL_COMMAND="", NVM_DIR=str(self.root / "missing-nvm"),
                )
                environment.pop("NATIVE_COLOR", None)
                master, slave = pty.openpty()
                try:
                    result = subprocess.run(
                        ["bash", "scripts/make/make.sh", "APPLICATION_CONFIG=fixture.json",
                         "INSIDE_DOCKER_CONTAINER=1", "phploc"],
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
                self.assertEqual("\x1b[1;34mSize\x1b[0m" in output, expected)
                self.assertEqual(re.sub(r"\x1b\[[0-9;]*m", "", output), plain.stdout)
                self.assertEqual((self.root / "build/phploc.json").read_bytes(), report)

        result = self.make("phploc", COLOR="always", FAIL_COMMAND="php")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("native failure diagnostic", result.stderr)
        self.assertNotIn("[INFO] JSON report:", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

        awk = self.root / "bin/awk"
        awk.write_text("#!/bin/sh\necho 'formatter failure' >&2\nexit 7\n")
        awk.chmod(0o755)
        result = self.make("phploc", COLOR="always", PHPLOC_SAMPLE="1")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("formatter failure", result.stderr)
        self.assertNotIn("[INFO] JSON report:", result.stderr)

    def test_phploc_disables_xdebug_without_hiding_php_errors(self):
        for color in ("always", "never"):
            with self.subTest(color=color):
                result = self.make(
                    "phploc", COLOR=color, XDEBUG_MODE="debug", CHECK_VERSION_XDEBUG="1", PHPLOC_SAMPLE="1",
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertNotIn("Xdebug:", result.stderr)
                self.assertIn("Lines of Code", result.stdout)
                self.assertIn("[INFO] JSON report:", re.sub(r"\x1b\[[0-9;]*m", "", result.stderr))

                result = self.make(
                    "phploc", COLOR=color, XDEBUG_MODE="debug", CHECK_VERSION_XDEBUG="1", FAIL_COMMAND="php",
                )
                self.assertNotEqual(result.returncode, 0)
                self.assertNotIn("Xdebug:", result.stderr)
                self.assertIn("native failure diagnostic", result.stderr)
                self.assertNotIn("[INFO] JSON report:", result.stderr)
                self.assertNotIn("[OK]", result.stderr)

    def test_phploc_path_is_shown_only_after_generator_success(self):
        result = self.make("phploc")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue((self.root / "build/phploc.json").is_file())
        self.assertIn("[INFO] JSON report: build/phploc.json", result.stderr)
        result = self.make("phploc", FAIL_COMMAND="php")
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn("[INFO] JSON report:", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        report = self.root / "build/phploc.json"
        for empty in (False, True):
            if report.exists():
                report.unlink()
            if empty:
                report.touch()
            result = self.make("phploc", OMIT_REPORTS="1")
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("Required generated report is missing or empty: build/phploc.json", result.stderr)
            self.assertNotIn("[INFO] JSON report:", result.stderr)
            self.assertNotIn("[OK]", result.stderr)

    def test_report_preparation_dry_run_changes_nothing(self):
        result = self.make("--dry-run", "prepare-sonar-reports")
        self.assertEqual(result.returncode, 0)
        self.assertFalse((self.root / "build/sonar").exists())
        self.assertNotIn("[OK]", result.stderr)


if __name__ == "__main__":
    unittest.main()
