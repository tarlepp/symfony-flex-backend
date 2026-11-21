#!/usr/bin/env python3
"""Run one workflow regression suite with nested unittest diagnostics.

Usage:
  From the repository root on the Docker host or in the PHP development container:
    make test-command-output
  To run one suite from the repository root inside the container:
    python3 scripts/tests/run-workflow-tests.py scripts/ci/tests/ci-gates-test.py
  Additional arguments are passed to the loaded suite through sys.argv.

Details:
  Loads the supplied Python file as a module and runs its unittest tests.
  Runner diagnostics go to stderr with four-space indentation; the standalone
  OK footer and trailing blank lines are omitted. Test stdout is not filtered.

Exit Status:
  Returns 0 for a successful unittest result, 1 for an unsuccessful result,
  and 64 when no test file is supplied. Import and execution errors propagate.

Callers:
  Scripts:
    - `scripts/checks/lint-workflows.sh` runs workflow regression suites.
    - `scripts/tests/command-output-contract-test.sh` runs output contract suites.
"""

import importlib.util
import sys
import unittest


class NestedTestOutput:
    """Indent unittest diagnostics while suppressing the standalone OK footer."""

    def __init__(self, stream):
        self.stream = stream
        self.pending = ""
        self.blank_lines = 0

    def write(self, text):
        # Buffer incomplete lines and blank lines so a trailing footer is omitted.
        self.pending += text
        while "\n" in self.pending:
            line, self.pending = self.pending.split("\n", 1)
            if not line:
                self.blank_lines += 1
            elif line != "OK":
                self.stream.write("\n" * self.blank_lines)
                self.blank_lines = 0
                self.stream.write(f"    {line}\n")

    def flush(self):
        self.stream.flush()

    def finish(self):
        """Forward a final partial diagnostic and flush the underlying stream."""
        if self.pending:
            self.stream.write(f"    {self.pending}\n")
            self.pending = ""
        self.stream.flush()


def main():
    if len(sys.argv) < 2:
        sys.stderr.write("Usage: run-workflow-tests.py <test-file> [arguments...]\n")
        return 64

    # Leave suite-specific arguments available to the imported module and unittest.
    path = sys.argv.pop(1)
    sys.argv[0] = path
    spec = importlib.util.spec_from_file_location("workflow_regressions", path)
    if spec is None or spec.loader is None:
        raise ImportError(f"Could not load workflow test module: {path}")
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)

    output = NestedTestOutput(sys.stderr)
    runner = unittest.TextTestRunner(stream=output)
    try:
        program = unittest.main(module=module, testRunner=runner, exit=False)
    finally:
        output.finish()
    return 0 if program.result.wasSuccessful() else 1


if __name__ == "__main__":
    sys.exit(main())
