#!/usr/bin/env python3
"""ShellCheck Bash/sh steps in local composite actions without executing them."""

import argparse
from pathlib import Path
import re
import shlex
import subprocess
import sys

import yaml


def check_action(path, shellcheck):
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
