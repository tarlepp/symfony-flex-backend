#!/usr/bin/env python3
"""Validate PHPUnit report inputs and print the informational CI summary."""

import argparse
from pathlib import Path
import sys
import xml.etree.ElementTree as ET


JUNIT = Path("build/logs/junit.xml")
CLOVER = Path("build/logs/clover.xml")
REQUIRED_REPORTS = (
    CLOVER, JUNIT, Path("build/report/index.html"), Path("build/phpmetrics/index.html"),
)


def count_metric(node, name):
    value = node.get(name, "")
    if not value.isascii() or not value.isdecimal():
        raise ValueError(f"{node.tag} requires a nonnegative integer {name} metric.")
    return int(value)


def validate_junit():
    report = ET.parse(JUNIT).getroot()
    if report.tag != "testsuites" or report.find("testsuite") is None:
        raise ValueError("JUnit report is not usable for PhpMetrics.")
    for suite in report.iter("testsuite"):
        for name in ("tests", "assertions", "failures", "errors", "skipped"):
            count_metric(suite, name)
    return 0


def validate_clover():
    report = ET.parse(CLOVER).getroot()
    metrics = report.find("./project/metrics")
    if report.tag != "coverage" or metrics is None:
        raise ValueError("Clover report requires a coverage root and project metrics.")
    statements = count_metric(metrics, "statements")
    covered = count_metric(metrics, "coveredstatements")
    if covered > statements:
        raise ValueError("Clover coveredstatements exceeds statements.")
    return 0


def verify_reports():
    invalid = False
    validators = {JUNIT: validate_junit, CLOVER: validate_clover}
    for report in REQUIRED_REPORTS:
        try:
            if not report.is_file() or report.stat().st_size == 0:
                raise ValueError(f"Required report is missing or empty: {report}")
            if report in validators:
                validators[report]()
        except (OSError, ET.ParseError, ValueError) as error:
            print(
                f"::error file={report}::{error}",
                file=sys.stderr,
            )
            invalid = True
    return int(invalid)


def print_summary():
    suite = ET.parse(JUNIT).getroot()
    while suite.tag == "testsuites" and len(suite) == 1:
        suite = suite[0]
    totals = {key: 0 for key in ("tests", "assertions", "failures", "errors", "skipped")}
    for node in ([suite] if suite.tag == "testsuite" else list(suite)):
        for key in totals:
            totals[key] += int(node.get(key, 0))

    metrics = ET.parse(CLOVER).getroot().find("./project/metrics")
    print("### PHPUnit results")
    print()
    print("| Tests | Assertions | Failures | Errors | Skipped |")
    print("|---|---|---|---|---|")
    print("| " + " | ".join(str(totals[key]) for key in totals) + " |")
    if metrics is not None:
        statements = int(metrics.get("statements", 0))
        covered = int(metrics.get("coveredstatements", 0))
        if statements:
            print()
            print(f"Line coverage: **{covered / statements:.2%}** ({covered}/{statements} statements)")
    return 0


def main():
    operations = {
        "validate-junit": validate_junit,
        "verify": verify_reports,
        "summary": print_summary,
    }
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("operation", choices=operations)
    arguments = parser.parse_args()
    try:
        return operations[arguments.operation]()
    except (OSError, ET.ParseError, ValueError) as error:
        print(f"::error::Test report {arguments.operation} failed: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
