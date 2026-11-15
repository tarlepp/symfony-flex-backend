#!/usr/bin/env python3
"""Check information streams, help inventory and local report preparation offline."""

import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]


class InformationOutputTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="make-information-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        for directory in ("scripts", "bin", "vendor/bin", "build/logs", "src", "tests", "public", "migrations"):
            (self.root / directory).mkdir(parents=True)
        shutil.copyfile(ROOT / "Makefile", self.root / "Makefile")
        for name in ("command-output.sh", "make.sh", "make-help.sh", "prepare-sonar-reports.sh",
                     "project-stats.sh", "docker-images.sh", "native-output.sh", "report-output.sh",
                     "tool-versions.sh"):
            shutil.copyfile(ROOT / "scripts" / name, self.root / "scripts" / name)
            (self.root / "scripts" / name).chmod(0o755)
        shutil.copyfile(ROOT / "scripts/native-output.py", self.root / "scripts/native-output.py")
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
if [[ "$*" == *--log-json=* ]] && [ "${OMIT_REPORTS:-0}" != 1 ]; then printf '{}\\n' > build/phploc.json; fi
"""
        for name in ("bin/php", "bin/php-fpm", "bin/console", "bin/composer", "bin/jq",
                     "bin/python3", "bin/node", "bin/npm"):
            file = self.root / name
            file.write_text(fixture)
            file.chmod(0o755)
        docker = self.root / "bin/docker"
        docker.write_text("""#!/bin/sh
if [ "$1 $2 $3" = 'buildx imagetools inspect' ]; then
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
            ["bash", "scripts/make.sh", "APPLICATION_CONFIG=fixture.json",
             "INSIDE_DOCKER_CONTAINER=1", target, *arguments],
            cwd=self.root, env=environment, capture_output=True, text=True, check=False,
        )
        return result

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
        detector = self.root / "scripts/ci-detect-changes.sh"
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
        for target in ("configuration", "project-stats", "application-info", "tool-versions", "check-licenses"):
            with self.subTest(target=target):
                (self.root / target).touch()
                result = self.make(target, COLOR="always")
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertNotRegex(result.stdout, r"\[(RUN|OK|INFO|FAIL)\]")
                if target != "tool-versions":
                    self.assertNotIn("\x1b", result.stdout)
                self.assertEqual(result.stderr.count("[OK]"), 1)
                if target == "configuration":
                    self.assertEqual(json.loads(result.stdout), {"fixture": True})
                if target == "check-licenses":
                    self.assertEqual(
                        {line.strip() for line in result.stdout.splitlines()},
                        {"2 MIT", "1 MIT OR Apache-2.0", "1 UNSPECIFIED"},
                    )
                if target == "project-stats":
                    self.assertTrue(result.stdout.startswith("Extension"))
                    self.assertIn("Total:", result.stdout)

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
        (self.root / "src").rename(self.root / "missing-src")
        result = self.make("project-stats")
        self.assertNotEqual(result.returncode, 0)
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
                ["bash", "scripts/tool-versions.sh", "--stack", "docker", "compose"],
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
        script = self.root / "scripts/tool-versions.sh"
        script.write_text(script.read_text().replace(
            "[ ! -f /.dockerenv ]", "[ ! -f /fixture-no-container-marker ]"))

        for arguments, expected in (
            ([], "make tool-versions"),
            (["--production"], "docker compose -f compose.yaml -f compose.prod.yaml exec"),
        ):
            with self.subTest(arguments=arguments):
                result = subprocess.run(
                    ["bash", "scripts/tool-versions.sh", *arguments],
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
            ["bash", "scripts/tool-versions.sh", "--production"],
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
            ["bash", "scripts/tool-versions.sh", "--invalid"],
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
            ["/bin/bash", "scripts/tool-versions.sh"],
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
            ["/bin/bash", "scripts/tool-versions.sh"],
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
                self.assertIn("Platforms: linux/amd64, linux/arm64", result.stdout)

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
