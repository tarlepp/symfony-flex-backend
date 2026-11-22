#!/usr/bin/env python3
"""Track confirmed PHP minor blockers and link their eventual migration PR.

The workflow supplies the readiness report, build log, base branch and token.
Open issues are identified by a branch, PHP minor and image variant marker;
registry failures and builds without extension compiler errors are not tracked.
"""

#
# Usage:
#   From the repository root in the GitHub Actions runner, with the workflow's
#   readiness files and environment available:
#     python3 scripts/docker/php-minor-issues.py \
#       --source "$RUNNER_TEMP/php-minor-readiness.md" \
#       --log "$RUNNER_TEMP/php-minor-readiness.log" --outcome "$CHECK_OUTCOME"
#
# Requirements:
#   Python 3 and an authenticated GitHub CLI with access to the repository.
#   Issue creation requires issues: write permission.
#
# Details:
#   A successful outcome and a recognized readiness result are required before
#   querying GitHub. Build failures are tracked only when the log contains an
#   extension compiler error. The marker identifies the base branch, PHP
#   minor version and image variant, so patch releases share an issue.
#   Existing open issues are reused; a confirmed blocker creates an issue only
#   when none matches. A prepared migration emits closing references rather
#   than closing issues directly. Errors are reported to stderr and exit nonzero.
#
# Environment:
#   BASE_BRANCH identifies the branch. GitHub CLI authentication and repository
#   selection use GH_TOKEN and GH_REPO in the workflow. Issue creation uses
#   GITHUB_SERVER_URL, GITHUB_REPOSITORY, GITHUB_RUN_ID and PHP_READINESS_REPORT.
#   Matching issues are
#   appended to GITHUB_STEP_SUMMARY; a prepared migration also writes
#   closing-issues to GITHUB_OUTPUT.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml` tracks blockers and adds
#       closing references to the PHP minor migration PR details.

import argparse
import hashlib
import html
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import uuid


def github_api(endpoint, payload=None):
    """Read all API pages or create an issue; propagate CLI and JSON failures."""
    command = ["gh", "api"]

    if payload is None:
        command.extend(["--paginate", "--slurp"])
    else:
        command.extend(["--method", "POST", "--input", "-"])

    command.append(endpoint)
    result = subprocess.run(
        command, input=json.dumps(payload) if payload is not None else None,
        text=True, capture_output=True, check=True, timeout=120,
    )

    return json.loads(result.stdout)


def main():
    """Track eligible blockers or expose closing references for a ready upgrade."""
    parser = argparse.ArgumentParser(description=__doc__)

    parser.add_argument("--source", type=Path, required=True)
    parser.add_argument("--log", type=Path, required=True)
    parser.add_argument("--outcome", required=True)
    args = parser.parse_args()

    if args.outcome != "success":
        return

    raw = args.source.read_text()
    result = next((line for line in reversed(raw.splitlines()) if line.startswith("**Result: ")), "")

    if not result:
        raise ValueError("PHP readiness evidence has no result")

    composer_blocked = result.startswith("**Result: Blocked: locked platform requirements")
    build_blocked = result.startswith("**Result: Blocked: candidate development image did not build")
    ready = result == "**Result: Prepared for a migration PR**"

    if build_blocked:
        log = args.log.read_text(errors="replace")
        build_blocked = bool(re.search(
            r"/(?:pear/temp|usr/src/php/ext)/[A-Za-z0-9_-]+/[^\n]*\b(?:fatal )?error:",
            log, re.IGNORECASE,
        ))

    if not (composer_blocked or build_blocked or ready):
        return

    candidate = re.search(r"^Candidate: `php:(\d+)\.(\d+)\.\d+(-[A-Za-z0-9_.-]+)`$", raw, re.MULTILINE)

    if not candidate:
        raise ValueError("PHP readiness evidence has no valid candidate image")

    branch = os.environ["BASE_BRANCH"]
    branch_id = hashlib.sha256(branch.encode()).hexdigest()
    version = f"{candidate[1]}.{candidate[2]}{candidate[3]}"
    # Patch versions are excluded so repeated checks reuse the same minor issue.
    marker = f"<!-- php-minor-blocker:{branch_id}:{version} -->"
    endpoint = "repos/{owner}/{repo}/issues"

    pages = github_api(f"{endpoint}?state=open&per_page=100")

    if not isinstance(pages, list) or not all(isinstance(page, list) for page in pages):
        raise ValueError("Unexpected GitHub issue listing")

    matches = [
        issue for page in pages for issue in page
        if "pull_request" not in issue and marker in (issue.get("body") or "")
    ]

    if (composer_blocked or build_blocked) and not matches:
        report = os.environ["PHP_READINESS_REPORT"].split("\n\n<details>", 1)[0].strip()

        if not report or "**Result:" not in report:
            raise ValueError("PHP readiness report has no readable result")

        if len(report) > 12000:
            report = report[:12000] + "\n\nReadiness summary truncated; see the run for full evidence."

        run_url = f"{os.environ['GITHUB_SERVER_URL']}/{os.environ['GITHUB_REPOSITORY']}/actions/runs/{os.environ['GITHUB_RUN_ID']}"
        body = (
            f"{marker}\n\nThe PHP `{version}` image cannot yet be adopted for the "
            f"base branch <code>{html.escape(branch)}</code>. "
            f"See the [readiness run]({run_url}) for the "
            "candidate, blocker and full diagnostics.\n\n"
            f"{report}\n"
        )
        issue = github_api(endpoint, {"title": f"PHP {version} upgrade is blocked", "body": body})
        matches = [issue]

    for issue in matches:
        if not isinstance(issue.get("number"), int) or issue["number"] <= 0:
            raise ValueError("GitHub returned an issue without a valid number")

    if ready and matches:
        closing = "\n".join(f"Closes #{issue['number']}" for issue in matches)
        delimiter = f"issues-{uuid.uuid4()}"

        with Path(os.environ["GITHUB_OUTPUT"]).open("a") as output:
            output.write(f"closing-issues<<{delimiter}\n{closing}\n{delimiter}\n")

    if matches:
        with Path(os.environ["GITHUB_STEP_SUMMARY"]).open("a") as summary:
            summary.write("\nPHP minor tracking issues: " + ", ".join(
                f"#{issue['number']}" for issue in matches
            ) + "\n")


if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, KeyError, subprocess.SubprocessError) as error:
        print(f"PHP minor issue tracking failed: {error}", file=sys.stderr)

        if isinstance(error, subprocess.CalledProcessError) and error.stderr:
            print(error.stderr, file=sys.stderr)

        sys.exit(1)
