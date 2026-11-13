#!/usr/bin/env python3
"""Exercise Docker maintenance workflow contracts without GitHub or registries."""

import hashlib
import os
from pathlib import Path
import re
import subprocess
import tempfile
import textwrap
import unittest


ROOT = Path(__file__).resolve().parents[2]
WORKFLOW = (ROOT / ".github/workflows/docker-image-updates.yml").read_text()
BOOTSTRAP = (ROOT / ".github/actions/php-bootstrap/action.yml").read_text()


def step(content, name):
    match = re.search(
        rf"^( +)- name: {re.escape(name)}\n(.*?)(?=^\1- |\Z)",
        content, re.MULTILINE | re.DOTALL,
    )
    if match is None:
        raise AssertionError(f"Missing step: {name}")
    return match.group(2)


def script(content, name):
    section = step(content, name)
    match = re.search(r"^ +run: \|\n(.*)", section, re.MULTILINE | re.DOTALL)
    if match is None:
        raise AssertionError(f"Missing shell body: {name}")
    return textwrap.dedent(match.group(1))


class DockerImageWorkflowTest(unittest.TestCase):
    def setUp(self):
        temporary = tempfile.TemporaryDirectory(prefix="docker-image-workflow-")
        self.addCleanup(temporary.cleanup)
        self.root = Path(temporary.name)
        self.output = self.root / "output"
        self.summary = self.root / "summary"
        self.environment = dict(
            os.environ, GITHUB_OUTPUT=str(self.output),
            GITHUB_STEP_SUMMARY=str(self.summary), RUNNER_TEMP=str(self.root),
        )

    def run_script(self, content, name, **overrides):
        environment = dict(self.environment, **overrides)
        return subprocess.run(
            ["bash", "-euo", "pipefail", "-c", script(content, name)],
            cwd=self.root, env=environment, text=True, capture_output=True,
            check=False, timeout=10,
        )

    def authentication(self, **overrides):
        values = dict(APP_CLIENT_ID="", APP_PRIVATE_KEY="", UPDATE_TOKEN="")
        values.update(overrides)
        return self.run_script(
            WORKFLOW, "Select pull request authentication", **values,
        )

    def test_schedule_manual_and_serialization_contracts(self):
        self.assertIn("  schedule:\n    - cron: '17 4 * * *'\n", WORKFLOW)
        self.assertIn("  workflow_dispatch:\n", WORKFLOW)
        self.assertIn("group: docker-image-updates-${{ github.ref }}", WORKFLOW)
        self.assertIn("cancel-in-progress: false", WORKFLOW)
        self.assertIn("if: ${{ github.ref_type == 'branch' }}", WORKFLOW)
        self.assertNotIn("pull_request_target", WORKFLOW)
        self.assertIn("persist-credentials: false", WORKFLOW)
        self.assertIn("if: ${{ always() }}", step(WORKFLOW, "Log out of Docker Hub"))
        self.assertIn(
            "if: ${{ always() }}", step(WORKFLOW, "Stop PHP development container"),
        )

    def test_app_precedes_pat_and_token_is_repository_scoped(self):
        result = self.authentication(
            APP_CLIENT_ID="example-client", APP_PRIVATE_KEY="example-key",
            UPDATE_TOKEN="example-pat",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.output.read_text(), "app=true\n")
        self.assertNotIn("example-key", result.stdout + result.stderr)
        action = step(WORKFLOW, "Create repository-scoped GitHub App token")
        self.assertIn("if: ${{ steps.authentication.outputs.app == 'true' }}", action)
        self.assertIn("permission-contents: write", action)
        self.assertIn("permission-pull-requests: write", action)
        self.assertNotIn("owner:", action)
        self.assertNotIn("skip-token-revoke:", action)
        self.assertRegex(action, r"actions/create-github-app-token@[a-f0-9]{40}")
        self.assertIn(
            "steps.app-token.outputs.token || secrets.DOCKER_IMAGE_UPDATE_TOKEN || github.token",
            step(WORKFLOW, "Create or update the Docker image pull request"),
        )

    def test_pat_avoids_fallback_warning(self):
        result = self.authentication(UPDATE_TOKEN="example-pat")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.output.read_text(), "app=false\n")
        self.assertNotIn("::warning::", result.stdout)
        self.assertFalse(self.summary.exists())

    def test_default_token_warns_in_logs_and_summary(self):
        result = self.authentication()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("::warning::Using GITHUB_TOKEN", result.stdout)
        self.assertIn("does not trigger PR CI", self.summary.read_text())

    def test_partial_app_configuration_fails_even_with_pat(self):
        for overrides in ({"APP_CLIENT_ID": "example"}, {"APP_PRIVATE_KEY": "example"}):
            with self.subTest(overrides=overrides):
                result = self.authentication(UPDATE_TOKEN="example-pat", **overrides)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("::error::Configure both", result.stdout)
                self.assertFalse(self.output.exists())

    def test_registry_login_precedes_all_builds(self):
        self.assertLess(
            WORKFLOW.index("- name: Authenticate to Docker Hub"),
            WORKFLOW.index("- name: Bootstrap maintenance container"),
        )
        binary = self.root / "docker"
        binary.write_text(
            '#!/bin/bash\n[ "$*" = "login docker.io --username example --password-stdin" ] || exit 64\n'
            'read -r token\n[ "$token" = example-token ] || exit 1\nexit "${LOGIN_EXIT:-0}"\n'
        )
        binary.chmod(0o755)
        for expected in (0, 7):
            result = self.run_script(
                WORKFLOW, "Authenticate to Docker Hub when credentials are available",
                DOCKERHUB_USERNAME="example", DOCKERHUB_TOKEN="example-token",
                PATH=f"{self.root}:{os.environ['PATH']}", LOGIN_EXIT=str(expected),
            )
            self.assertEqual(result.returncode, expected, result.stderr)
            self.assertNotIn("example-token", result.stdout + result.stderr)
        result = self.run_script(
            WORKFLOW, "Authenticate to Docker Hub when credentials are available",
            DOCKERHUB_USERNAME="example", DOCKERHUB_TOKEN="",
        )
        self.assertNotEqual(result.returncode, 0)

    def test_bootstrap_defaults_and_maintenance_guards(self):
        self.assertIn("    default: development", BOOTSTRAP)
        self.assertIn("target: ${{ inputs.build-target }}", BOOTSTRAP)
        self.assertIn("build-target: maintenance", WORKFLOW)
        self.assertIn(
            "inputs.build-target == 'development' && github.event_name != 'schedule'",
            BOOTSTRAP,
        )
        self.assertIn(
            "inputs.build-target == 'development' && github.event_name == 'push'",
            BOOTSTRAP,
        )
        values = dict(
            BUILD_TARGET="maintenance", INSTALL_DEPENDENCIES="false",
            CACHE_DEPENDENCIES="false", PRINT_VERSIONS="false",
        )
        result = self.run_script(BOOTSTRAP, "Validate bootstrap target", **values)
        self.assertEqual(result.returncode, 0, result.stderr)
        for key in ("INSTALL_DEPENDENCIES", "CACHE_DEPENDENCIES", "PRINT_VERSIONS"):
            result = self.run_script(
                BOOTSTRAP, "Validate bootstrap target", **dict(values, **{key: "true"}),
            )
            self.assertNotEqual(result.returncode, 0)
        result = self.run_script(
            BOOTSTRAP, "Validate bootstrap target",
            **dict(values, BUILD_TARGET="production"),
        )
        self.assertNotEqual(result.returncode, 0)
        result = self.run_script(
            BOOTSTRAP, "Validate bootstrap target", BUILD_TARGET="development",
            INSTALL_DEPENDENCIES="true", CACHE_DEPENDENCIES="true", PRINT_VERSIONS="true",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        dockerfile = (ROOT / "Dockerfile").read_text()
        maintenance_pin = re.search(r"^FROM (\S+) AS maintenance$", dockerfile, re.MULTILINE)
        application_pin = re.search(r"^FROM (\S+) AS base$", dockerfile, re.MULTILINE)
        self.assertEqual(maintenance_pin.group(1), application_pin.group(1))
        maintenance = dockerfile.split(" AS maintenance\n", 1)[1].split("\nFROM ", 1)[0]
        self.assertIn("jq make", maintenance)
        self.assertNotIn("install-php-extensions", maintenance)
        self.assertNotIn("nvm", maintenance)
        self.assertTrue(dockerfile.rsplit("\nFROM ", 1)[1].startswith("base AS production"))

    def test_pr_base_stable_branch_and_template(self):
        directory = self.root / ".github"
        directory.mkdir()
        template = (ROOT / ".github/pull_request_template.md").read_text()
        (directory / "pull_request_template.md").write_text(template)
        binary = self.root / "git"
        binary.write_text(
            '#!/bin/bash\n[ "$1" = diff ] || exit 64\nprintf "Dockerfile\\n"\n'
        )
        binary.chmod(0o755)
        branch = "feat(ci)/parent-branch"
        result = self.run_script(
            WORKFLOW, "Prepare pull request details", BASE_BRANCH=branch,
            PATH=f"{self.root}:{os.environ['PATH']}",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        suffix = hashlib.sha256(branch.encode()).hexdigest()[:16]
        self.assertEqual(
            self.output.read_text(), f"branch=chore(docker)/image-updates-{suffix}\n",
        )
        body = (self.root / "docker-image-pr.md").read_text()
        for line in template.splitlines():
            if line.startswith("#") or line.startswith("* ["):
                self.assertIn(line, body)
        self.assertIn("Dockerfile", body)
        pr = step(WORKFLOW, "Create or update the Docker image pull request")
        self.assertIn("base: ${{ github.ref_name }}", pr)
        self.assertIn("branch: ${{ steps.details.outputs.branch }}", pr)
        self.assertNotIn("branch-suffix:", pr)
        # Always invoke the action: it owns existing-PR reconciliation on no diff.
        self.assertNotIn("if:", pr)

    def test_pr_and_no_change_summaries(self):
        for url in ("", "https://github.com/example/repo/pull/1"):
            result = self.run_script(
                WORKFLOW, "Summarize pull request result",
                PR_URL=url, PR_OPERATION="created" if url else "",
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            summary = self.summary.read_text()
            self.assertIn(url if url else "no update PR was needed", summary)
            if not url:
                self.assertIn("core CI's Docker image version report", summary)

    def test_major_reporting_is_independent_of_automatic_updates(self):
        self.assertNotIn("check-updates", WORKFLOW)
        self.assertNotIn("continue-on-error:", WORKFLOW)
        self.assertIn("run: bash scripts/make.sh docker-image-update-pins", WORKFLOW)
        core = (ROOT / ".github/workflows/main.yml").read_text()
        report = re.search(
            r"^  docker-image-check-updates:\n(.*?)(?=^  [a-z][a-z-]*:\n|\Z)",
            core, re.MULTILINE | re.DOTALL,
        )
        self.assertIsNotNone(report)
        self.assertIn("github.event_name == 'schedule'", report.group(1))
        self.assertIn(
            "run: bash scripts/docker-images.sh check-updates --advisory",
            report.group(1),
        )
        self.assertIn("  workflow_dispatch:\n", core)


if __name__ == "__main__":
    unittest.main()
