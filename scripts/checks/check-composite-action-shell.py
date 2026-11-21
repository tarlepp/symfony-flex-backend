#!/usr/bin/env python3
"""ShellCheck Bash/sh steps in local composite actions without executing them."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make lint-workflows
#   Direct invocation from the repository root inside the container, with
#   ShellCheck on PATH:
#     python3 scripts/checks/check-composite-action-shell.py --shellcheck "$(command -v shellcheck)" .github/actions/php-bootstrap/action.yml
#
# Requirements:
#   PyYAML and a ShellCheck executable supplied via --shellcheck.
#
# Details:
#   Skips non-composite actions and steps without a run body. Replaces GitHub
#   expressions with placeholders before analysis; their values are not checked.
#
# Exit Status:
#   Returns zero if all checks pass or are skipped. Otherwise returns the
#   first failure status; metadata and tool-launch errors count as status 1.
#
# Callers:
#   Scripts:
#     - `scripts/checks/lint-workflows.sh` invokes it after metadata validation.
#     - `scripts/checks/tests/composite-action-shell-test.py` checks fixtures.
#   Makefile:
#     - `lint-workflows` runs the validator in the PHP development container.
#   GitHub Actions:
#     - `.github/workflows/main.yml` (`lint-workflows` job, via Make).

import argparse
from pathlib import Path
import re
import shlex
import subprocess
import sys

import yaml


def check_action(path, shellcheck):
    """Check all Bash/sh run steps, returning the first ShellCheck failure."""
    action = yaml.safe_load(path.read_text())
    if action["runs"]["using"] != "composite":
        return 0
    status = 0
    for index, step in enumerate(action["runs"]["steps"], 1):
        if "run" not in step:
            continue
        command = shlex.split(step["shell"])
        if not command:
            raise ValueError(f"{path}: step {index}: shell must not be empty")
        shell = command[0]
        if shell not in ("bash", "sh"):
            raise ValueError(f"{path}: step {index}: unsupported shell {shell!r}")
        print(f"{path}: step {index} ({step.get('name', step.get('id', 'run'))})",
              file=sys.stderr, flush=True)
        # GitHub substitutes expressions before the shell sees the script.
        script = re.sub(r"\$\{\{.*?\}\}", "expression", step["run"], flags=re.DOTALL)
        result = subprocess.run(
            [shellcheck, "--shell", shell, "-"], input=script, text=True, check=False,
        )
        if result.returncode and not status:
            status = result.returncode
    return status


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--shellcheck", required=True)
    parser.add_argument("actions", nargs="+", type=Path)
    args = parser.parse_args()
    status = 0
    for path in args.actions:
        try:
            result = check_action(path, args.shellcheck)
        except (OSError, yaml.YAMLError, ValueError, KeyError, TypeError) as error:
            print(f"::error file={path}::Composite action shell validation failed: {error}",
                  file=sys.stderr)
            result = 1
        if result and not status:
            status = result
    return status


if __name__ == "__main__":
    sys.exit(main())
