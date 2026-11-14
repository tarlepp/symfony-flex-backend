#!/usr/bin/env python3
"""Offline regression checks for GitHub Action and workflow-tool updates."""

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
                    action=False, color=False, sha_failure=False, wrapped=False,
                    action_refs=None, action_path="example/action",
                    action_sha_refs=None, composite=False, git_error="",
                    sha_failure_status=1, workflow_content=None,
                    composite_content=None):
        with tempfile.TemporaryDirectory(prefix="tool-update-test-") as directory:
            root = Path(directory)
            (root / "workflows").mkdir()
            (root / "actions").mkdir()
            if action or workflow_content is not None:
                (root / "workflows/check.yml").write_text(
                    workflow_content if workflow_content is not None
                    else f"steps:\n  - uses: {action_path}@{SHA} # v1.0.0\n"
                )
            if composite or composite_content is not None:
                (root / "actions/bootstrap").mkdir()
                (root / "actions/bootstrap/action.yml").write_text(
                    composite_content if composite_content is not None
                    else f"runs:\n  steps:\n    - uses: {action_path}@{SHA} # v1.0.0\n"
                )
            pins = root / "pins.sh"
            pins.write_text(
                pin if pin is not None
                else f"FIXTURE_TAG=v1.0.0 # tool-pin: example/tool@{SHA}\n"
            )
            (root / "refs").write_text(refs)
            (root / "action-refs").write_text(
                refs if action_refs is None else action_refs
            )
            (root / "action-sha-refs").write_text(
                (refs if action_refs is None else action_refs)
                if action_sha_refs is None else action_sha_refs
            )
            git = root / "git"
            (root / "git-error").write_text(git_error)
            git.write_text(
                '#!/bin/bash\n'
                + f'cat "{root}/git-error" >&2\n'
                + (f'if [[ "$*" == *example/action.git* && "$*" != *--refs* ]]; then exit {sha_failure_status}; fi\n'
                   if sha_failure else '')
                + '[[ "$1" == ls-remote ]] || exit 64\n'
                + 'case "$*" in\n'
                + '  *https://github.com/example/action.git*)\n'
                + '    if [[ "$*" == *--refs* ]]; then\n'
                + f'      grep -v \'\\^{{}}$\' "{root}/action-refs"\n'
                + '    else\n'
                + '      [[ "$*" == *"^{}"* ]] || exit 64\n'
                + f'      cat "{root}/action-sha-refs"\n'
                + '    fi ;;\n'
                + f'  *https://github.com/example/tool.git*) cat "{root}/refs" ;;\n'
                + '  *) exit 64 ;;\n'
                + f'esac\nexit {git_status}\n'
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
            command = ["bash", str(CHECKER), mode]
            if wrapped:
                command = ["bash", str(CHECKER.parent / "action-update-output.sh"), *command]
            return subprocess.run(
                command,
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
        self.assertIn("[FAIL] Unable to fetch action version: example/action", result.stderr)
        self.assertNotIn("All checked actions and tools are up-to-date", result.stdout)

    def test_available_tool_updates_are_yellow(self):
        refs = f"{SHA}\trefs/tags/v1.0.0\n{NEW_SHA}\trefs/tags/v2.0.0"
        result = self.run_checker(refs, color=True)
        self.assertEqual(result.returncode, 1)
        self.assertIn("\033[33m[WARN]\033[0m UPDATE AVAILABLE (tool):", result.stdout)
        self.assertIn("Updates available:       \033[33m1", result.stdout)

    def test_wrapped_checker_prints_only_one_final_outcome(self):
        for refs, git_status, label, direct_status in (
            (f"{SHA}\trefs/tags/v1.0.0", 0, "OK", 0),
            (f"{SHA}\trefs/tags/v1.0.0\n{NEW_SHA}\trefs/tags/v2.0.0", 0, "WARN", 1),
            ("", 1, "FAIL", 2),
        ):
            with self.subTest(label=label):
                direct = self.run_checker(refs, git_status=git_status)
                wrapped = self.run_checker(refs, git_status=git_status, wrapped=True)
                self.assertEqual(direct.returncode, direct_status, direct.stderr)
                self.assertEqual(wrapped.returncode, 0, wrapped.stderr)
                self.assertEqual(direct.stderr.count(f"\n\n[{label}]"), 1)
                self.assertEqual(wrapped.stderr.count(f"\n\n[{label}]"), 1)
                self.assertIn("Action/tool update summary", wrapped.stderr)
                self.assertIn("Actions/tools checked:", wrapped.stdout)
                self.assertNotIn("All checked actions and tools are up-to-date", wrapped.stderr)
                self.assertNotIn("action/tool update(s) available", wrapped.stderr)
                if label == "FAIL":
                    self.assertIn("review the failed entries above", wrapped.stderr)
                    self.assertIn("retry failed GitHub lookups", wrapped.stderr)
                    self.assertIn("informational Make target remains successful", wrapped.stderr)
                    self.assertIn("[FAIL] Unable to fetch tool tags:", wrapped.stderr)

    def test_action_sha_lookup_failure_is_a_red_discovery_error(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
            color=True, sha_failure=True,
        )
        self.assertEqual(result.returncode, 2)
        self.assertIn("\033[31m[FAIL]\033[0m Unable to fetch action SHA:", result.stderr)
        self.assertNotIn("All checked actions and tools are up-to-date", result.stdout)

    def test_timeout_diagnostics_show_repository_and_limit(self):
        result = self.run_checker(
            "", mode="", action=True, git_status=124,
        )
        self.assertEqual(result.returncode, 2, result.stdout)
        self.assertIn(
            "example/action @ v1.0.0 (GitHub lookup timed out after 10s",
            result.stderr,
        )
        self.assertIn(
            "example/tool (GitHub lookup timed out after 20s",
            result.stderr,
        )
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
            sha_failure=True, sha_failure_status=124,
        )
        self.assertEqual(result.returncode, 2, result.stdout)
        self.assertIn(
            "Unable to fetch action SHA: example/action @ v1.0.0 "
            "(GitHub lookup timed out after 10s",
            result.stderr,
        )

    def test_git_failures_preserve_exit_status_and_native_diagnostic(self):
        for wrapped in (False, True):
            with self.subTest(wrapped=wrapped):
                result = self.run_checker(
                    "", mode="", action=True, git_status=128,
                    git_error="fatal: Could not resolve host: github.com\n",
                    wrapped=wrapped,
                )
                self.assertEqual(result.returncode, 0 if wrapped else 2)
                self.assertIn("git ls-remote failed with exit 128", result.stderr)
                self.assertIn(
                    "Git diagnostic: fatal: Could not resolve host: github.com",
                    result.stderr,
                )
                self.assertNotIn("timed out", result.stderr)

    def test_successful_lookup_without_version_tags_is_not_network_failure(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
            action_refs=f"{SHA}\trefs/tags/v5",
        )
        self.assertEqual(result.returncode, 2, result.stdout)
        self.assertIn(
            "GitHub lookup succeeded, but no version-like tags", result.stderr,
        )
        self.assertNotIn("git ls-remote failed", result.stderr)
        self.assertNotIn("timed out", result.stderr)

    def test_action_matching_lightweight_or_peeled_commit_is_current(self):
        for refs in (
            f"{SHA}\trefs/tags/v1.0.0",
            f"{TAG_SHA}\trefs/tags/v1.0.0\n{SHA}\trefs/tags/v1.0.0^{{}}",
        ):
            with self.subTest(refs=refs):
                result = self.run_checker(refs, mode="", action=True)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn("[OK] example/action", result.stdout)
                self.assertNotIn("UPDATE AVAILABLE", result.stdout)

    def test_same_version_pin_difference_reports_commit_and_source(self):
        for refs in (
            f"{NEW_SHA}\trefs/tags/v1.0.0",
            f"{SHA}\trefs/tags/v1.0.0\n{NEW_SHA}\trefs/tags/v1.0.0^{{}}",
        ):
            with self.subTest(refs=refs):
                result = self.run_checker(
                    f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
                    action_refs=refs,
                )
                self.assertEqual(result.returncode, 1, result.stderr)
                self.assertIn("v1.0.0 -> v1.0.0", result.stdout)
                self.assertIn("Pin differs from tag commit (same version)", result.stdout)
                self.assertIn(f"Pinned SHA: {SHA}", result.stdout)
                self.assertIn(f"SHA:   {NEW_SHA}", result.stdout)
                self.assertIn("workflows/check.yml:2", result.stdout)
                self.assertIn("Updates available:       1", result.stdout)
                self.assertNotIn("[OK] example/action", result.stdout)
                wrapped = self.run_checker(
                    f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
                    action_refs=refs, wrapped=True,
                )
                self.assertEqual(wrapped.returncode, 0, wrapped.stderr)
                self.assertIn("UPDATE AVAILABLE: example/action", wrapped.stdout)
                self.assertIn("[WARN]", wrapped.stderr)

    def test_new_action_version_resolves_exact_annotated_subpath_tag(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
            action_path="example/action/init",
            action_refs=(
                f"{SHA}\trefs/tags/v1.0.0\n"
                f"{TAG_SHA}\trefs/tags/v2.0.0\n"
                f"{NEW_SHA}\trefs/tags/v2.0.0^{{}}"
            ),
        )
        self.assertEqual(result.returncode, 1, result.stderr)
        self.assertIn("UPDATE AVAILABLE: example/action/init", result.stdout)
        self.assertIn("v1.0.0 -> v2.0.0", result.stdout)
        self.assertIn(f"SHA:   {NEW_SHA}", result.stdout)
        self.assertNotIn(f"SHA:   {TAG_SHA}", result.stdout)

    def test_same_version_update_groups_workflow_and_composite_sources(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="", action=True, composite=True,
            action_refs=f"{NEW_SHA}\trefs/tags/v1.0.0",
        )
        self.assertEqual(result.returncode, 1, result.stderr)
        self.assertIn("workflows/check.yml:2", result.stdout)
        self.assertIn("actions/bootstrap/action.yml:3", result.stdout)
        self.assertEqual(result.stdout.count("UPDATE AVAILABLE: example/action"), 1)
        self.assertIn("Updates available:       1", result.stdout)

    def test_missing_or_malformed_action_commit_is_an_error(self):
        for refs in (
            f"{NEW_SHA}\trefs/tags/v1.0.0-unrelated",
            "invalid\trefs/tags/v1.0.0",
            f"{SHA}\trefs/tags/v1.0.0\ninvalid\trefs/tags/v1.0.0^{{}}",
        ):
            with self.subTest(refs=refs):
                result = self.run_checker(
                    f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
                    action_sha_refs=refs,
                )
                self.assertEqual(result.returncode, 2, result.stdout)
                self.assertIn("Unable to fetch action SHA:", result.stderr)
                self.assertIn(
                    "exact tag resolved to a missing or invalid", result.stderr,
                )
                self.assertNotIn("[OK] example/action", result.stdout)

    def test_multiple_versions_share_one_heading_and_keep_their_sources(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0",
            mode="",
            workflow_content=(
                f"steps:\n  - uses: example/action@{SHA} # v1.0.0\n"
                f"  - uses: example/action@{NEW_SHA} # v2.0.0\n"
            ),
            composite=True,
            action_refs=(
                f"{SHA}\trefs/tags/v1.0.0\n"
                f"{TAG_SHA}\trefs/tags/v2.0.0\n"
                f"{NEW_SHA}\trefs/tags/v2.0.0^{{}}"
            ),
        )
        self.assertEqual(result.returncode, 1, result.stderr)
        self.assertEqual(result.stdout.count("UPDATE AVAILABLE: example/action"), 1)
        self.assertIn("v1.0.0 -> v2.0.0", result.stdout)
        self.assertIn("v2.0.0 (up-to-date)", result.stdout)
        self.assertIn("Source: ", result.stdout)
        self.assertIn("workflows/check.yml:2", result.stdout)
        self.assertIn("actions/bootstrap/action.yml:3", result.stdout)
        self.assertNotIn("workflows/check.yml:3", result.stdout)
        self.assertIn("Actions/tools checked:   3", result.stdout)
        self.assertIn("Up-to-date:              2", result.stdout)
        self.assertIn("Updates available:       1", result.stdout)

    def test_both_discovery_sources_ignore_local_and_reusable_references(self):
        ignored = (
            "  - uses: ./local/action\n"
            "  - uses: ../other/action\n"
            "  - uses: example/repo/.github/workflows/check.yml@main\n"
            "  - uses: invalid\n"
        )
        for source in ("workflow_content", "composite_content"):
            with self.subTest(source=source):
                result = self.run_checker(
                    "", mode="--current-pins-md",
                    **{source: ignored + f"  - uses: example/action@{SHA} # v1.0.0\n"},
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout.count("| example/action@v1.0.0 |"), 1)
                self.assertNotIn("local/action", result.stdout)
                self.assertNotIn("other/action", result.stdout)
                self.assertNotIn("example/repo", result.stdout)

    def test_conflicting_composite_pin_keeps_first_ref_and_reports_sources(self):
        result = self.run_checker(
            "", mode="--current-pins-md", action=True,
            composite_content=f"  - uses: example/action@{NEW_SHA} # v1.0.0\n",
        )
        self.assertEqual(result.returncode, 2, result.stdout)
        self.assertIn("Conflicting pinned refs detected", result.stderr)
        self.assertIn("workflows/check.yml:2", result.stderr)
        self.assertIn("actions/bootstrap/action.yml:1", result.stderr)
        self.assertIn(f"`{SHA[:8]}`", result.stdout)
        self.assertNotIn(f"`{NEW_SHA[:8]}`", result.stdout)

    def test_both_discovery_sources_preserve_unpinned_and_missing_version_warnings(self):
        for source in ("workflow_content", "composite_content"):
            with self.subTest(source=source):
                result = self.run_checker(
                    "", mode="--current-pins-md",
                    **{source: (
                        "  - uses: example/action@v1.0.0\n"
                        f"  - uses: example/unknown@{'a' * 40}\n"
                    )},
                )
                self.assertEqual(result.returncode, 2, result.stdout)
                self.assertIn("Unpinned action reference", result.stderr)
                self.assertIn("Cannot determine version for example/unknown", result.stderr)
                self.assertIn("| example/action@v1.0.0 |", result.stdout)
                self.assertNotIn("| example/unknown@", result.stdout)


if __name__ == "__main__":
    unittest.main()
