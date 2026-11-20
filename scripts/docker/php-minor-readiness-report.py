#!/usr/bin/env python3
"""Publish PHP readiness evidence for separate maintenance jobs and PRs.

The workflow supplies the check's Markdown report, log, and step outcome.
GitHub job outputs carry the same evidence in compact PR and highlighted Actions
forms. Missing evidence is explicitly unavailable, never a successful check.
"""

import argparse
import html
import os
from pathlib import Path
import re
import uuid


def reports(raw, log, outcome):
    """Keep verified evidence shared; change only prominence and disclosure state."""
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
        line = re.sub(r"\x1b\[[0-9;]*m", "", line)
        line = re.sub(r"^#\d+ [\d.]+ ", "", line)
        if re.search(r"\berror:|^ERROR:|^\[FAIL\]|^::error::", line, re.IGNORECASE):
            line = line[:500]
            if line not in errors:
                errors.append(line)
            if len(errors) == 5:
                break
    if not reason and errors:
        reason = f"Reason: <code>{html.escape(errors[0])}</code>"
    if not reason and outcome != "success":
        reason = "Reason: The check did not complete; compatibility has not been established. See the job log."
    if not reason and not raw:
        reason = "Reason: No readiness evidence is available. See the job log."
    if not reason and "Blocked:" in result:
        reason = "Reason: No detailed diagnostic was captured. See the job log."

    explanation = ""
    if "Blocked: candidate development image did not build" in result:
        explanation = "**Build error:** The candidate PHP development image could not be built."
        diagnostics = html.unescape(reason + "\n" + "\n".join(errors))
        extension = re.search(r"/pear/temp/([A-Za-z0-9_-]+)/", diagnostics)
        if extension:
            explanation += (
                f" PHP extension `{extension[1]}` failed to compile against the candidate PHP runtime."
            )
        if "too many arguments to function 'php_verror'" in diagnostics:
            explanation += (
                " `php_verror` is called with more arguments than the candidate PHP API accepts."
            )
        explanation += " Composer compatibility checks did not run; the upgrade remains blocked."

    headline = "\n\n".join(
        [*identity, result, *([explanation] if explanation else []), *([reason] if reason else [])],
    )
    evidence = raw.strip() or "No readiness report was produced."
    if errors:
        evidence += "\n\n### Diagnostic excerpts\n\n<pre>" + html.escape("\n".join(errors)) + "</pre>"
    attention = "Blocked:" in result or "Check failed" in result or "Unavailable;" in result
    heading = "## PHP minor upgrade readiness\n\n"
    notice = "> [!WARNING]\n> PHP minor upgrade needs attention; routine image updates remain separate.\n\n"
    details = "\n\n<details{state}>\n<summary>Readiness evidence and diagnostics</summary>\n\n"
    compact = heading + headline + details.format(state="") + evidence + "\n\n</details>\n"
    highlighted = heading + (notice if attention else "") + headline
    highlighted += details.format(state=" open" if attention else "") + evidence + "\n\n</details>\n"
    return compact, highlighted


def main():
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
