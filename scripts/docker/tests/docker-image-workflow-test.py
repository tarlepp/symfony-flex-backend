#!/usr/bin/env python3
"""Test Docker image maintenance workflow wiring and selected steps offline."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make lint-workflows
#   To run only this suite from the repository root inside the container:
#     python3 scripts/docker/tests/docker-image-workflow-test.py
#
# Requirements:
#   Python 3, PyYAML, Bash, jq, and standard Linux command-line tools.
#
# Details:
#   The suite inspects checked-out workflow and action definitions. It runs
#   selected shell steps in temporary directories with fixture credentials and
#   stubbed external commands, without running hosted jobs or contacting registries.
#
# Callers:
#   Scripts:
#     - `scripts/checks/lint-workflows.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py`.

import hashlib
import itertools
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import textwrap
import unittest

import yaml


ROOT = Path(__file__).resolve().parents[3]
WORKFLOW = (ROOT / ".github/workflows/docker-image-updates.yml").read_text()
BOOTSTRAP = (ROOT / ".github/actions/php-bootstrap/action.yml").read_text()
CORE_WORKFLOW = (ROOT / ".github/workflows/main.yml").read_text()


def step(content, name):
    """Extract a named step up to the next step at the same indentation."""
    match = re.search(
        rf"^( +)- name: {re.escape(name)}\n(.*?)(?=^\1- |\Z)",
        content, re.MULTILINE | re.DOTALL,
    )
    if match is None:
        raise AssertionError(f"Missing step: {name}")
    return match.group(2)


def script(content, name):
    """Read an inline command or dedent a literal run block for Bash."""
    section = step(content, name)
    inline = re.search(r"^ +run: ([^|\n].*)$", section, re.MULTILINE)
    if inline is not None:
        return inline.group(1)
    match = re.search(r"^ +run: \|\n(.*)", section, re.MULTILINE | re.DOTALL)
    if match is None:
        raise AssertionError(f"Missing shell body: {name}")
    return textwrap.dedent(match.group(1))


class DockerImageWorkflowTest(unittest.TestCase):
    def setUp(self):
        temporary = tempfile.TemporaryDirectory(prefix="docker-image-workflow-")
        self.addCleanup(temporary.cleanup)
        self.root = Path(temporary.name)
        (self.root / "scripts/ci").mkdir(parents=True)
        (self.root / "scripts/ci/ci-validate-bootstrap.sh").write_text(
            (ROOT / "scripts/ci/ci-validate-bootstrap.sh").read_text(),
        )
        (self.root / "scripts/ci/ci-workflow-overview.sh").write_text(
            (ROOT / "scripts/ci/ci-workflow-overview.sh").read_text(),
        )
        (self.root / "scripts/ci/ci-docker-hub-auth.sh").write_text(
            (ROOT / "scripts/ci/ci-docker-hub-auth.sh").read_text(),
        )
        (self.root / "scripts/docker").mkdir(parents=True)
        for name in (
            "sync-doctrine-mariadb.sh", "sync-ci-mariadb.sh",
            "prepare-php-minor-pr.sh", "prepare-image-update-pr.sh",
            "select-maintenance-pr-auth.sh",
            "php-minor-pr-outcome.sh", "image-update-overview.sh",
        ):
            (self.root / "scripts/docker" / name).write_bytes((ROOT / "scripts/docker" / name).read_bytes())
        (self.root / "scripts/docker/docker-image-digest-summary.py").write_text(
            (ROOT / "scripts/docker/docker-image-digest-summary.py").read_text(),
        )
        (self.root / "scripts/docker/php-minor-readiness-report.py").write_text(
            (ROOT / "scripts/docker/php-minor-readiness-report.py").read_text(),
        )
        (self.root / "scripts/docker/php-minor-issues.py").write_text(
            (ROOT / "scripts/docker/php-minor-issues.py").read_text(),
        )
        (self.root / ".github").mkdir(exist_ok=True)
        (self.root / ".github/pull_request_template.md").write_text(
            (ROOT / ".github/pull_request_template.md").read_text(),
        )
        self.output = self.root / "output"
        self.summary = self.root / "summary"
        self.environment = dict(
            os.environ, GITHUB_OUTPUT=str(self.output),
            GITHUB_STEP_SUMMARY=str(self.summary), RUNNER_TEMP=str(self.root),
            PHP_READINESS_REPORT="", PHP_READINESS_SUMMARY="", PHP_READINESS_RESULT="success",
            PHP_CLOSING_ISSUES="",
        )

    def run_script(self, content, name, **overrides):
        """Run one checked-out step body with fixture paths and inputs."""
        environment = dict(self.environment, **overrides)
        return subprocess.run(
            ["bash", "-euo", "pipefail", "-c", script(content, name)],
            cwd=self.root, env=environment, text=True, capture_output=True,
            check=False, timeout=10,
        )

    def authentication(self, **overrides):
        """Start with empty credentials so each case controls the auth method."""
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

    def test_php_minor_readiness_is_separate_and_update_waits_without_failure_gating(self):
        jobs = yaml.safe_load(WORKFLOW)["jobs"]
        readiness = jobs["php-minor-readiness"]
        self.assertNotIn("needs", readiness)
        self.assertEqual(jobs["update"]["needs"], "php-minor-readiness")
        self.assertEqual(readiness["permissions"], {"contents": "read", "issues": "write"})
        self.assertIn("!cancelled() && github.ref_type == 'branch'", jobs["update"]["if"])
        self.assertEqual(readiness["steps"][1]["with"], {
            "ref": "${{ github.ref_name }}", "persist-credentials": False,
        })
        self.assertIn("bash scripts/docker/docker-images.sh check-php-minor --prepare", readiness["steps"][2]["run"])
        steps = {item.get("id"): item for item in readiness["steps"]}
        self.assertEqual(steps["php-minor"]["shell"], "bash")
        self.assertNotIn("continue-on-error", steps["php-minor"])
        self.assertIn("!cancelled()", steps["readiness-report"]["if"])
        self.assertIn("steps.php-minor.outcome != 'skipped'", steps["readiness-report"]["if"])
        self.assertEqual(readiness["outputs"]["report"], "${{ steps.readiness-report.outputs.report }}")
        self.assertEqual(readiness["outputs"]["summary-outcome"], "${{ steps.readiness-report.outcome }}")
        self.assertNotIn("GITHUB_STEP_SUMMARY", steps["php-minor"].get("env", {}))
        self.assertIn(
            'GITHUB_STEP_SUMMARY="$RUNNER_TEMP/php-minor-readiness.md"', steps["php-minor"]["run"],
        )
        self.assertIn("steps.php-minor.outputs.prepared == 'true'", steps["minor-auth"]["if"])
        self.assertIn("steps.php-minor.outcome == 'success'", steps["minor-issues"]["if"])
        self.assertIn("steps.minor-auth.outputs.method != ''", steps["minor-details"]["if"])
        self.assertIn("steps.minor-details.outcome == 'success'", steps["minor-pr"]["if"])
        self.assertNotIn("github.token", steps["minor-pr"]["with"]["token"])
        self.assertNotIn("docker/mariadb", steps["minor-pr"]["with"]["add-paths"])

    def php_minor_fixture(self):
        """Prepare local registry and Docker stand-ins for the readiness check."""
        for path in ("docker/docker-images.sh", "output/command-output.sh"):
            source = ROOT / "scripts" / path
            destination = self.root / "scripts" / path
            destination.parent.mkdir(parents=True, exist_ok=True)
            destination.write_bytes(source.read_bytes())

        for directory in (self.root, self.root / "tools/analyzer", self.root / "tools/linter"):
            directory.mkdir(parents=True, exist_ok=True)
            for name in ("composer.json", "composer.lock"):
                (directory / name).write_text("{}\n")
        dockerfile = self.root / "Dockerfile"
        dockerfile.write_text(f"FROM php:8.5.13-fpm-trixie@sha256:{'b' * 64} AS php-upstream\n")

        tags = [
            "8.5.14-fpm-trixie", "8.6.1-fpm-trixie", "8.6.2-fpm-trixie",
            "8.6.99-fpm-alpine", "8.6.99RC1-fpm-trixie",
            "8.7.0-fpm-trixie", "9.0.0-fpm-trixie",
        ]
        (self.root / "tags").write_text(json.dumps({
            "next": None, "results": [{"name": tag} for tag in tags],
        }))
        bin_dir = self.root / "bin"
        bin_dir.mkdir()
        curl = bin_dir / "curl"
        curl.write_text("#!/bin/bash\ncat tags\n")
        curl.chmod(0o755)
        docker = bin_dir / "docker"
        docker.write_text(textwrap.dedent("""\
            #!/bin/bash
            set -eu
            printf '%s\\n' "$*" >> docker-calls
            case "$*" in
                "buildx version"|"info --format "*|"image inspect "*|"image rm "*) ;;
                "buildx imagetools inspect php:8.6.2-fpm-trixie --format "*)
                    if [[ "$MODE" == drift && -f refreshed ]]; then
                        printf 'sha256:%064d\\n' 1
                    else
                        printf 'sha256:%064d\\n' 0
                    fi
                    ;;
                "buildx imagetools inspect --raw "*)
                    extra='{"platform":{"os":"linux","architecture":"arm64"}}'
                    if [[ "$MODE" == platform-loss && "$*" == *"php:8.6.2"* ]]; then
                        extra='{"platform":{"os":"linux","architecture":"386"}}'
                    fi
                    printf '%s\\n' '{"mediaType":"application/vnd.oci.image.index.v1+json",
                         "manifests":[{"platform":{"os":"linux","architecture":"amd64"}},
                                      '"$extra"']}'
                    ;;
                "buildx build --progress plain --file - --target development --load --tag "*. )
                    cat > candidate-Dockerfile
                    if [[ "$MODE" == build-failure ]]; then
                        printf '%s\\n' "#18 15.63 /tmp/pear/temp/apcu/apc.c:44:17: error: too many arguments to function 'php_verror'"
                        printf '%s\\n' 'ERROR: failed to build: process install-php-extensions exited 1'
                        exit 1
                    fi
                    ;;
                "run --rm --pull=never --network none "*)
                    [[ "$*" == *" --no-interaction --no-plugins --no-scripts check-platform-reqs --lock --format=json" ]]
                    if [[ "$*" == *" --working-dir=/app/tools/analyzer "* ]]; then
                        case "$MODE" in
                            blocked)
                                printf '%s\\n' '[{"status":"failed","failed_requirement":{"source":"tool/analyzer"}}]'
                                exit 1 ;;
                            incomplete) exit 125 ;;
                        esac
                    fi
                    printf '%s\\n' '[{"name":"php","status":"success"}]'
                    ;;
                "run --rm --pull=never -i "*)
                    [[ "$*" == *" /app/scripts/sync-composer-php.sh --next-minor" ]]
                    for arg in "$@"; do
                        if [[ "$arg" == type=bind,source=*,target=/proposal ]]; then
                            proposal="${arg#type=bind,source=}"
                            proposal="${proposal%,target=/proposal}"
                        fi
                    done
                    grep -q 'php:8.5.13' /dev/stdin
                    printf 'updated\\n' > "$proposal/tools/analyzer/composer.lock"
                    touch refreshed
                    if [[ "$MODE" == sync-failure ]]; then exit 1; fi
                    ;;
                *) echo "Unexpected Docker invocation: $*" >&2; exit 99 ;;
            esac
            """))
        docker.chmod(0o755)
        return dockerfile, f"{bin_dir}:{os.environ['PATH']}"

    def test_php_minor_readiness_prepares_files_only_when_all_checks_pass(self):
        dockerfile, path = self.php_minor_fixture()
        self.summary = self.root / "php-minor-readiness.md"
        original = dockerfile.read_text()
        cases = {
            "ready": (0, "Prepared for a migration PR"),
            "blocked": (0, "Blocked: locked platform requirements"),
            "incomplete": (1, "Check failed"),
            "sync-failure": (1, "Check failed"),
            "drift": (1, "Check failed"),
            "platform-loss": (1, "Check failed"),
            "build-failure": (0, "Blocked: candidate development image did not build"),
            "absent": (0, "Not available"),
            "registry-error": (1, "Check failed"),
        }
        name = "Check the next PHP minor against locked platform requirements"
        for mode, (status, result_text) in cases.items():
            with self.subTest(mode=mode):
                (self.root / "docker-calls").write_text("")
                (self.root / "refreshed").unlink(missing_ok=True)
                dockerfile.write_text(original)
                lockfile = self.root / "tools/analyzer/composer.lock"
                lockfile.write_text("{}\n")
                # Each scenario must earn its own prepared output and summary.
                self.output.write_text("")
                self.summary.write_text("")
                if mode == "absent":
                    (self.root / "tags").write_text(json.dumps({
                        "next": None, "results": [
                            {"name": "8.5.20-fpm-trixie"}, {"name": "8.7.0-fpm-trixie"},
                        ],
                    }))
                elif mode == "registry-error":
                    (self.root / "tags").write_text("invalid JSON")
                result = self.run_script(WORKFLOW, name, PATH=path, MODE=mode)
                self.assertEqual(result.returncode, status, result.stdout + result.stderr)
                self.assertIn(f"**Result: {result_text}", self.summary.read_text())
                calls = (self.root / "docker-calls").read_text()
                if mode == "ready":
                    self.assertEqual(self.output.read_text(), "prepared=true\ncandidate=php:8.6.2-fpm-trixie\n")
                    self.assertIn("php:8.6.2-fpm-trixie", dockerfile.read_text())
                    self.assertEqual(lockfile.read_text(), "updated\n")
                else:
                    self.assertEqual(self.output.read_text(), "")
                    self.assertEqual(dockerfile.read_text(), original)
                    self.assertEqual(lockfile.read_text(), "{}\n")

                if mode in {"absent", "registry-error"}:
                    self.assertNotIn("imagetools inspect", calls)
                    self.assertNotIn("buildx build", calls)
                    continue
                self.assertIn("Candidate: `php:8.6.2-fpm-trixie`", self.summary.read_text())
                if mode == "platform-loss":
                    self.assertIn("would remove platform linux/arm64", result.stdout + result.stderr)
                    self.assertNotIn("buildx build", calls)
                    continue
                self.assertEqual(
                    (self.root / "candidate-Dockerfile").read_text(),
                    f"FROM php:8.6.2-fpm-trixie@sha256:{'0' * 64} AS php-upstream\n",
                )
                self.assertIn("image rm symfony-php-minor-readiness:", calls)
                if mode == "build-failure":
                    self.assertNotIn("check-platform-reqs", calls)
                    self.assertIn("Composer checks did not run.", self.summary.read_text())
                    self.assertIn("Reason: <code>/tmp/pear/temp/apcu/apc.c", self.summary.read_text())
                    self.assertIn("too many arguments to function", self.summary.read_text())
                    continue
                for project in (".", "tools/analyzer", "tools/linter"):
                    self.assertIn(f"--working-dir=/app/{project} ", calls)
                if mode == "blocked":
                    self.assertIn("tool/analyzer", result.stdout)
                    self.assertIn("| `tools/analyzer` | Blocked:", self.summary.read_text())
                if mode in {"ready", "sync-failure", "drift"}:
                    self.assertIn("sync-composer-php.sh --next-minor", calls)
                else:
                    self.assertNotIn("sync-composer-php.sh --next-minor", calls)

    def test_php_minor_pr_authentication_requires_ci_capable_credentials(self):
        for app_id, key, pat, exit_code, output in (
            ("id", "key", "pat", 0, "method=app\n"),
            ("", "", "pat", 0, "method=pat\n"),
            ("", "", "", 0, ""),
            ("id", "", "pat", 1, ""),
        ):
            self.output.write_text("")
            self.summary.write_text("")
            result = self.run_script(
                WORKFLOW, "Select PHP minor PR authentication",
                APP_CLIENT_ID=app_id, APP_PRIVATE_KEY=key, UPDATE_TOKEN=pat,
            )
            self.assertEqual(result.returncode, exit_code, result.stderr)
            self.assertEqual(self.output.read_text(), output)
            if not output:
                self.assertIn("No PHP migration PR:", self.summary.read_text())

    def test_maintenance_authentication_matrix_and_output_failures(self):
        workflows = yaml.safe_load(WORKFLOW)["jobs"]
        minor = {item.get("id"): item for item in workflows["php-minor-readiness"]["steps"]}
        update = {item.get("id"): item for item in workflows["update"]["steps"]}
        self.assertEqual(minor["minor-auth"]["run"], "bash scripts/docker/select-maintenance-pr-auth.sh php-minor")
        self.assertEqual(update["authentication"]["run"], "bash scripts/docker/select-maintenance-pr-auth.sh image-update")
        self.assertEqual(minor["minor-auth"]["env"], update["authentication"]["env"])
        self.assertEqual(minor["minor-auth"]["if"], "${{ steps.php-minor.outputs.prepared == 'true' }}")
        self.assertEqual(minor["minor-token"]["if"], "${{ steps.minor-auth.outputs.method == 'app' }}")
        self.assertEqual(minor["minor-details"]["if"], "${{ steps.minor-auth.outputs.method != '' }}")
        self.assertEqual(update["app-token"]["if"], "${{ steps.authentication.outputs.app == 'true' }}")
        self.assertEqual(
            minor["minor-pr"]["with"]["token"],
            "${{ steps.minor-token.outputs.token || secrets.DOCKER_IMAGE_UPDATE_TOKEN }}",
        )
        self.assertEqual(
            update["pull-request"]["with"]["token"],
            "${{ steps.app-token.outputs.token || secrets.DOCKER_IMAGE_UPDATE_TOKEN || github.token }}",
        )

        for mode, name in (
            ("php-minor", "Select PHP minor PR authentication"),
            ("image-update", "Select pull request authentication"),
        ):
            for has_id, has_key, has_pat in itertools.product((False, True), repeat=3):
                with self.subTest(mode=mode, has_id=has_id, has_key=has_key, has_pat=has_pat):
                    self.output.unlink(missing_ok=True)
                    self.summary.unlink(missing_ok=True)
                    credentials = {
                        "APP_CLIENT_ID": "fixture-client" if has_id else "",
                        "APP_PRIVATE_KEY": "fixture-private-key" if has_key else "",
                        "UPDATE_TOKEN": "fixture-pat" if has_pat else "",
                    }
                    result = self.run_script(WORKFLOW, name, **credentials)
                    output = self.output.read_text() if self.output.exists() else ""
                    summary = self.summary.read_text() if self.summary.exists() else ""
                    partial = has_id != has_key
                    self.assertEqual(result.returncode, 1 if partial else 0, result.stderr)
                    expected = ""
                    if not partial and has_id:
                        expected = "method=app\n" if mode == "php-minor" else "app=true\n"
                    elif not partial and has_pat:
                        expected = "method=pat\n" if mode == "php-minor" else "app=false\n"
                    elif not partial and mode == "image-update":
                        expected = "app=false\n"
                    self.assertEqual(output, expected)
                    if partial:
                        self.assertIn("configure both", (summary + result.stdout).lower())
                    elif not has_id and not has_pat:
                        self.assertIn(
                            "No PHP migration PR:" if mode == "php-minor" else "does not trigger PR CI",
                            summary,
                        )
                    else:
                        self.assertEqual(summary, "")
                    for secret in ("fixture-client", "fixture-private-key", "fixture-pat"):
                        self.assertNotIn(secret, result.stdout + result.stderr + output + summary)

            for overrides in (
                {"APP_CLIENT_ID": "fixture-client", "APP_PRIVATE_KEY": "fixture-private-key",
                 "GITHUB_OUTPUT": str(self.root)},
                {"GITHUB_STEP_SUMMARY": str(self.root)},
            ):
                values = {"APP_CLIENT_ID": "", "APP_PRIVATE_KEY": "", "UPDATE_TOKEN": ""}
                values.update(overrides)
                result = self.run_script(WORKFLOW, name, **values)
                self.assertNotEqual(result.returncode, 0)
                self.assertTrue(result.stderr)

        helper = self.root / "scripts/docker/select-maintenance-pr-auth.sh"
        for arguments in ([], ["unknown"], ["php-minor", "extra"]):
            result = subprocess.run(
                ["bash", str(helper), *arguments], cwd=self.root, env=self.environment,
                text=True, capture_output=True, check=False, timeout=10,
            )
            self.assertEqual(result.returncode, 2)
            self.assertIn("Usage:", result.stderr)

    def test_php_minor_issue_tracking_uses_real_workflow_step(self):
        gh = self.root / "gh"
        gh.write_text(textwrap.dedent("""\
            #!/usr/bin/env python3
            import json, os, sys
            from pathlib import Path
            calls = Path("gh-calls")
            with calls.open("a") as out:
                out.write(json.dumps(sys.argv[1:]) + "\\n")
            if os.environ.get("GH_FAIL") == "1":
                print("API unavailable", file=sys.stderr)
                sys.exit(1)
            if "--paginate" in sys.argv:
                print(Path("issue-pages").read_text())
            elif "--method" in sys.argv:
                Path("created-issue").write_text(sys.stdin.read())
                print('{"number": 42}')
            else:
                sys.exit(2)
        """))
        gh.chmod(0o755)
        marker = (
            f"<!-- php-minor-blocker:{hashlib.sha256(b'master').hexdigest()}:"
            "8.6-fpm-trixie -->"
        )
        source = self.root / "php-minor-readiness.md"
        log = self.root / "php-minor-readiness.log"
        calls = self.root / "gh-calls"
        pages = self.root / "issue-pages"
        created = self.root / "created-issue"
        env = dict(
            PATH=f"{self.root}:{os.environ['PATH']}", BASE_BRANCH="master",
            CHECK_OUTCOME="success", GITHUB_SERVER_URL="https://github.com",
            GITHUB_REPOSITORY="owner/repo", GITHUB_RUN_ID="123",
        )

        def run(result_text, existing, error="", **overrides):
            source.write_text(
                "Candidate: `php:8.6.2-fpm-trixie`\n\n"
                "| `tools/analyzer` | Blocked: package/tool requires php <8.6 |\n\n"
                f"**Result: {result_text}**\n"
            )
            log.write_text(error)
            pages.write_text(json.dumps(existing))
            calls.unlink(missing_ok=True)
            created.unlink(missing_ok=True)
            self.output.write_text("")
            self.summary.write_text("")
            published = self.run_script(
                WORKFLOW, "Publish PHP readiness evidence", CHECK_OUTCOME=overrides.get("CHECK_OUTCOME", "success"),
            )
            self.assertEqual(published.returncode, 0, published.stderr)
            report_match = re.search(
                r"(?ms)^report<<([^\n]+)\n(.*?)\n\1$", self.output.read_text(),
            )
            assert report_match is not None, "Readiness publisher must emit a report output"
            report = report_match.group(2)
            self.output.write_text("")
            self.summary.write_text("")
            return self.run_script(
                WORKFLOW, "Track confirmed PHP minor blockers",
                **dict(env, PHP_READINESS_REPORT=report, **overrides),
            )

        blocked = "Blocked: locked platform requirements are not satisfied"
        result = run(blocked, [[], [{"number": 90, "body": "unrelated"}]])
        self.assertEqual(result.returncode, 0, result.stderr)
        payload = json.loads(created.read_text())
        self.assertIn(marker, payload["body"])
        self.assertIn("owner/repo/actions/runs/123", payload["body"])
        self.assertIn("<code>master</code>", payload["body"])
        self.assertIn(blocked, payload["body"])
        self.assertIn("Composer project `tools/analyzer`", payload["body"])
        self.assertIn("package/tool requires php <8.6", payload["body"])
        self.assertIn("#42", self.summary.read_text())
        result = run(blocked, [[{"number": 42, "body": marker}]])
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(created.exists())
        self.assertEqual(len(calls.read_text().splitlines()), 1)
        source.write_text(source.read_text().replace("8.6.2", "8.6.9"))
        patch_release = self.run_script(WORKFLOW, "Track confirmed PHP minor blockers", **env)
        self.assertEqual(patch_release.returncode, 0, patch_release.stderr)
        self.assertFalse(created.exists())
        result = run(blocked, [[{"number": 42, "body": marker}]], BASE_BRANCH="other")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(created.exists())

        result = run("Prepared for a migration PR", [
            [{"number": 100, "body": marker, "pull_request": {}}],
            [{"number": 42, "body": marker}, {"number": 43, "body": marker}],
        ])
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("Closes #42\nCloses #43", self.output.read_text())
        self.assertNotIn("#100", self.output.read_text())
        self.assertFalse(created.exists())

        for result_text, error, outcome in (
            ("Blocked: candidate development image did not build", "ERROR: registry timeout", "success"),
            ("Check failed", "", "failure"),
            ("Not available", "", "success"),
        ):
            result = run(result_text, [[]], error, CHECK_OUTCOME=outcome)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertFalse(calls.exists())
        result = run(
            "Blocked: candidate development image did not build", [[]],
            "/tmp/pear/temp/apcu/apc.c:44: error: incompatible PHP API",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(created.exists())
        payload = json.loads(created.read_text())
        self.assertIn("PHP extension `apcu` failed to compile", payload["body"])
        self.assertIn("incompatible PHP API", payload["body"])
        self.assertIn("Composer compatibility checks did not run", payload["body"])
        result = run(blocked, [[]], GH_FAIL="1")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("API unavailable", result.stderr)
        result = run(blocked, [{"unexpected": "page"}])
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Unexpected GitHub issue listing", result.stderr)
        source.unlink()
        result = self.run_script(WORKFLOW, "Track confirmed PHP minor blockers", **env)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("PHP minor issue tracking failed", result.stderr)

    def test_readiness_publication_shares_evidence_with_different_prominence(self):
        source = self.root / "php-minor-readiness.md"
        log = self.root / "php-minor-readiness.log"
        cases = (
            ("Blocked: candidate development image did not build", "success", True),
            ("Blocked: locked platform requirements are not satisfied", "success", True),
            ("Prepared for a migration PR", "success", False),
            ("Not available", "success", False),
            ("Check failed", "failure", True),
            ("Prepared for a migration PR", "failure", True),
            ("", "success", True),
        )
        for result_text, outcome, attention in cases:
            with self.subTest(result=result_text, outcome=outcome):
                source.unlink(missing_ok=True)
                log.unlink(missing_ok=True)
                self.output.write_text("")
                self.summary.write_text("")
                if result_text:
                    source.write_text(
                        "## PHP minor readiness\n\nCurrent: `php:8.5.13-fpm-trixie`\n\n"
                        "Candidate: `php:8.6.2-fpm-trixie`\n\n"
                        "| Composer project | Platform requirements |\n| --- | --- |\n"
                        "| `tools/analyzer` | Blocked: package/tool requires php <8.6 |\n\n"
                        f"**Result: {result_text}**\n",
                    )
                    log.write_text("ERROR: <upstream> failed </details>\n" * 10)
                published = self.run_script(
                    WORKFLOW, "Publish PHP readiness evidence", CHECK_OUTCOME=outcome,
                )
                self.assertEqual(published.returncode, 0, published.stdout + published.stderr)
                matches = re.findall(
                    r"(?m)^(report|actions-report)<<([^\n]+)\n(.*?)\n\2\n",
                    self.output.read_text(), re.DOTALL,
                )
                outputs = {name: value for name, _, value in matches}
                compact = outputs["report"]
                actions = outputs["actions-report"]
                self.assertEqual(self.summary.read_text(), actions)
                self.assertNotIn("<details open>", compact)
                self.assertEqual("> [!WARNING]" in actions, attention)
                self.assertNotIn("<details open>", actions)
                normalized_actions = re.sub(r"> \[!WARNING\]\n> [^\n]+\n\n", "", actions)
                self.assertEqual(normalized_actions.replace("<details open>", "<details>"), compact)
                self.assertIn("## PHP minor upgrade readiness", compact)
                if result_text:
                    self.assertIn("Candidate: `php:8.6.2-fpm-trixie`", compact)
                    self.assertIn("package/tool requires php <8.6", compact)
                    self.assertIn("&lt;upstream&gt; failed &lt;/details&gt;", compact)
                    self.assertNotIn("ERROR: <upstream>", compact)
                    self.assertIn("Reason: Composer project `tools/analyzer`", compact)
                else:
                    self.assertIn("Unavailable; no readiness result was produced", compact)
                if outcome == "failure":
                    self.assertIn("Check failed; PHP minor readiness is incomplete", compact)

    def test_readiness_fallback_is_explicit_in_pr_and_actions(self):
        git = self.root / "git"
        git.write_text("#!/bin/bash\n[ \"$1\" = diff ] || exit 64\n")
        git.chmod(0o755)
        for job_result in ("failure", "skipped", "success"):
            result = self.run_script(
                WORKFLOW, "Prepare pull request details", BASE_BRANCH="master",
                PATH=f"{self.root}:{os.environ['PATH']}", PHP_READINESS_RESULT=job_result,
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            body = (self.root / "docker-image-pr.md").read_text()
            self.assertIn("**Result: Unavailable**", body)
            self.assertIn(f"readiness job: {job_result}", body)
            self.assertIn("No compatibility conclusion can be drawn", body)

    def test_build_blocker_without_diagnostics_is_explicit(self):
        source = self.root / "php-minor-readiness.md"
        source.write_text("**Result: Blocked: candidate development image did not build**\n")
        for log_text, expected in (
            ("", "No detailed diagnostic was captured"),
            ("ERROR: generic build wrapper failed", "generic build wrapper failed"),
        ):
            with self.subTest(log=log_text):
                (self.root / "php-minor-readiness.log").write_text(log_text)
                self.output.write_text("")
                result = self.run_script(
                    WORKFLOW, "Publish PHP readiness evidence", CHECK_OUTCOME="success",
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn(expected, self.output.read_text())
                self.assertIn(
                    "The failing extension could not be identified from compiler error source paths",
                    self.summary.read_text(),
                )

    def test_build_error_survives_runner_summary_handoff_into_pr(self):
        _, path = self.php_minor_fixture()
        check = self.run_script(
            WORKFLOW, "Check the next PHP minor against locked platform requirements",
            PATH=path, MODE="build-failure",
        )
        self.assertEqual(check.returncode, 0, check.stdout + check.stderr)
        raw = self.root / "php-minor-readiness.md"
        self.assertTrue(raw.is_file(), "Readiness evidence must not use the runner-owned summary path")
        self.assertFalse(self.summary.exists(), "Only the publisher should write the Actions summary")
        published = self.run_script(
            WORKFLOW, "Publish PHP readiness evidence", CHECK_OUTCOME="success",
        )
        self.assertEqual(published.returncode, 0, published.stderr)
        outputs = {
            name: value for name, _, value in re.findall(
                r"(?m)^(report|actions-report)<<([^\n]+)\n(.*?)\n\2\n",
                self.output.read_text(), re.DOTALL,
            )
        }
        git = self.root / "git"
        git.write_text("#!/bin/bash\n[ \"$1\" = diff ] || exit 64\n")
        git.chmod(0o755)
        details = self.run_script(
            WORKFLOW, "Prepare pull request details", BASE_BRANCH="master",
            PATH=f"{self.root}:{os.environ['PATH']}", PHP_READINESS_REPORT=outputs["report"],
        )
        self.assertEqual(details.returncode, 0, details.stderr)
        body = (self.root / "docker-image-pr.md").read_text()
        for content in (body, self.summary.read_text()):
            self.assertNotIn("Unavailable", content)
            self.assertIn("Candidate: `php:8.6.2-fpm-trixie`", content)
            self.assertIn("**Build error:**", content)
            self.assertIn("PHP extension `apcu` failed to compile", content)
            self.assertIn(
                "`php_verror` is called with more arguments than the candidate PHP API accepts",
                content,
            )
            self.assertIn("Composer compatibility checks did not run", content)
            self.assertIn("too many arguments to function", content)
            self.assertIn("php_verror", content)
        self.assertIn(outputs["report"], body)
        self.assertIn("**Build error:**", body.split("<details>", 1)[0])
        self.assertNotIn("<details open>", body)
        self.assertNotIn("<details open>", self.summary.read_text())

    def test_extension_errors_are_grouped_without_truncating_full_diagnostics(self):
        source = self.root / "php-minor-readiness.md"
        source.write_text("**Result: Blocked: candidate development image did not build**\n")
        errors = [
            f"#18 15.63 /tmp/pear/temp/apcu/apc.c:44:17: error: failure {number}"
            for number in range(7)
        ]
        long_error = "/usr/src/php/ext/intl/intl.c:10:2: fatal error: " + "x" * 650 + " <end>"
        log = "\n".join([
            "Installing zip and bcmath",
            "/tmp/pear/temp/igbinary/test.c:1: note: not a compiler error",
            *errors, errors[0], long_error,
            "ERROR: process install-php-extensions apcu zip bcmath failed",
        ])
        (self.root / "php-minor-readiness.log").write_text(log)
        result = self.run_script(
            WORKFLOW, "Publish PHP readiness evidence", CHECK_OUTCOME="success",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        report = self.summary.read_text()
        headline, details = report.split("<details>", 1)
        self.assertIn("| `apcu` |", headline)
        self.assertIn("| `intl` |", headline)
        self.assertNotIn("| `zip` |", headline)
        self.assertNotIn("| `bcmath` |", headline)
        self.assertNotIn("| `igbinary` |", headline)
        self.assertIn("Other extensions are not confirmed compatible", headline)
        self.assertIn("failure 6", details)
        self.assertIn("x" * 650 + " &lt;end&gt;", details)
        self.assertEqual(details.count("apc.c:44:17: error: failure 0"), 1)
        self.assertNotIn("<details open>", report)

    def test_php_minor_pr_details_use_template_and_separate_branch(self):
        (self.root / ".github").mkdir(exist_ok=True)
        template = ROOT / ".github/pull_request_template.md"
        (self.root / ".github/pull_request_template.md").write_bytes(template.read_bytes())
        result = self.run_script(
            WORKFLOW, "Prepare PHP minor PR details",
            BASE_BRANCH="master", PHP_CANDIDATE="php:8.6.2-fpm-trixie",
            PHP_READINESS_REPORT="## PHP minor upgrade readiness\n\nPrepared evidence",
            PHP_CLOSING_ISSUES="Closes #42\nCloses #43",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.output.read_text(),
                         f"branch=chore(docker)/php-minor-{hashlib.sha256(b'master').hexdigest()[:16]}\n")
        body = (self.root / "php-minor-pr.md").read_text()
        self.assertIn("Update PHP to `php:8.6.2-fpm-trixie`", body)
        self.assertIn("## PHP minor upgrade readiness\n\nPrepared evidence", body)
        self.assertIn("Closes #42\nCloses #43", body)
        for line in template.read_text().splitlines():
            if line.startswith(("#", "* [ ]")):
                self.assertIn(line, body)

        self.output.write_text("")
        result = self.run_script(
            WORKFLOW, "Prepare PHP minor PR details",
            BASE_BRANCH="feature/parent", PHP_CANDIDATE="php:8.6.2-fpm-trixie",
            PHP_READINESS_REPORT="Readiness evidence", PHP_CLOSING_ISSUES="",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        suffix = hashlib.sha256(b"feature/parent").hexdigest()[:16]
        self.assertEqual(self.output.read_text(), f"branch=chore(docker)/php-minor-{suffix}\n")
        body = (self.root / "php-minor-pr.md").read_text()
        self.assertIn("Readiness evidence", body)
        self.assertNotIn("Closes #", body)

    def test_pr_preparation_wiring_and_failures(self):
        jobs = yaml.safe_load(WORKFLOW)["jobs"]
        minor = {item.get("id"): item for item in jobs["php-minor-readiness"]["steps"]}
        update = {item.get("id"): item for item in jobs["update"]["steps"]}
        self.assertEqual(minor["minor-details"]["run"], "bash scripts/docker/prepare-php-minor-pr.sh")
        self.assertEqual(update["details"]["run"], "bash scripts/docker/prepare-image-update-pr.sh")
        self.assertEqual(minor["minor-pr"]["with"]["body-path"], "${{ runner.temp }}/php-minor-pr.md")
        self.assertEqual(update["pull-request"]["with"]["body-path"], "${{ runner.temp }}/docker-image-pr.md")

        git = self.root / "git"
        git.write_text(textwrap.dedent("""\
            #!/bin/bash
            [[ "$1" == diff && "$2" == --no-ext-diff ]] || exit 64
            if [[ "$3" == --stat=160,120 ]]; then
                [[ "${FAIL_AT:-}" != stat ]] || exit 7
                echo ' Dockerfile | 2 +-'
            elif [[ "$3" == --unified=0 && "$4" == -- ]]; then
                if [[ "$#" == 6 ]]; then
                    [[ "${FAIL_AT:-}" != digest ]] || exit 7
                elif [[ "$*" == *config/packages/doctrine.yaml* ]]; then
                    [[ "${FAIL_AT:-}" != saved ]] || exit 7
                elif [[ "$*" == *.github/workflows/main.yml* ]]; then
                    [[ "${FAIL_AT:-}" != body ]] || exit 7
                else
                    exit 64
                fi
            else
                exit 64
            fi
            """))
        git.chmod(0o755)
        path = f"{self.root}:{os.environ['PATH']}"
        for fail_at in ("digest", "stat", "saved", "body"):
            with self.subTest(fail_at=fail_at):
                result = self.run_script(
                    WORKFLOW, "Prepare pull request details",
                    BASE_BRANCH="master", PATH=path, FAIL_AT=fail_at,
                )
                self.assertNotEqual(result.returncode, 0, result.stdout + result.stderr)
                if fail_at == "digest":
                    self.assertIn("Could not read the Dockerfile diff", result.stderr)

        template = self.root / ".github/pull_request_template.md"
        template.unlink()
        for name, overrides in (
            ("Prepare PHP minor PR details", {"PHP_CANDIDATE": "php:8.6.2-fpm-trixie"}),
            ("Prepare pull request details", {"PATH": path}),
        ):
            with self.subTest(step=name):
                result = self.run_script(WORKFLOW, name, BASE_BRANCH="master", **overrides)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("pull_request_template.md", result.stderr)

    def test_all_bootstrap_consumers_diagnose_failure_before_cleanup(self):
        consumers = set()
        for path in sorted((ROOT / ".github/workflows").iterdir()):
            if path.suffix not in (".yml", ".yaml"):
                continue
            workflow = yaml.safe_load(path.read_text())
            for name, job in workflow["jobs"].items():
                steps = job.get("steps", [])
                if not any(item.get("uses") == "./.github/actions/php-bootstrap"
                           for item in steps):
                    continue
                consumers.add(path.name)
                with self.subTest(workflow=path.name, job=name):
                    diagnostics = [
                        item for item in steps
                        if item.get("run") == "bash scripts/ci/ci-failure-diagnostics.sh"
                    ]
                    cleanup = [
                        item for item in steps
                        if item.get("run") == "bash scripts/make/make.sh ci-stop"
                    ]
                    self.assertEqual(len(diagnostics), 1)
                    self.assertEqual(len(cleanup), 1)
                    guard = (
                        " && steps.bootstrap.outcome != 'skipped'"
                        if path.name == "main.yml" and name == "report" else ""
                    )
                    self.assertEqual(diagnostics[0]["if"], f"${{{{ failure(){guard} }}}}")
                    self.assertEqual(diagnostics[0]["timeout-minutes"], 2)
                    self.assertFalse(diagnostics[0].get("continue-on-error", False))
                    self.assertEqual(cleanup[0]["if"], f"${{{{ always(){guard} }}}}")
                    self.assertLess(steps.index(diagnostics[0]), steps.index(cleanup[0]))
                    for index, item in enumerate(steps):
                        if (path.name == "docker-image-updates.yml"
                                and item.get("run") == "docker logout docker.io"):
                            self.assertLess(steps.index(diagnostics[0]), index)
                            self.assertEqual(item["if"], "${{ always() }}")
        self.assertTrue(
            {"main.yml", "action-updates.yml", "docker-image-updates.yml"} <= consumers,
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
        app_step = next(item for item in yaml.safe_load(WORKFLOW)["jobs"]["update"]["steps"]
                        if item.get("id") == "app-token")
        self.assertEqual(app_step["with"]["permission-contents"], "write")
        self.assertEqual(app_step["with"]["permission-pull-requests"], "write")
        self.assertEqual(app_step["with"]["permission-workflows"], "write")
        minor_token = next(item for item in yaml.safe_load(WORKFLOW)["jobs"]["php-minor-readiness"]["steps"]
                           if item.get("id") == "minor-token")
        self.assertNotIn("permission-workflows", minor_token["with"])
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
        for username, token, notice in (
            ("", "", "::notice::Using anonymous Docker Hub requests (rate limits apply)."),
            ("", "example-token", "::error::Configure both DOCKERHUB_USERNAME and DOCKERHUB_TOKEN, or neither."),
        ):
            result = self.run_script(
                WORKFLOW, "Authenticate to Docker Hub when credentials are available",
                DOCKERHUB_USERNAME=username, DOCKERHUB_TOKEN=token,
            )
            self.assertEqual(result.returncode, 0 if not token else 1)
            self.assertIn(notice, result.stdout)
            self.assertNotIn("example-token", result.stdout + result.stderr)
        helper = self.root / "scripts/ci/ci-docker-hub-auth.sh"
        for args in ([], [""], ["notice", "extra"]):
            result = subprocess.run(
                ["bash", str(helper), *args], env=self.environment,
                text=True, capture_output=True, check=False,
            )
            self.assertEqual(result.returncode, 2)
            self.assertIn("Usage:", result.stderr)
        empty_bin = self.root / "empty-bin"
        empty_bin.mkdir()
        result = subprocess.run(
            ["/bin/bash", str(helper), "notice"],
            env={**self.environment, "PATH": str(empty_bin),
                 "DOCKERHUB_USERNAME": "example", "DOCKERHUB_TOKEN": "example-token"},
            text=True, capture_output=True, check=False,
        )
        self.assertEqual(result.returncode, 127)
        self.assertIn("docker: command not found", result.stderr)
        self.assertNotIn("example-token", result.stdout + result.stderr)

    def test_core_registry_login_precedes_bootstrap_and_preserves_policy(self):
        job = CORE_WORKFLOW.split("  docker-image-check-updates:\n", 1)[1].split(
            "\n  lightweight-analyzers:", 1,
        )[0]
        login_name = "Authenticate to Docker Hub when credentials are available"
        self.assertLess(
            job.index(f"- name: {login_name}"),
            job.index("uses: ./.github/actions/php-bootstrap"),
        )
        self.assertLess(
            job.index("run: bash scripts/make/make.sh test-docker-images"),
            job.index("run: bash scripts/docker/docker-images.sh check-updates --advisory"),
        )
        self.assertIn(
            "if: ${{ (github.event_name == 'push' && github.ref == 'refs/heads/master') || github.event_name == 'schedule' }}",
            step(job, login_name),
        )
        self.assertIn("if: ${{ always() }}", step(job, "Log out of Docker Hub"))
        binary = self.root / "docker"
        binary.write_text(
            '#!/bin/bash\n'
            'printf "%s\\n" "$*" >> "$DOCKER_CALLS"\n'
            '[ "$*" = "login docker.io --username example --password-stdin" ] || exit 64\n'
            'read -r token\n[ "$token" = example-token ] || exit 1\n'
            'exit "${LOGIN_EXIT:-0}"\n'
        )
        binary.chmod(0o755)
        calls = self.root / "docker-calls"
        for username, token, login_exit, expected, invoked in (
            ("example", "example-token", "0", 0, True),
            ("example", "example-token", "7", 7, True),
            ("", "", "0", 0, False),
            ("example", "", "0", 1, False),
            ("", "example-token", "0", 1, False),
        ):
            with self.subTest(username=username, token=bool(token), exit=login_exit):
                calls.unlink(missing_ok=True)
                result = self.run_script(
                    job, login_name, DOCKERHUB_USERNAME=username,
                    DOCKERHUB_TOKEN=token, LOGIN_EXIT=login_exit,
                    DOCKER_CALLS=str(calls),
                    PATH=f"{self.root}:{os.environ['PATH']}",
                )
                self.assertEqual(result.returncode, expected, result.stderr)
                self.assertEqual(calls.exists(), invoked)
                self.assertNotIn("example-token", result.stdout + result.stderr)
                if not username and not token:
                    self.assertIn("anonymous registry requests", result.stdout)
                elif not username or not token:
                    self.assertIn("::error::Configure both", result.stdout)

    def test_core_image_updates_use_maintenance_only_for_offline_tests(self):
        job = CORE_WORKFLOW.split("  docker-image-check-updates:\n", 1)[1].split(
            "\n  lightweight-analyzers:", 1,
        )[0]
        bootstrap = step(job, "Bootstrap maintenance container")
        self.assertIn("uses: ./.github/actions/php-bootstrap", bootstrap)
        self.assertIn("build-target: maintenance", bootstrap)
        for option in ("install-dependencies", "print-versions", "cache-dependencies"):
            self.assertIn(f"{option}: 'false'", bootstrap)
        self.assertIn(
            "run: bash scripts/make/make.sh test-docker-images",
            step(job, "Run Docker image helper regression tests"),
        )
        self.assertIn(
            "run: bash scripts/docker/docker-images.sh check-updates --advisory",
            step(job, "Check for newer stable Docker image versions"),
        )
        self.assertLess(
            job.index("uses: ./.github/actions/php-bootstrap"),
            job.index("run: bash scripts/make/make.sh test-docker-images"),
        )
        self.assertIn("- *failure_diagnostics_step\n      - *stop_php_step", job)

    def test_bootstrap_defaults_and_maintenance_guards(self):
        self.assertIn("    default: development", BOOTSTRAP)
        self.assertIn("target: ${{ inputs.build-target }}", BOOTSTRAP)
        self.assertIn("build-target: maintenance", WORKFLOW)
        bootstrap_action = yaml.safe_load(BOOTSTRAP)
        build = next(
            item for item in bootstrap_action["runs"]["steps"]
            if item.get("name") == "Build PHP CI image"
        )
        self.assertTrue(build["uses"].startswith("docker/build-push-action@"))
        self.assertEqual(build["env"]["DOCKER_BUILD_SUMMARY"], "false")
        self.assertEqual(build["env"]["DOCKER_BUILD_RECORD_UPLOAD"], "false")
        for workflow in (CORE_WORKFLOW, (ROOT / ".github/workflows/vulnerability-scan.yml").read_text()):
            self.assertNotIn("DOCKER_BUILD_SUMMARY", step(workflow, "Build the Docker image"))
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
        assert maintenance_pin is not None
        assert application_pin is not None
        self.assertEqual(maintenance_pin.group(1), application_pin.group(1))
        maintenance = dockerfile.split(" AS maintenance\n", 1)[1].split("\nFROM ", 1)[0]
        self.assertIn("jq make", maintenance)
        self.assertNotIn("install-php-extensions", maintenance)
        self.assertNotIn("nvm", maintenance)
        self.assertTrue(dockerfile.rsplit("\nFROM ", 1)[1].startswith("base AS production"))

    def test_bootstrap_boolean_defaults_and_bindings_are_validated_first(self):
        action = yaml.safe_load(BOOTSTRAP)
        validation = action["runs"]["steps"][0]
        self.assertEqual(validation["name"], "Validate bootstrap target")
        self.assertEqual(validation["shell"], "bash")
        self.assertEqual(validation["run"], "bash scripts/ci/ci-validate-bootstrap.sh")
        self.assertNotIn("if", validation)
        self.assertNotIn("continue-on-error", validation)
        bindings = {
            "BUILD_TARGET": "build-target",
            "INSTALL_DEPENDENCIES": "install-dependencies",
            "CACHE_DEPENDENCIES": "cache-dependencies",
            "PRINT_VERSIONS": "print-versions",
        }
        self.assertEqual(validation["env"], {
            variable: "${{ inputs." + name + " }}"
            for variable, name in bindings.items()
        })
        defaults = {
            variable: action["inputs"][name]["default"]
            for variable, name in bindings.items()
        }
        self.assertEqual(defaults, {
            "BUILD_TARGET": "development", "INSTALL_DEPENDENCIES": "true",
            "CACHE_DEPENDENCIES": "false", "PRINT_VERSIONS": "true",
        })
        result = self.run_script(BOOTSTRAP, validation["name"], **defaults)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(self.output.exists())
        self.assertFalse(self.summary.exists())

    def test_bootstrap_accepts_all_development_boolean_combinations(self):
        keys = ("INSTALL_DEPENDENCIES", "CACHE_DEPENDENCIES", "PRINT_VERSIONS")
        for values in itertools.product(("true", "false"), repeat=len(keys)):
            with self.subTest(values=values):
                result = self.run_script(
                    BOOTSTRAP, "Validate bootstrap target", BUILD_TARGET="development",
                    **dict(zip(keys, values)),
                )
                self.assertEqual(result.returncode, 0, result.stderr)

    def test_bootstrap_rejects_malformed_booleans_for_both_targets(self):
        inputs = {
            "INSTALL_DEPENDENCIES": "install-dependencies",
            "CACHE_DEPENDENCIES": "cache-dependencies",
            "PRINT_VERSIONS": "print-versions",
        }
        for target in ("development", "maintenance"):
            for key, name in inputs.items():
                for invalid in ("", "TRUE", "False", "1", "0", "yes", " true", "false ",
                                "true\nfalse", "sensitive-invalid-value"):
                    with self.subTest(target=target, input=name, value=invalid):
                        values = dict.fromkeys(inputs, "false")
                        values[key] = invalid
                        result = self.run_script(
                            BOOTSTRAP, "Validate bootstrap target",
                            BUILD_TARGET=target, **values,
                        )
                        self.assertEqual(result.returncode, 1, result.stderr)
                        self.assertEqual(
                            result.stdout,
                            f"::error::Bootstrap input {name} must be exactly 'true' or 'false'.\n",
                        )
                        self.assertEqual(result.stderr, "")
                        self.assertFalse(self.output.exists())
                        self.assertFalse(self.summary.exists())

    def test_pr_description_and_base_branch(self):
        binary = self.root / "git"
        binary.write_text(
            '#!/bin/bash\n'
            '[ "$1" = diff ] || exit 64\n'
            '[ "$2" = --no-ext-diff ] || exit 64\n'
            'if [ "$3" = --stat=160,120 ]; then\n'
            '  [ "${NO_CHANGES:-}" = true ] || printf "%s\\n" " composer.lock | 2 +-" " Dockerfile | 2 +-"\n'
            '  exit 0\n'
            'fi\n'
            '[ "$3" = --unified=0 ] || exit 64\n'
            '[ "${NO_CHANGES:-}" != true ] || exit 0\n'
            'printf "%s\\n" '
            '"diff --git a/Dockerfile b/Dockerfile" '
            '"--- a/Dockerfile" "+++ b/Dockerfile" '
            '"@@ -1 +1 @@" "-FROM php:8.3-cli" "+FROM php:8.3.1-cli"'
        )
        binary.chmod(0o755)
        branch = "feat(ci)/parent-branch"
        readiness_report = (
            "## PHP minor upgrade readiness\n\n**Result: Blocked: PHP extension build failed**\n\n"
            "<details>\n<summary>Readiness evidence and diagnostics</summary>\n\n"
            "APCu: too many arguments to function php_verror\n\n</details>\n"
        )
        result = self.run_script(
            WORKFLOW, "Prepare pull request details", BASE_BRANCH=branch,
            PATH=f"{self.root}:{os.environ['PATH']}",
            PHP_READINESS_REPORT=readiness_report,
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        suffix = hashlib.sha256(branch.encode()).hexdigest()[:16]
        self.assertEqual(
            self.output.read_text(), f"branch=chore(docker)/image-updates-{suffix}\n",
        )
        body = (self.root / "docker-image-pr.md").read_text()
        self.assertIn("# Summary\n", body)
        self.assertIn("stable minor/patch versions", body)
        self.assertIn("without changing their major versions", body)
        self.assertIn("supported platforms", body)
        self.assertIn("Major-version upgrades remain a manual review", body)
        self.assertIn("## Digest-only image comparisons\n", body)
        self.assertIn("No digest-only image updates.", body)
        self.assertIn("## Dockerfile and CI image changes\n", body)
        self.assertIn("## Validation\n", body)
        self.assertIn("`bash scripts/make/make.sh test-docker-images` — passed", body)
        self.assertIn("`bash scripts/make/make.sh docker-image-update-pins` — passed", body)
        self.assertIn("`bash scripts/make/make.sh docker-image-verify-pins` — passed", body)
        self.assertIn("Application tests and builds are not run", body)
        self.assertIn("Repository architecture checklist", body)
        self.assertIn("* [ ]", body)
        self.assertIn(readiness_report, body)
        self.assertIn("Dockerfile", body)
        self.assertIn("```diff\n", body)
        self.assertIn("-FROM php:8.3-cli", body)
        self.assertIn("+FROM php:8.3.1-cli", body)
        report = self.root / "docker-image-changes.md"
        self.assertIn("composer.lock | 2 +-", report.read_text())
        self.assertIn("+FROM php:8.3.1-cli", report.read_text())
        result = self.run_script(
            WORKFLOW, "Prepare pull request details", BASE_BRANCH=branch, NO_CHANGES="true",
            PATH=f"{self.root}:{os.environ['PATH']}",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("No eligible tag or digest changes.", report.read_text())
        self.assertNotIn("```diff", report.read_text())
        pr = step(WORKFLOW, "Create or update the Docker image pull request")
        self.assertIn("base: ${{ github.ref_name }}", pr)
        self.assertIn("branch: ${{ steps.details.outputs.branch }}", pr)
        self.assertNotIn("branch-suffix:", pr)
        # Always invoke the action: it owns existing-PR reconciliation on no diff.
        self.assertNotIn("if:", pr)
        for path in ("composer.json", "composer.lock", "tools/*/composer.json", "tools/*/composer.lock"):
            self.assertIn(f"            {path}\n", pr)
        self.assertIn("            config/packages/doctrine.yaml\n", pr)
        self.assertIn("            .github/workflows/main.yml\n", pr)

    def test_digest_comparison_reports_registry_evidence_and_unavailable_data(self):
        old_digest, new_digest = "a" * 64, "b" * 64
        diff = (
            f"-FROM example:1@sha256:{old_digest}\n"
            f"+FROM example:1@sha256:{new_digest}\n"
            f"-FROM other:1@sha256:{old_digest}\n"
            f"+FROM other:2@sha256:{new_digest}\n"
        )
        (self.root / "diff").write_text(diff)
        git = self.root / "git"
        git.write_text('#!/bin/bash\n[ "$1" = diff ] || exit 64\ncat diff\n')
        git.chmod(0o755)
        docker = self.root / "docker"
        docker.write_text(textwrap.dedent("""\
            #!/bin/bash
            [ "$1 $2 $3 $4" = "buildx imagetools inspect --raw" ] || exit 64
            printf '%s\\n' "$5" >> calls
            case "$5" in
                *sha256:aaaaaaaa*) cat old.json ;;
                *sha256:bbbbbbbb*) cat new.json ;;
                *) exit 64 ;;
            esac
            """))
        docker.chmod(0o755)
        def descriptor(arch, digest, annotations=None):
            return {
                "digest": "sha256:" + digest * 64,
                "platform": {"os": "linux", "architecture": arch},
                "annotations": annotations or {},
            }
        old = {"manifests": [
            descriptor("amd64", "c", {
                "org.opencontainers.image.revision": "old",
                "org.opencontainers.image.base.name": "php:8-alpine",
            }),
            descriptor("arm64", "d"),
        ]}
        new = {"manifests": [
            descriptor("amd64", "e", {
                "org.opencontainers.image.revision": "<new>",
                "org.opencontainers.image.base.name": "php:8.5-alpine",
            }),
            descriptor("386", "f"),
            {"digest": "sha256:" + "1" * 64, "platform": {"os": "unknown", "architecture": "unknown"}},
        ]}
        (self.root / "old.json").write_text(json.dumps(old))
        (self.root / "new.json").write_text(json.dumps(new))
        env = dict(self.environment, PATH=f"{self.root}:{os.environ['PATH']}")
        command = ["python3", "scripts/docker/docker-image-digest-summary.py"]
        result = subprocess.run(command, cwd=self.root, env=env, text=True, capture_output=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("<code>example:1</code>", result.stdout)
        self.assertNotIn("other:", result.stdout)
        self.assertIn("<code>linux/amd64</code>: manifest changed", result.stdout)
        self.assertIn("<code>linux/arm64</code>: removed", result.stdout)
        self.assertIn("<code>linux/386</code>: added", result.stdout)
        self.assertIn("org.opencontainers.image.revision", result.stdout)
        self.assertIn("&lt;new&gt;", result.stdout)
        self.assertIn("Non-runnable descriptors", result.stdout)
        self.assertIn("not the publisher's reason or exact package changes", result.stdout)
        overview, details = result.stdout.split("<details>\n<summary>Show index and platform details</summary>\n", 1)
        self.assertIn("1 changed, 0 unchanged, 1 added, 1 removed", overview)
        self.assertIn("Base image metadata (1 platform(s)):", overview)
        self.assertIn("php:8-alpine", overview)
        self.assertIn("php:8.5-alpine", overview)
        self.assertIn("Source revision metadata (1 platform(s)):", overview)
        self.assertIn("&lt;new&gt;", overview)
        self.assertNotIn("linux/amd64", overview)
        self.assertIn("<code>linux/amd64</code>: manifest changed", details)
        self.assertIn("</details>", details)
        self.assertEqual(len((self.root / "calls").read_text().splitlines()), 2)

        result = self.run_script(
            WORKFLOW, "Prepare pull request details", BASE_BRANCH="master",
            PATH=f"{self.root}:{os.environ['PATH']}",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        body = (self.root / "docker-image-pr.md").read_text()
        self.assertIn("<code>linux/amd64</code>: manifest changed", body)
        self.assertIn("org.opencontainers.image.revision", body)
        self.assertIn("<details>\n<summary>Show index and platform details</summary>", body)
        self.assertLess(body.index("Runnable platform manifests:"), body.index("<details>"))
        digest_report = (self.root / "docker-image-digests.md").read_text()
        change_report = (self.root / "docker-image-changes.md").read_text()
        self.assertIn(digest_report, body)
        self.assertIn(digest_report, change_report)
        # Two calls for the direct report above, and two for the shared workflow report.
        self.assertEqual(len((self.root / "calls").read_text().splitlines()), 4)
        result = self.run_script(
            WORKFLOW, "Publish workflow run overview", EVENT_NAME="workflow_dispatch",
            PRIOR_STATUS="success", BASE_BRANCH="master", BOOTSTRAP="success",
            TESTS="success", UPDATE="success", VERIFY="success", COMPOSER="success",
            DOCTRINE="success", CI_MARIADB="success", DETAILS="success", PULL_REQUEST="success",
            PR_URL="", PR_OPERATION="none",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn(digest_report, self.summary.read_text())

        unchanged = {"manifests": [descriptor("amd64", "c")]}
        metadata_only = {"manifests": [
            descriptor("amd64", "c", {"org.opencontainers.image.created": "2026-01-01"}),
        ]}
        (self.root / "old.json").write_text(json.dumps(unchanged))
        (self.root / "new.json").write_text(json.dumps(metadata_only))
        result = subprocess.run(command, cwd=self.root, env=env, text=True, capture_output=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("manifest unchanged", result.stdout)
        self.assertIn("org.opencontainers.image.created", result.stdout)
        self.assertIn("Platform descriptor metadata changed", result.stdout)
        self.assertIn("index changed without a confirmed runnable manifest change", result.stdout)

        (self.root / "diff").write_text(
            diff + f"-COPY --from=example:1@sha256:{'c' * 64} /x /x\n",
        )
        result = subprocess.run(command, cwd=self.root, env=env, text=True, capture_output=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("Multiple old or new digests", result.stdout)
        (self.root / "diff").write_text(diff)

        (self.root / "old.json").write_text("not JSON")
        result = subprocess.run(command, cwd=self.root, env=env, text=True, capture_output=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("the old index could not be retrieved or parsed", result.stdout)
        self.assertIn(old_digest, result.stdout)
        self.assertNotIn("<details>", result.stdout)

        (self.root / "old.json").unlink()
        result = subprocess.run(command, cwd=self.root, env=env, text=True, capture_output=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("the old index could not be retrieved or parsed", result.stdout)

        git.write_text("#!/bin/bash\nexit 1\n")
        result = subprocess.run(command, cwd=self.root, env=env, text=True, capture_output=True, check=False)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Could not read the Dockerfile diff", result.stderr)

    def test_doctrine_sync_updates_only_server_version_on_a_same_major_tag_change(self):
        dockerfile = self.root / "docker/mariadb/Dockerfile"
        dockerfile.parent.mkdir(parents=True)
        config = self.root / "config/packages/doctrine.yaml"
        config.parent.mkdir(parents=True)
        mock_git = self.root / "git"
        mock_git.write_text('#!/bin/bash\n[[ "$*" == "show HEAD:docker/mariadb/Dockerfile" ]] || exit 64\ncat old-pin\n')
        mock_git.chmod(0o755)
        original = (ROOT / "config/packages/doctrine.yaml").read_text()

        def pin(version, digest="a"):
            return f"FROM mariadb:{version}@sha256:{digest * 64}\n"

        (self.root / "old-pin").write_text(pin("13.0.2"))
        for candidate, source, status, expected in (
            (pin("13.1.1"), original, 0, original.replace("mariadb-13.0.2", "mariadb-13.1.1")),
            (pin("13.0.2", "b"), original, 0, original),
            (pin("14.0.0"), original, 1, original),
            (pin("13.1.1") * 2, original, 1, original),
            (pin("13.1.1"), original.replace("server_version:", "other:"), 1,
             original.replace("server_version:", "other:")),
            (pin("13.1.1"), original + "        server_version: 'mariadb-13.0.2'\n", 1,
             original + "        server_version: 'mariadb-13.0.2'\n"),
            (pin("13.1.1"), original.replace("'mariadb-13.0.2'", "'13.0.2'"), 1,
             original.replace("'mariadb-13.0.2'", "'13.0.2'")),
        ):
            with self.subTest(candidate=candidate, status=status):
                dockerfile.write_text(candidate)
                config.write_text(source)
                result = self.run_script(
                    WORKFLOW, "Synchronize Doctrine with the MariaDB image",
                    PATH=f"{self.root}:{os.environ['PATH']}",
                )
                self.assertEqual(result.returncode, status, result.stdout + result.stderr)
                self.assertEqual(config.read_text(), expected)

    def test_mariadb_sync_steps_fail_closed_on_missing_inputs_and_command_failures(self):
        steps = yaml.safe_load(WORKFLOW)["jobs"]["update"]["steps"]
        doctrine = next(item for item in steps if item.get("id") == "doctrine")
        ci = next(item for item in steps if item.get("id") == "ci-mariadb")
        self.assertLess(steps.index(doctrine), steps.index(ci))
        self.assertEqual(doctrine["run"], "bash scripts/docker/sync-doctrine-mariadb.sh")
        self.assertEqual(ci["run"], "bash scripts/docker/sync-ci-mariadb.sh")
        self.assertEqual(doctrine["shell"], "bash")
        self.assertEqual(ci["shell"], "bash")

        dockerfile = self.root / "docker/mariadb/Dockerfile"
        dockerfile.parent.mkdir(parents=True)
        config = self.root / "config/packages/doctrine.yaml"
        config.parent.mkdir(parents=True)
        workflow = self.root / ".github/workflows/main.yml"
        workflow.parent.mkdir(parents=True)
        old = f"mariadb:13.0.2@sha256:{'a' * 64}"
        new = f"mariadb:13.1.1@sha256:{'b' * 64}"
        original_config = "        server_version: 'mariadb-13.0.2'\n"
        original_workflow = f"        image: {old}\n"
        config.write_text(original_config)
        workflow.write_text(original_workflow)
        git = self.root / "git"
        git.write_text(
            '#!/bin/bash\n[[ "$*" == "show HEAD:docker/mariadb/Dockerfile" ]] || exit 64\n'
            f"printf 'FROM {old}\\n'\n",
        )
        git.chmod(0o755)
        env = {"PATH": f"{self.root}:{os.environ['PATH']}"}

        for name in ("Synchronize Doctrine with the MariaDB image", "Synchronize CI MariaDB image pin"):
            result = self.run_script(WORKFLOW, name, **env)
            self.assertNotEqual(result.returncode, 0)
            self.assertNotIn("synchronized to", result.stdout)
        self.assertEqual(config.read_text(), original_config)
        self.assertEqual(workflow.read_text(), original_workflow)

        dockerfile.write_text(f"FROM {new}\n")
        for old_content in ("", "FROM mariadb:13.0.2\n", f"FROM {old}\nFROM {old}\n"):
            git.write_text(
                '#!/bin/bash\n[[ "$*" == "show HEAD:docker/mariadb/Dockerfile" ]] || exit 64\n'
                f"printf '%s' '{old_content}'\n",
            )
            result = self.run_script(WORKFLOW, "Synchronize Doctrine with the MariaDB image", **env)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("::error::", result.stdout)
            self.assertEqual(config.read_text(), original_config)

        git.write_text(
            '#!/bin/bash\n[[ "$*" == "show HEAD:docker/mariadb/Dockerfile" ]] || exit 64\n'
            f"printf 'FROM {old}\\n'\nexit 7\n",
        )
        result = self.run_script(WORKFLOW, "Synchronize Doctrine with the MariaDB image", **env)
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn("synchronized to", result.stdout)
        self.assertEqual(config.read_text(), original_config)

        git.write_text(
            '#!/bin/bash\n[[ "$*" == "show HEAD:docker/mariadb/Dockerfile" ]] || exit 64\n'
            f"printf 'FROM {old}\\n'\n",
        )
        for name, target, original in (
            ("Synchronize Doctrine with the MariaDB image", config, original_config),
            ("Synchronize CI MariaDB image pin", workflow, original_workflow),
        ):
            target.unlink()
            result = self.run_script(WORKFLOW, name, **env)
            self.assertNotEqual(result.returncode, 0)
            self.assertNotIn("synchronized to", result.stdout)
            self.assertFalse(target.exists())
            target.write_text(original)

        sed = self.root / "sed"
        sed.write_text('#!/bin/bash\nif [[ "$1" == -i ]]; then exit 7; fi\nexec /bin/sed "$@"\n')
        sed.chmod(0o755)
        for name, target, original in (
            ("Synchronize Doctrine with the MariaDB image", config, original_config),
            ("Synchronize CI MariaDB image pin", workflow, original_workflow),
        ):
            result = self.run_script(WORKFLOW, name, **env)
            self.assertNotEqual(result.returncode, 0)
            self.assertNotIn("synchronized to", result.stdout)
            self.assertEqual(target.read_text(), original)

    def test_ci_mariadb_pin_sync_handles_tags_digests_drift_and_invalid_inputs(self):
        dockerfile = self.root / "docker/mariadb/Dockerfile"
        dockerfile.parent.mkdir(parents=True)
        workflow = self.root / ".github/workflows/main.yml"
        workflow.parent.mkdir(parents=True)
        old = f"mariadb:13.0.2@sha256:{'a' * 64}"
        for candidate in (old, f"mariadb:13.1.1@sha256:{'b' * 64}",
                          f"mariadb:13.0.2@sha256:{'b' * 64}"):
            with self.subTest(candidate=candidate):
                dockerfile.write_text(f"FROM {candidate}\n")
                workflow.write_text(f"services:\n  mariadb:\n        image: {old}\n")
                result = self.run_script(WORKFLOW, "Synchronize CI MariaDB image pin")
                self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertEqual(workflow.read_text(), f"services:\n  mariadb:\n        image: {candidate}\n")
                again = self.run_script(WORKFLOW, "Synchronize CI MariaDB image pin")
                self.assertEqual(again.returncode, 0, again.stdout + again.stderr)

        for docker_source, workflow_source in (
            (f"FROM {old}\nFROM {old}\n", f"        image: {old}\n"),
            ("FROM mariadb:13.0.2\n", f"        image: {old}\n"),
            ("", f"        image: {old}\n"),
            (f"FROM {old}\n", f"        image: {old}\n        image: {old}\n"),
            (f"FROM {old}\n", "        image: mariadb:13.0.2\n"),
            (f"FROM {old}\n", ""),
        ):
            with self.subTest(docker_source=docker_source, workflow_source=workflow_source):
                dockerfile.write_text(docker_source)
                workflow.write_text(workflow_source)
                result = self.run_script(WORKFLOW, "Synchronize CI MariaDB image pin")
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("::error::", result.stdout)
                self.assertEqual(workflow.read_text(), workflow_source)

    def test_composer_sync_only_for_patch_changes_and_fails_closed(self):
        helper = ROOT / "scripts/sync-composer-php.sh"
        mock = self.root / "composer"
        mock.write_text('#!/bin/bash\nprintf "%s\\n" "$*" >> calls\n[[ "$*" != *"${FAIL_COMMAND:-never-match}"* ]]\n')
        mock.chmod(0o755)
        calls = self.root / "calls"
        tool = self.root / "tools/example"
        tool.mkdir(parents=True)
        for directory in (self.root, tool):
            for name in ("composer.json", "composer.lock"):
                (directory / name).touch()

        def dockerfile(version, digest="a"):
            return f"FROM php:{version}-fpm-trixie@sha256:{digest * 64} AS php-upstream\n"

        def run(new, old=dockerfile("8.5.12"), *args, **overrides):
            (self.root / "Dockerfile").write_text(new)
            calls.unlink(missing_ok=True)
            return subprocess.run(
                ["bash", str(helper), *args], input=old, cwd=self.root, text=True, capture_output=True,
                env={**self.environment, "PATH": f"{self.root}:{os.environ['PATH']}", **overrides},
                check=False, timeout=10,
            )

        for candidate, status in (
            (dockerfile("8.5.12", "b"), 0), (dockerfile("8.6.1"), 1),
            ("FROM scratch\n", 1), (dockerfile("8.5.13") * 2, 1),
        ):
            result = run(candidate)
            self.assertEqual(result.returncode, status, result.stderr)
            self.assertFalse(calls.exists())

        result = run(dockerfile("8.5.13"))
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(calls.read_text().splitlines(), [
            f"--working-dir={directory} --no-interaction --no-plugins --no-scripts {command}"
            for directory in ("tools/example", ".")
            for command in (
                "config platform.php 8.5.13", "require php:^8.5.13 --no-update",
                "update --lock --no-install --ignore-platform-req=ext-*", "validate --no-check-version",
            )
        ])
        result = run(dockerfile("8.5.13"), FAIL_COMMAND="update --lock")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(len(calls.read_text().splitlines()), 3)
        (tool / "composer.lock").unlink()
        result = run(dockerfile("8.5.13"))
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Missing Composer manifest or lockfile", result.stderr)
        self.assertFalse(calls.exists())

        for directory in (self.root, tool):
            (directory / "composer.lock").write_text('{"packages":[],"packages-dev":[]}\n')
        for version in ("8.5.13", "8.7.0", "9.0.0"):
            result = run(dockerfile(version), dockerfile("8.5.12"), "--next-minor")
            self.assertEqual(result.returncode, 1, result.stderr)
            self.assertFalse(calls.exists())
        result = run(dockerfile("8.6.2"), dockerfile("8.5.12"), "--next-minor")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("require php:^8.6.2 --no-update", calls.read_text())
        self.assertIn("update --lock --no-install\n", calls.read_text())
        self.assertNotIn("--ignore-platform-req", calls.read_text())

        with mock.open("a") as script_file:
            script_file.write(
                'if [[ "$*" == *" update --lock "* ]]; then\n'
                '    printf \'%s\\n\' \'{"packages":[{"name":"example/tool","version":"2.0"}],"packages-dev":[]}\''
                ' > "${1#--working-dir=}/composer.lock"\n'
                'fi\n'
            )
        result = run(dockerfile("8.6.2"), dockerfile("8.5.12"), "--next-minor")
        self.assertEqual(result.returncode, 1)
        self.assertIn("Package versions or references changed", result.stderr)

    def test_php_minor_pr_outcome_summary_and_failures(self):
        steps = yaml.safe_load(WORKFLOW)["jobs"]["php-minor-readiness"]["steps"]
        outcome_step = next(item for item in steps if item.get("name") == "Publish PHP minor PR outcome")
        self.assertEqual(outcome_step["run"], "bash scripts/docker/php-minor-pr-outcome.sh")
        self.assertEqual(outcome_step["if"], "${{ !cancelled() && steps.php-minor.outputs.prepared == 'true' }}")
        self.assertTrue(outcome_step["continue-on-error"])
        url = "https://github.com/example/repo/pull/1"
        for outcome, link, expected in (
            ("success", url, f"\nPHP minor migration PR: {url}\n"),
            ("success", "", "\nNo PHP minor PR changes.\n"),
            ("failure", "", "\nNo PHP minor PR was confirmed (PR step: failure).\n"),
            ("skipped", "", "\nNo PHP minor PR was confirmed (PR step: skipped).\n"),
            ("", "", "\nNo PHP minor PR was confirmed (PR step: ).\n"),
        ):
            with self.subTest(outcome=outcome, link=link):
                self.summary.write_text("Earlier summary\n")
                result = self.run_script(
                    WORKFLOW, "Publish PHP minor PR outcome", PR_OUTCOME=outcome, PR_URL=link,
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(self.summary.read_text(), "Earlier summary\n" + expected)

        result = self.run_script(
            WORKFLOW, "Publish PHP minor PR outcome", PR_OUTCOME="success", PR_URL=url,
            GITHUB_STEP_SUMMARY=str(self.root),
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertTrue(result.stderr)

    def test_maintenance_overview_propagates_missing_evidence_and_helper_failures(self):
        environment = dict(
            EVENT_NAME="workflow_dispatch", PRIOR_STATUS="success", BASE_BRANCH="master",
            BOOTSTRAP="success", TESTS="success", UPDATE="success", VERIFY="success",
            COMPOSER="success", DOCTRINE="success", CI_MARIADB="success", DETAILS="success",
            PULL_REQUEST="success", PR_URL="", PR_OPERATION="none", PHP_READINESS_SUMMARY="success",
        )
        result = self.run_script(WORKFLOW, "Publish workflow run overview", **environment)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("docker-image-changes.md", result.stderr)

        (self.root / "docker-image-changes.md").write_text("Saved changes\n")
        (self.root / "scripts/ci/ci-workflow-overview.sh").unlink()
        result = self.run_script(WORKFLOW, "Publish workflow run overview", **environment)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("ci-workflow-overview.sh", result.stderr)

        result = self.run_script(
            WORKFLOW, "Publish workflow run overview", **environment, GITHUB_STEP_SUMMARY=str(self.root),
        )
        self.assertNotEqual(result.returncode, 0)
        self.assertTrue(result.stderr)

    def test_pr_and_no_change_summaries(self):
        url = "https://github.com/example/repo/pull/1"
        (self.root / "docker-image-changes.md").write_text("## Proposed changes\n\nSaved before the PR action.\n")
        for outcome, operation, link, details, message in (
            ("success", "created", url, "success", f"(created): {url}"),
            ("success", "closed", "", "success", "reported: closed."),
            ("success", "none", "", "success", "No pull request changes."),
            ("failure", "", "", "success", "action outcome: failure"),
            ("skipped", "", "", "skipped", "action outcome: skipped"),
        ):
            with self.subTest(outcome=outcome, operation=operation):
                self.summary.unlink(missing_ok=True)
                result = self.run_script(
                    WORKFLOW, "Publish workflow run overview", EVENT_NAME="workflow_dispatch",
                    PRIOR_STATUS="success" if outcome == "success" else "failure", BASE_BRANCH="master",
                    BOOTSTRAP="success", TESTS="success", UPDATE="success", VERIFY="success",
                    COMPOSER="success", DOCTRINE="success", CI_MARIADB="success", DETAILS=details, PULL_REQUEST=outcome,
                    PR_URL=link, PR_OPERATION=operation,
                    PHP_READINESS_SUMMARY="success",
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                summary = self.summary.read_text()
                self.assertIn("Base branch: `master`", summary)
                self.assertIn(message, summary)
                self.assertIn("Core CI's Docker image version report", summary)
                self.assertIn("evidence is in the **Check PHP minor upgrade readiness** job summary", summary)
                self.assertNotIn("## PHP minor upgrade readiness", summary)
                self.assertIn(
                    "Saved before the PR action." if details == "success" else "No verified change report",
                    summary,
                )

    def test_readiness_summary_reference_and_unavailable_fallback(self):
        (self.root / "docker-image-changes.md").write_text("## Proposed changes\n")
        for publication, job_result in (
            ("success", "success"), ("success", "failure"), ("failure", "failure"),
            ("skipped", "skipped"), ("", "success"),
        ):
            with self.subTest(publication=publication, job_result=job_result):
                self.summary.unlink(missing_ok=True)
                result = self.run_script(
                    WORKFLOW, "Publish workflow run overview", EVENT_NAME="workflow_dispatch",
                    PRIOR_STATUS="success", BASE_BRANCH="master",
                    BOOTSTRAP="success", TESTS="success", UPDATE="success", VERIFY="success",
                    COMPOSER="success", DOCTRINE="success", CI_MARIADB="success", DETAILS="success",
                    PULL_REQUEST="success", PR_URL="", PR_OPERATION="none",
                    PHP_READINESS_SUMMARY=publication, PHP_READINESS_RESULT=job_result,
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                summary = self.summary.read_text()
                if publication == "success":
                    self.assertIn("evidence is in the **Check PHP minor upgrade readiness** job summary", summary)
                    self.assertNotIn("**Result: Unavailable**", summary)
                else:
                    self.assertIn("**Result: Unavailable**", summary)
                    self.assertIn(f"summary step: {publication or 'unavailable'}", summary)
                    self.assertIn("No compatibility conclusion can be drawn", summary)
                self.assertIn(f"readiness job: {job_result}" if publication != "success"
                              else f"job result: {job_result}", summary)

    def test_major_reporting_is_independent_of_automatic_updates(self):
        self.assertNotIn("check-updates", WORKFLOW)
        without_overview = WORKFLOW.replace(
            "      - name: Publish workflow run overview\n"
            + step(WORKFLOW, "Publish workflow run overview"), "",
        )
        informational_step = step(WORKFLOW, "Publish PHP minor PR outcome")
        self.assertNotIn("continue-on-error:", without_overview.replace(informational_step, "", 1))
        self.assertIn("run: bash scripts/make/make.sh docker-image-update-pins", WORKFLOW)
        core = (ROOT / ".github/workflows/main.yml").read_text()
        report = re.search(
            r"^  docker-image-check-updates:\n(.*?)(?=^  [a-z][a-z-]*:\n|\Z)",
            core, re.MULTILINE | re.DOTALL,
        )
        assert report is not None
        self.assertIn("github.event_name == 'schedule'", report.group(1))
        self.assertIn(
            "run: bash scripts/docker/docker-images.sh check-updates --advisory",
            report.group(1),
        )
        self.assertIn("  workflow_dispatch:\n", core)


if __name__ == "__main__":
    unittest.main()
