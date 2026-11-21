#!/usr/bin/env python3
"""Publish PHP readiness evidence for separate maintenance jobs and PRs.

The workflow supplies the check's Markdown report, log, and step outcome.
GitHub job outputs carry the same evidence in compact PR and highlighted Actions
forms. Missing evidence is explicitly unavailable, never a successful check.
"""

#
# Usage:
#   From the repository root in the GitHub Actions runner, with the workflow's
#   environment available:
#     python3 scripts/docker/php-minor-readiness-report.py \
#       --source "$RUNNER_TEMP/php-minor-readiness.md" \
#       --log "$RUNNER_TEMP/php-minor-readiness.log" --outcome "$CHECK_OUTCOME"
#
# Requirements:
#   Python 3. The outcome must be success, failure, cancelled, or skipped.
#   Missing source or log files are treated as empty evidence.
#
# Details:
#   Produces compact and highlighted Markdown from the readiness report and
#   captured error lines. A non-success outcome overrides any reported result;
#   a successful outcome without a result is marked unavailable. Diagnostics
#   extracted from the log are escaped before inclusion in Markdown.
#
# Environment:
#   Appends multiline `report` and `actions-report` outputs to GITHUB_OUTPUT and
#   the highlighted report to GITHUB_STEP_SUMMARY. Both paths must be writable.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml` uses the compact report in
#       PHP migration and image-update PRs, and the highlighted report in the
#       workflow run overview.

import argparse
import html
import os
from pathlib import Path
import re
import uuid


def reports(raw, log, outcome):
    """Return compact and highlighted reports with the same readiness evidence."""
    lines = raw.splitlines()
    result = next((line for line in reversed(lines) if line.startswith("**Result: ")), "")
    if outcome != "success":
        result = "**Result: Check failed; PHP minor readiness is incomplete**"
    elif not result:
        result = "**Result: Unavailable; no readiness result was produced**"

    identity = [line for line in lines if line.startswith(("Current: ", "Candidate: "))]
    reason = next((line for line in lines if line.startswith("Reason: ")), "")
    if not reason:
        constraint = next((
            match for line in lines
            if (match := re.fullmatch(r"\| (`[^`]+`) \| Blocked: (.+) \|", line))
        ), None)
        if constraint:
            reason = f"Reason: Composer project {constraint[1]} is blocked: {constraint[2]}"
    errors = []
    for line in log.splitlines():
        # Normalize BuildKit prefixes and ANSI color before deduplicating errors.
        line = re.sub(r"\x1b\[[0-9;]*m", "", line)
        line = re.sub(r"^#\d+ [\d.]+ ", "", line)
        if re.search(r"\berror:|^ERROR:|^\[FAIL\]|^::error::", line, re.IGNORECASE):
            if line not in errors:
                errors.append(line)
    if not reason and errors:
        reason = f"Reason: <code>{html.escape(errors[0])}</code>"
    if not reason and outcome != "success":
        reason = "Reason: The check did not complete; compatibility has not been established. See the job log."
    if not reason and not raw:
        reason = "Reason: No readiness evidence is available. See the job log."
    if not reason and "Blocked:" in result:
        reason = "Reason: No detailed diagnostic was captured. See the job log."

    explanation = ""
    extension_summary = ""
    if "Blocked: candidate development image did not build" in result:
        explanation = "**Build error:** The candidate PHP development image could not be built."
        failures = {}
        for diagnostic in errors or [html.unescape(reason)]:
            extension = re.search(
                r"/(?:pear/temp|usr/src/php/ext)/([A-Za-z0-9_-]+)/", diagnostic,
            )
            if extension:
                failures.setdefault(extension[1], []).append(diagnostic)
        if len(failures) == 1:
            name = next(iter(failures))
            explanation += (
                f" PHP extension `{name}` failed to compile against the candidate PHP runtime."
            )
        elif failures:
            explanation += " PHP extensions " + ", ".join(f"`{name}`" for name in failures)
            explanation += " have compiler errors against the candidate PHP runtime."
        diagnostics = html.unescape(reason + "\n" + "\n".join(errors))
        if "too many arguments to function 'php_verror'" in diagnostics:
            explanation += (
                " `php_verror` is called with more arguments than the candidate PHP API accepts."
            )
        explanation += " Composer compatibility checks did not run; the upgrade remains blocked."
        if failures:
            extension_summary = "**Failing extensions observed in the build log**\n\n"
            extension_summary += "| Extension | First compiler error |\n| --- | --- |\n"
            for name, extension_errors in failures.items():
                message = re.split(r"\b(?:fatal )?error:\s*", extension_errors[0], maxsplit=1)[-1]
                message = html.escape(message[:200]).replace("|", "&#124;")
                extension_summary += f"| `{name}` | <code>{message}</code> |\n"
            extension_summary += (
                "\nOther extensions are not confirmed compatible: installation can stop at the first failure."
            )
        else:
            extension_summary = (
                "The failing extension could not be identified from compiler error source paths."
            )

    headline = "\n\n".join(
        [*identity, result, *([explanation, extension_summary] if explanation else []),
         *([reason] if reason else [])],
    )
    evidence = raw.strip() or "No readiness report was produced."
    if errors:
        evidence += "\n\n### Full captured error lines\n\n<pre>" + html.escape("\n".join(errors)) + "</pre>"
    attention = "Blocked:" in result or "Check failed" in result or "Unavailable;" in result
    heading = "## PHP minor upgrade readiness\n\n"
    notice = "> [!WARNING]\n> PHP minor upgrade needs attention; routine image updates remain separate.\n\n"
    details = "\n\n<details>\n<summary>Readiness evidence and full errors</summary>\n\n"
    compact = heading + headline + details + evidence + "\n\n</details>\n"
    highlighted = heading + (notice if attention else "") + headline
    highlighted += details + evidence + "\n\n</details>\n"
    return compact, highlighted


def main():
    """Read available evidence and append reports to GitHub Actions files."""
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--source", type=Path, required=True)
    parser.add_argument("--log", type=Path, required=True)
    parser.add_argument("--outcome", choices=("success", "failure", "cancelled", "skipped"), required=True)
    args = parser.parse_args()
    raw = args.source.read_text() if args.source.is_file() else ""
    log = args.log.read_text(errors="replace") if args.log.is_file() else ""
    compact, highlighted = reports(raw, log, args.outcome)

    with Path(os.environ["GITHUB_OUTPUT"]).open("a") as output:
        for name, value in (("report", compact), ("actions-report", highlighted)):
            delimiter = f"readiness-{uuid.uuid4()}"
            output.write(f"{name}<<{delimiter}\n{value}\n{delimiter}\n")
    with Path(os.environ["GITHUB_STEP_SUMMARY"]).open("a") as summary:
        summary.write(highlighted)


if __name__ == "__main__":
    main()
