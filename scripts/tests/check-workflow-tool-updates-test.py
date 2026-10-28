#!/usr/bin/env python3
"""Offline regression checks for workflow-tool release pin discovery."""

import os
from pathlib import Path
import subprocess
import tempfile
import unittest


CHECKER = Path(__file__).resolve().parents[1] / "check-action-updates.sh"
SHA = "1" * 40
NEW_SHA = "2" * 40
TAG_SHA = "3" * 40


class ToolUpdatesTest(unittest.TestCase):
    def run_checker(self, refs, mode="--tools-only", pin=None, git_status=0):
        with tempfile.TemporaryDirectory(prefix="tool-update-test-") as directory:
            root = Path(directory)
            (root / "workflows").mkdir()
            (root / "actions").mkdir()
            pins = root / "pins.sh"
            pins.write_text(
                pin if pin is not None
                else f"FIXTURE_TAG=v1.0.0 # tool-pin: example/tool@{SHA}\n"
            )
            (root / "refs").write_text(refs)
            git = root / "git"
            git.write_text(
                f'#!/bin/bash\ncat "{root}/refs"\nexit {git_status}\n'
            )
            git.chmod(0o755)
            environment = dict(
                os.environ,
                GIT_BIN=str(git),
                TOOL_PINS_FILE=str(pins),
                WORKFLOW_DIR=str(root / "workflows"),
                ACTIONS_DIR=str(root / "actions"),
                NO_COLOR="1",
            )
            return subprocess.run(
                ["bash", str(CHECKER), mode],
                env=environment, text=True, capture_output=True, check=False,
            )

    def test_inventory_is_offline_and_includes_full_pin(self):
        result = self.run_checker("", "--current-pins-md", git_status=1)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("example/tool@v1.0.0", result.stdout)
        self.assertIn(SHA[:8], result.stdout)

    def test_lightweight_and_annotated_tags(self):
        for refs in (
            f"{SHA}\trefs/tags/v1.0.0",
            f"{TAG_SHA}\trefs/tags/v1.0.0\n{SHA}\trefs/tags/v1.0.0^{{}}",
        ):
            with self.subTest(refs=refs):
                result = self.run_checker(refs)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn("tag verified", result.stdout)

    def test_new_stable_tag_uses_peeled_commit_and_excludes_prerelease(self):
        refs = (
            f"{SHA}\trefs/tags/v1.0.0\n"
            f"{TAG_SHA}\trefs/tags/v2.0.0\n"
            f"{NEW_SHA}\trefs/tags/v2.0.0^{{}}\n"
            f"{TAG_SHA}\trefs/tags/v3.0.0-rc1"
        )
        result = self.run_checker(refs)
        self.assertEqual(result.returncode, 1, result.stderr)
        self.assertIn("v1.0.0 -> v2.0.0", result.stdout)
        self.assertIn(f"Commit SHA: {NEW_SHA}", result.stdout)
        self.assertNotIn("v3.0.0-rc1", result.stdout)

    def test_mismatch_missing_tag_and_lookup_failure_are_errors(self):
        for refs, status in (
            (f"{NEW_SHA}\trefs/tags/v1.0.0", 0),
            ("", 0),
            ("", 1),
        ):
            with self.subTest(refs=refs, status=status):
                result = self.run_checker(refs, git_status=status)
                self.assertEqual(result.returncode, 2, result.stdout)

    def test_malformed_and_absent_pins_are_errors(self):
        for pin in ("# no pins\n", "FIXTURE_TAG=v1 # tool-pin: example/tool@bad\n"):
            result = self.run_checker("", pin=pin)
            self.assertEqual(result.returncode, 2, result.stdout)


if __name__ == "__main__":
    unittest.main()
