#!/usr/bin/env python3
"""Test action and workflow-tool pin discovery, updates, and reporting offline.

Usage:
  From the repository root inside the PHP development container:
    python3 scripts/checks/tests/check-workflow-tool-updates-test.py

Requirements:
  Python 3, Bash, and standard Linux command-line tools. GitHub lookups use
  fixture data instead of the network. The unreadable-file case requires a
  non-root user.

Details:
  Each checker invocation uses temporary workflows, action metadata, and tool
  pins. Tests compare direct checker outcomes with the informational output
  wrapper, which can report updates or lookup errors without failing Make.

Callers:
  GitHub Actions:
    - `.github/workflows/main.yml` runs this suite directly in the
      `lint-configuration` job, separately from `make test-command-output`.
"""

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
                    composite_content=None, files=None, missing_directory=None,
                    find_status=None, unreadable_file=None):
        """Run the real checker against isolated inputs and capture its result.

        By default, only tool pins are checked. Pass an empty mode to check
        actions too, or enable wrapping to exercise Make's advisory policy.
        Nonzero exit codes are returned for assertions rather than raised.
        """
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
            for name, content in (files or {}).items():
                path = root / name
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text(content)
            if unreadable_file is not None:
                (root / unreadable_file).chmod(0)
            if missing_directory is not None:
                (root / missing_directory).rmdir()
            if find_status is not None:
                find = root / "find"
                find.write_text(f"#!/bin/bash\nexit {find_status}\n")
                find.chmod(0o755)
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
            # Tag discovery omits peeled refs; exact lookups can return them.
            # Reject unexpected calls instead of falling back to real Git.
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
                PATH=f"{root}:{os.environ['PATH']}",
            )
            command = ["bash", str(CHECKER), mode]
            if wrapped:
                output_wrapper = CHECKER.parents[1] / "output" / "action-update-output.sh"
                command = ["bash", str(output_wrapper), *command]
            return subprocess.run(
                command,
                env=environment, text=True, capture_output=True, check=False,
                timeout=10,
            )

    def test_inventory_is_offline_and_includes_full_pin(self):
        result = self.run_checker("", "--current-pins-md", git_status=1)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("example/tool@v1.0.0", result.stdout)
        self.assertIn(SHA[:8], result.stdout)

    def test_discovery_covers_yaml_variants_nested_metadata_and_spaced_paths(self):
        reference = f"  - uses: example/action@{SHA} # v1.0.0\n"
        files = {
            "workflows/check.yml": reference,
            "workflows/another check.yaml": reference,
            "actions/bootstrap/action.yml": reference,
            "actions/nested/with spaces/action.yaml": reference,
            "actions/root/action.yaml": reference,
        }
        ignored = {
            "workflows/nested/ignored.yml": reference.replace("example/action", "ignored/workflow"),
            "workflows/ignored.txt": reference.replace("example/action", "ignored/text"),
            "actions/nested/ignored.yml": reference.replace("example/action", "ignored/metadata"),
        }
        result = self.run_checker(
            "", mode="--current-pins-md", git_status=99, files={**files, **ignored},
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.count("| example/action@v1.0.0 |"), 1)
        for path in files:
            self.assertIn(f"{path}:1", result.stdout)
        self.assertNotIn("ignored/", result.stdout)

    def test_nested_yaml_conflicts_preserve_first_pin_and_source_diagnostics(self):
        result = self.run_checker(
            "", mode="--current-pins-md", action=True,
            files={"actions/nested/bootstrap/action.yaml":
                   f"  - uses: example/action@{NEW_SHA} # v1.0.0\n"},
        )
        self.assertEqual(result.returncode, 2, result.stdout)
        self.assertIn("workflows/check.yml:2", result.stderr)
        self.assertIn("actions/nested/bootstrap/action.yaml:1", result.stderr)
        self.assertIn(f"`{SHA[:8]}`", result.stdout)
        self.assertNotIn(f"`{NEW_SHA[:8]}`", result.stdout)

    def test_missing_directories_are_errors_but_other_sources_are_inventoried(self):
        for directory in ("workflows", "actions"):
            remaining_source = (
                "actions/local/action.yaml" if directory == "workflows"
                else "workflows/check.yaml"
            )
            for wrapped in (False, True):
                with self.subTest(directory=directory, wrapped=wrapped):
                    result = self.run_checker(
                        f"{SHA}\trefs/tags/v1.0.0", mode="",
                        missing_directory=directory, wrapped=wrapped,
                        files={remaining_source:
                               f"  - uses: example/action@{SHA} # v1.0.0\n"},
                    )
                    self.assertEqual(result.returncode, 0 if wrapped else 2, result.stdout)
                    self.assertIn("directory not found", result.stderr)
                    self.assertIn("example/action", result.stdout)
                    self.assertIn("example/tool", result.stdout)

    def test_unreadable_metadata_is_an_error_and_other_sources_continue(self):
        path = "actions/nested/action.yaml"
        result = self.run_checker(
            "", mode="--current-pins-md", action=True,
            files={path: f"  - uses: example/action@{SHA} # v1.0.0\n"},
            unreadable_file=path,
        )
        self.assertEqual(result.returncode, 2, result.stdout)
        self.assertIn("Cannot read action references:", result.stderr)
        self.assertIn(path, result.stderr)
        self.assertIn("example/action", result.stdout)
        self.assertIn("example/tool", result.stdout)

    def test_failed_inventory_is_an_error_and_tools_only_skips_action_discovery(self):
        result = self.run_checker("", mode="--current-pins-md", find_status=7)
        self.assertEqual(result.returncode, 2, result.stdout)
        self.assertIn("Cannot discover Workflow files", result.stderr)
        self.assertIn("Cannot discover Actions files", result.stderr)
        self.assertIn("example/tool", result.stdout)
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", missing_directory="workflows", find_status=7,
        )
        self.assertEqual(result.returncode, 0, result.stderr)

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
            "GitHub lookup succeeded, but no usable stable version tags", result.stderr,
        )
        self.assertNotIn("git ls-remote failed", result.stderr)
        self.assertNotIn("timed out", result.stderr)

    def test_major_only_action_tags_check_current_moved_and_newer_pins(self):
        workflow = f"steps:\n  - uses: example/action@{SHA} # v5\n"
        for action_refs, expected, message in (
            (f"{SHA}\trefs/tags/v5", 0, "v5 (up-to-date)"),
            (f"{TAG_SHA}\trefs/tags/v5\n{SHA}\trefs/tags/v5^{{}}",
             0, "v5 (up-to-date)"),
            (f"{NEW_SHA}\trefs/tags/v5", 1, "Pin differs from tag commit (same version)"),
            (f"{SHA}\trefs/tags/v5\n{NEW_SHA}\trefs/tags/v6", 1, "v5 -> v6"),
        ):
            with self.subTest(action_refs=action_refs):
                result = self.run_checker(
                    f"{SHA}\trefs/tags/v1.0.0", mode="",
                    workflow_content=workflow, action_refs=action_refs,
                )
                self.assertEqual(result.returncode, expected, result.stderr)
                self.assertIn(message, result.stdout)
                if expected == 1:
                    self.assertIn(f"SHA:   {NEW_SHA}", result.stdout)

    def test_major_only_fallback_never_selects_nonversion_or_prerelease_tags(self):
        workflow = f"steps:\n  - uses: example/action@{SHA} # v5\n"
        for action_refs in (
            f"{SHA}\trefs/tags/verbose\n{SHA}\trefs/tags/v6-rc1",
            "",
        ):
            with self.subTest(action_refs=action_refs):
                result = self.run_checker(
                    f"{SHA}\trefs/tags/v1.0.0", mode="",
                    workflow_content=workflow, action_refs=action_refs,
                )
                self.assertEqual(result.returncode, 2, result.stdout)
                self.assertIn("no usable stable version tags", result.stderr)

    def test_action_candidates_exclude_prereleases_and_malformed_suffixes(self):
        excluded = (
            "v9.0.0-rc1", "v9.0.0-beta.2", "v9.0.0-alpha", "v9.0.0-preview",
            "v9.0.0+build", "v9.0.0junk", "v9.0.0.1", "v9.1-rc1", "v99",
        )
        for stable in ("v2.0.0", "2.0.0", "v2.1", "2.1"):
            with self.subTest(stable=stable):
                current = "v1.0.0" if stable.startswith("v") else "1.0.0"
                result = self.run_checker(
                    f"{SHA}\trefs/tags/v1.0.0", mode="", action=True,
                    workflow_content=f"steps:\n  - uses: example/action@{SHA} # {current}\n",
                    action_refs=(
                        f"{SHA}\trefs/tags/{current}\n{NEW_SHA}\trefs/tags/{stable}\n"
                        + "\n".join(f"{TAG_SHA}\trefs/tags/{tag}" for tag in excluded)
                    ),
                    action_sha_refs=f"{NEW_SHA}\trefs/tags/{stable}",
                )
                self.assertEqual(result.returncode, 1, result.stderr)
                self.assertIn(f"{current} -> {stable}", result.stdout)
                self.assertIn(f"SHA:   {NEW_SHA}", result.stdout)
                for tag in excluded:
                    self.assertNotIn(f"-> {tag}", result.stdout)

    def test_prerelease_only_action_candidates_are_explicit_errors(self):
        for wrapped in (False, True):
            with self.subTest(wrapped=wrapped):
                result = self.run_checker(
                    f"{SHA}\trefs/tags/v1.0.0", mode="", action=True, wrapped=wrapped,
                    action_refs=f"{NEW_SHA}\trefs/tags/v2.0.0-rc1",
                )
                self.assertEqual(result.returncode, 0 if wrapped else 2, result.stderr)
                self.assertIn("no usable stable version tags", result.stderr)
                self.assertNotIn("[OK] example/action", result.stdout)

    def test_mixed_prefix_action_tags_use_numeric_order_and_exact_commit(self):
        for older, newer in (
            ("v1.0.0", "2.0.0"), ("1.0.0", "v2.0.0"),
            ("v2.9", "2.10"), ("2.9", "v2.10"),
            ("v2.9.9", "2.10.0"), ("2.9.9", "v2.10.0"),
            ("v9", "10"), ("9", "v10"),
        ):
            for current in (older, newer):
                for wrapped in (False, True):
                    with self.subTest(older=older, newer=newer, current=current, wrapped=wrapped):
                        current_sha = SHA if current == older else NEW_SHA
                        # The tag listing exposes the tag object; the exact
                        # lookup must supply the commit used for the pin.
                        result = self.run_checker(
                            f"{SHA}\trefs/tags/v1.0.0", mode="", wrapped=wrapped,
                            workflow_content=(
                                f"steps:\n  - uses: example/action@{current_sha} # {current}\n"
                            ),
                            action_refs=f"{SHA}\trefs/tags/{older}\n{TAG_SHA}\trefs/tags/{newer}",
                            action_sha_refs=(
                                f"{TAG_SHA}\trefs/tags/{newer}\n"
                                f"{NEW_SHA}\trefs/tags/{newer}^{{}}"
                            ),
                        )
                        self.assertEqual(
                            result.returncode, 1 if current == older and not wrapped else 0,
                            result.stdout + result.stderr,
                        )
                        if current == older:
                            self.assertIn(f"{older} -> {newer}", result.stdout)
                            self.assertIn(f"SHA:   {NEW_SHA}", result.stdout)
                        else:
                            self.assertIn(f"{newer} (up-to-date)", result.stdout)
                            self.assertNotIn("UPDATE AVAILABLE", result.stdout)

    def test_equivalent_action_tag_prefixes_have_deterministic_preference(self):
        for version in ("2.0.0", "2.1", "2"):
            refs = [f"{TAG_SHA}\trefs/tags/{version}", f"{SHA}\trefs/tags/v{version}"]
            for ordered in (refs, list(reversed(refs))):
                with self.subTest(version=version, refs=ordered):
                    result = self.run_checker(
                        f"{SHA}\trefs/tags/v1.0.0", mode="",
                        workflow_content=f"steps:\n  - uses: example/action@{SHA} # v{version}\n",
                        action_refs="\n".join(ordered),
                    )
                    self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                    self.assertIn(f"v{version} (up-to-date)", result.stdout)

    def test_major_fallback_ignores_full_prereleases(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="",
            workflow_content=f"steps:\n  - uses: example/action@{SHA} # v5\n",
            action_refs=f"{SHA}\trefs/tags/v5\n{NEW_SHA}\trefs/tags/v6.0.0-rc1",
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("v5 (up-to-date)", result.stdout)

    def test_major_pin_prefers_full_version_tags_when_available(self):
        result = self.run_checker(
            f"{SHA}\trefs/tags/v1.0.0", mode="",
            workflow_content=f"steps:\n  - uses: example/action@{SHA} # v5\n",
            action_refs=f"{SHA}\trefs/tags/v5\n{NEW_SHA}\trefs/tags/v5.1.0",
        )
        self.assertEqual(result.returncode, 1, result.stderr)
        self.assertIn("v5 -> v5.1.0", result.stdout)

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
                content = ignored + f"  - uses: example/action@{SHA} # v1.0.0\n"
                if source == "workflow_content":
                    result = self.run_checker(
                        "", mode="--current-pins-md", workflow_content=content,
                    )
                else:
                    result = self.run_checker(
                        "", mode="--current-pins-md", composite_content=content,
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
                content = (
                    "  - uses: example/action@v1.0.0\n"
                    f"  - uses: example/unknown@{'a' * 40}\n"
                )
                if source == "workflow_content":
                    result = self.run_checker(
                        "", mode="--current-pins-md", workflow_content=content,
                    )
                else:
                    result = self.run_checker(
                        "", mode="--current-pins-md", composite_content=content,
                    )
                self.assertEqual(result.returncode, 2, result.stdout)
                self.assertIn("Unpinned action reference", result.stderr)
                self.assertIn("Cannot determine version for example/unknown", result.stderr)
                self.assertIn("| example/action@v1.0.0 |", result.stdout)
                self.assertNotIn("| example/unknown@", result.stdout)


if __name__ == "__main__":
    unittest.main()
