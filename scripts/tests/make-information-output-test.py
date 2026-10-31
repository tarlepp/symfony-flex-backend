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
                     "project-stats.sh", "docker-images.sh", "native-output.sh"):
            shutil.copyfile(ROOT / "scripts" / name, self.root / "scripts" / name)
            (self.root / "scripts" / name).chmod(0o755)
        fixture = """#!/usr/bin/env bash
printf '%s %s\\n' "$0" "$*" >> "$CALLS"
if [ "$(basename "$0")" = "${FAIL_COMMAND:-}" ]; then
    echo 'native failure diagnostic' >&2
    exit 7
fi
case "$(basename "$0")" in
    jq)
        if [[ "$*" == *dependencies* ]]; then exec /usr/bin/jq "$@"; fi
        printf '{"fixture": true}\\n' ;;
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
if [[ "$*" == *--log-json=* ]]; then printf '{}\\n' > build/phploc.json; fi
"""
        for name in ("bin/php", "bin/console", "bin/composer", "bin/jq"):
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
        self.assertEqual(result.stderr, "")
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
                self.assertNotRegex(result.stdout, r"\[(RUN|OK|INFO|FAIL)\]|\x1b")
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
        self.assertIn("[OK] Submit coverage to Coveralls", result.stderr)
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

    def test_report_preparation_dry_run_changes_nothing(self):
        result = self.make("--dry-run", "prepare-sonar-reports")
        self.assertEqual(result.returncode, 0)
        self.assertFalse((self.root / "build/sonar").exists())
        self.assertNotIn("[OK]", result.stderr)


if __name__ == "__main__":
    unittest.main()
