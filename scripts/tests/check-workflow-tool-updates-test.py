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
    def run_checker(self, refs, mode="--tools-only", pin=None, git_status=0,
                    action=False, color=False, sha_failure=False):
        with tempfile.TemporaryDirectory(prefix="tool-update-test-") as directory:
            root = Path(directory)
            (root / "workflows").mkdir()
            (root / "actions").mkdir()
            if action:
                (root / "workflows/check.yml").write_text(
                    f"steps:\n  - uses: example/action@{SHA} # v1.0.0\n"
                )
            pins = root / "pins.sh"
            pins.write_text(
                pin if pin is not None
                else f"FIXTURE_TAG=v1.0.0 # tool-pin: example/tool@{SHA}\n"
            )
            (root / "refs").write_text(refs)
            git = root / "git"
            git.write_text(
                '#!/bin/bash\n'
                + ('if [[ "$*" == *example/action.git* && "$*" != *--refs* ]]; then exit 1; fi\n'
                   if sha_failure else '')
                + f'cat "{root}/refs"\nexit {git_status}\n'
            )
            git.chmod(0o755)
            environment = dict(
                os.environ,
                GIT_BIN=str(git),
                TOOL_PINS_FILE=str(pins),
                WORKFLOW_DIR=str(root / "workflows"),
                ACTIONS_DIR=str(root / "actions"),
                NO_COLOR="" if color else "1",
                COLOR="always" if color else "never",
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

    def test_action_lookup_failures_are_not_reported_as_up_to_date(self):
        result = self.run_checker("", mode="", git_status=1, action=True)
        self.assertEqual(result.returncode, 2)
        self.assertIn("ERROR: Unable to fetch action version: example/action", result.stderr)
        self.assertNotIn("All checked actions and tools are up-to-date", result.stdout)

    def test_available_tool_updates_are_yellow(self):
        refs = f"{SHA}\trefs/tags/v1.0.0\n{NEW_SHA}\trefs/tags/v2.0.0"
        result = self.run_checker(refs, color=True)
        self.assertEqual(result.returncode, 1)
        self.assertIn("\033[1;33mUPDATE AVAILABLE (tool):", result.stdout)
        self.assertIn("Updates available:       \033[1;33m1", result.stdout)

    def test_action_sha_lookup_failure_is_a_red_discovery_error(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
            color=True, sha_failure=True,
        )
        self.assertEqual(result.returncode, 2)
        self.assertIn("\033[0;31mERROR: Unable to fetch action SHA:", result.stderr)
        self.assertNotIn("All checked actions and tools are up-to-date", result.stdout)


if __name__ == "__main__":
    unittest.main()
