#!/usr/bin/env python3
"""Run one workflow regression suite with nested unittest diagnostics."""

import importlib.util
import sys
import unittest


class NestedTestOutput:
    def __init__(self, stream):
        self.stream = stream
        self.pending = ""
        self.blank_lines = 0

    def write(self, text):
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
        if self.pending:
            self.stream.write(f"    {self.pending}\n")
            self.pending = ""
        self.stream.flush()


def main():
    if len(sys.argv) < 2:
        sys.stderr.write("Usage: run-workflow-tests.py <test-file> [arguments...]\n")
        return 64

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
