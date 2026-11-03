#!/usr/bin/env python3
"""Offline checks of CI gate cancellation guards and actual result scripts."""

import os
from pathlib import Path
import re
import subprocess
import textwrap
import unittest


WORKFLOW = Path(__file__).resolve().parents[2] / ".github/workflows/main.yml"
METADATA_EDIT = (
    "github.event_name == 'pull_request' && github.event.action == 'edited' && "
    "github.event.changes.base == null && "
    "(github.event.changes.title != null || github.event.changes.body != null)"
)


class CiGatesTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.workflow = WORKFLOW.read_text()
        cls.jobs = dict(re.findall(
            r"^  ([\w-]+):\n(.*?)(?=^  [\w-]+:|\Z)",
            cls.workflow, re.MULTILINE | re.DOTALL,
        ))

    def test_gates_run_after_failed_dependencies_but_not_cancelled_workflows(self):
        for job in ("ci-summary", "ci-result"):
            with self.subTest(job=job):
                conditions = re.findall(
                    r"^    if: (.+)$", self.jobs[job], re.MULTILINE,
                )
                # Keep failed prerequisites visible, release cancelled runs,
                # and skip only confirmed title/body-only PR edits.
                expected = "${{ !cancelled() && !(" + METADATA_EDIT + ") }}"
                self.assertEqual(conditions, [expected])

    def test_container_cleanup_remains_unconditional(self):
        cleanup = re.findall(
            r"^        name: Stop PHP development container\n"
            r"        if: (.+)\n"
            r"        run: bash scripts/make\.sh ci-stop$",
            self.workflow, re.MULTILINE,
        )
        self.assertTrue(cleanup, "Expected unconditional container cleanup")
        self.assertTrue(all(condition == "${{ always() }}" for condition in cleanup))

    def run_gate(self, job, environment):
        script = re.search(
            r"^        run: \|\n((?:^          .*\n|^\n)+)",
            self.jobs[job], re.MULTILINE,
        )
        self.assertIsNotNone(script, f"Missing result script for {job}")
        return subprocess.run(
            ["bash", "--noprofile", "--norc", "-eo", "pipefail", "-c",
             textwrap.dedent(script.group(1))],
            env=dict(os.environ, **environment),
            text=True, capture_output=True, check=False,
        )

    def test_actual_scripts_preserve_result_validation(self):
        for job in ("ci-summary", "ci-result"):
            result_names = re.findall(
                r"^          (\w+): \$\{\{ needs\.[\w-]+\.result \}\}$",
                self.jobs[job], re.MULTILINE,
            )
            self.assertTrue(result_names, f"Missing result bindings for {job}")
            for event in ("pull_request", "push", "merge_group",
                          "workflow_dispatch", "schedule"):
                environment = dict.fromkeys(result_names, "success")
                environment.update(EVENT_NAME=event, CODE_CHANGED="true")
                if event == "schedule":
                    periodic = {"SECURITY_CHECK", "DOCKER_UPDATES", "CI_SUMMARY"}
                    for name in result_names:
                        if name not in periodic:
                            environment[name] = "skipped"
                with self.subTest(job=job, event=event):
                    result = self.run_gate(job, environment)
                    self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                for name in result_names:
                    for status in ("failure", "cancelled", "skipped"):
                        if status == environment[name]:
                            continue
                        with self.subTest(job=job, event=event, name=name, status=status):
                            result = self.run_gate(job, dict(environment, **{name: status}))
                            self.assertNotEqual(result.returncode, 0, result.stdout)


if __name__ == "__main__":
    unittest.main()
