#!/usr/bin/env python3
"""Offline checks of CI gate cancellation guards and actual result scripts."""

import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import unittest


WORKFLOW = Path(__file__).resolve().parents[2] / ".github/workflows/main.yml"


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
                # Keep failed prerequisites visible and release cancelled runs.
                self.assertEqual(conditions, ["${{ !cancelled() }}"])

    def test_container_cleanup_remains_unconditional(self):
        cleanup = re.findall(
            r"^        name: Stop PHP development container\n"
            r"        if: (.+)\n"
            r"        run: bash scripts/make\.sh ci-stop$",
            self.workflow, re.MULTILINE,
        )
        self.assertTrue(cleanup, "Expected unconditional container cleanup")
        self.assertTrue(all(condition == "${{ always() }}" for condition in cleanup))

    def test_every_bootstrap_consumer_finishes_with_container_cleanup(self):
        consumers = {
            name: body for name, body in self.jobs.items()
            if "uses: ./.github/actions/php-bootstrap\n" in body
        }
        self.assertIn("test", consumers)
        for name, body in consumers.items():
            with self.subTest(job=name):
                if name == "lint-configuration":
                    self.assertTrue(body.rstrip().endswith("run: bash scripts/make.sh ci-stop"))
                elif name == "report":
                    self.assertTrue(body.rstrip().endswith("run: bash scripts/make.sh ci-stop"))
                    self.assertIn(
                        "if: ${{ always() && steps.bootstrap.outcome != 'skipped' }}", body,
                    )
                else:
                    self.assertTrue(body.rstrip().endswith("- *stop_php_step"))
                    self.assertEqual(body.count("- *stop_php_step"), 1)

    def test_gate_scripts_are_checked_out_without_prerequisite_success_guards(self):
        for job, script in (
            ("ci-summary", "ci-prerequisite-gate.sh"),
            ("ci-result", "ci-result-gate.sh"),
        ):
            with self.subTest(job=job):
                steps = self.jobs[job].split("    steps:\n", 1)[1]
                self.assertEqual(
                    steps.split("        env:\n", 1)[0],
                    "      - *harden_runner_step\n\n"
                    "      - *checkout_step\n\n"
                    f"      - name: Validate {'prerequisite' if job == 'ci-summary' else 'final'} job results\n",
                )
                self.assertEqual(
                    re.findall(r"^        run: (.+)$", steps, re.MULTILINE),
                    [f"bash scripts/{script}"],
                )
                self.assertNotIn("        if:", steps)

    def test_every_php_consumer_diagnoses_failure_before_cleanup(self):
        for name, body in self.jobs.items():
            if "uses: ./.github/actions/php-bootstrap\n" not in body:
                continue
            with self.subTest(job=name):
                if name in ("lint-configuration", "report"):
                    self.assertLess(body.index("name: Diagnose PHP container failure"),
                                    body.index("name: Stop PHP development container"))
                    condition = "failure()" + (
                        " && steps.bootstrap.outcome != 'skipped'" if name == "report" else ""
                    )
                    self.assertIn(
                        f"        if: ${{{{ {condition} }}}}\n"
                        "        timeout-minutes: 2\n"
                        "        run: bash scripts/ci-failure-diagnostics.sh\n", body,
                    )
                else:
                    self.assertIn(
                        "      - *failure_diagnostics_step\n      - *stop_php_step\n", body,
                    )

    def test_full_history_is_limited_to_history_consumers(self):
        anchors = dict(re.findall(
            r"^      - &(checkout\w+)\n(.*?)(?=^      - |\Z)",
            self.workflow, re.MULTILINE | re.DOTALL,
        ))
        self.assertEqual(set(anchors), {"checkout_step", "checkout_full_history_step"})
        history_consumers = {"changes", "gitleaks", "lightweight-analyzers", "report"}
        self.assertTrue(history_consumers <= self.jobs.keys())
        job_names = re.findall(
            r"^  ([\w-]+):$", self.workflow.split("jobs:\n", 1)[1], re.MULTILINE,
        )
        for name in job_names:
            body = self.jobs[name]
            with self.subTest(job=name):
                checkouts = []
                for step in re.findall(
                    r"^      - (.*?)(?=^      - |\Z)", body, re.MULTILINE | re.DOTALL,
                ):
                    if step.strip().startswith("*checkout"):
                        step = anchors[step.strip()[1:]]
                    if "uses: actions/checkout@" in step:
                        checkouts.append(step)
                self.assertEqual(len(checkouts), 1)
                depth = "0" if name in history_consumers else "1"
                self.assertIn(f"          fetch-depth: '{depth}'\n", checkouts[0])
                self.assertIn("          persist-credentials: false\n", checkouts[0])
                self.assertRegex(checkouts[0], r"uses: actions/checkout@[a-f0-9]{40}\b")
                if name == "report":
                    self.assertIn("          ref: ${{ github.sha }}\n", checkouts[0])
                    self.assertIn(
                        "        if: ${{ steps.reporting.outputs.enabled == 'true' }}\n",
                        checkouts[0],
                    )

    def test_missing_shared_helper_or_required_inputs_fail_explicitly(self):
        for script in ("ci-prerequisite-gate.sh", "ci-result-gate.sh"):
            with self.subTest(script=script), tempfile.TemporaryDirectory() as directory:
                root = Path(directory)
                shutil.copyfile(WORKFLOW.parents[2] / "scripts" / script, root / script)
                environment = {"PATH": os.environ["PATH"]}
                missing_helper = subprocess.run(
                    ["bash", str(root / script)], cwd=WORKFLOW.parent, env=environment,
                    text=True, capture_output=True, check=False, timeout=10,
                )
                self.assertNotEqual(missing_helper.returncode, 0)
                self.assertIn("ci-gate-common.sh", missing_helper.stderr)
                shutil.copyfile(
                    WORKFLOW.parents[2] / "scripts/ci-gate-common.sh",
                    root / "ci-gate-common.sh",
                )
                missing_inputs = subprocess.run(
                    ["bash", str(root / script)], cwd=WORKFLOW.parent, env=environment,
                    text=True, capture_output=True, check=False, timeout=10,
                )
                self.assertNotEqual(missing_inputs.returncode, 0)
                self.assertIn("EVENT_NAME: unbound variable", missing_inputs.stderr)

    def test_image_scans_consume_build_artifacts_without_rebuilding(self):
        build = self.jobs["docker-build"]
        scan = self.jobs["docker-scan"]
        self.assertNotIn("aquasecurity/trivy-action@", build)
        self.assertIn("outputs: type=docker,dest=${{ runner.temp }}/docker-image.tar", build)
        self.assertIn("uses: actions/upload-artifact@", build)
        self.assertIn("path: ${{ runner.temp }}/docker-image.tar", build)
        self.assertIn("if-no-files-found: error", build)
        self.assertNotIn("uses: docker/build-push-action@", scan)
        self.assertIn("uses: actions/download-artifact@", scan)
        self.assertIn("path: ${{ runner.temp }}/docker-image", scan)
        self.assertIn("input: ${{ runner.temp }}/docker-image/docker-image.tar", scan)
        self.assertIn("uses: aquasecurity/trivy-action@", scan)
        self.assertIn("exit-code: '1'", scan)
        self.assertIn("severity: 'CRITICAL,HIGH'", scan)
        self.assertIn("ignore-unfixed: true", scan)
        self.assertIn("vuln-type: 'os,library'", scan)
        self.assertIn("    needs:\n      - changes\n      - docker-build\n", scan)
        self.assertIn("      - docker-scan\n", self.jobs["ci-result"])
        for job in (build, scan):
            self.assertIn("name: docker-image-${{ matrix.target }}", job)
            self.assertIn("target: [development, production]", job)
            self.assertIn("fail-fast: false", job)
            self.assertIn(
                "if: ${{ github.event_name != 'schedule' && "
                "needs.changes.outputs.docker == 'true' }}", job,
            )

    def test_image_tags_are_valid_and_independent_of_git_ref_names(self):
        build = self.jobs["docker-build"]
        tags = re.findall(r"^          tags: (.+)$", build, re.MULTILINE)
        self.assertEqual(tags, [
            "symfony-flex-backend:ci-${{ github.sha }}-${{ matrix.target }}",
        ])
        self.assertNotIn("github.ref_name", build)
        self.assertNotIn("DOCKER_TAG", build)
        self.assertNotIn("steps.vars", build)
        for sha in ("a" * 40, "0123456789abcdef" * 2 + "01234567"):
            for target in ("development", "production"):
                with self.subTest(sha=sha, target=target):
                    image = tags[0].replace("${{ github.sha }}", sha).replace(
                        "${{ matrix.target }}", target,
                    )
                    tag = image.split(":", 1)[1]
                    self.assertRegex(tag, r"^[A-Za-z0-9_][A-Za-z0-9_.-]{0,127}$")
                    self.assertEqual(tag, f"ci-{sha}-{target}")

    def run_gate(self, job, environment):
        script = re.search(
            r"^        run: bash (scripts/ci-[\w-]+\.sh)$",
            self.jobs[job], re.MULTILINE,
        )
        self.assertIsNotNone(script, f"Missing result script for {job}")
        return subprocess.run(
            ["bash", str(WORKFLOW.parents[2] / script.group(1))],
            cwd=WORKFLOW.parent,
            env=dict(os.environ, **environment),
            text=True, capture_output=True, check=False, timeout=10,
        )

    def test_actual_scripts_preserve_result_validation(self):
        labels = {
            "CHANGES": "Change detection",
            "SECURITY_CHECK": "Dependency security",
            "DOCKER_UPDATES": "Docker image updates",
            "DOCKER_PINS": "Docker pin verification",
            "GITLEAKS": "Gitleaks",
            "LINT_CONFIGURATION": "Configuration lint",
            "LINT_DOCUMENTATION": "Documentation lint",
            "LINT_WORKFLOWS": "Workflow validation",
            "STATIC_ANALYZERS": "Static analyzer matrix",
            "LIGHTWEIGHT_ANALYZERS": "Lightweight analyzers",
            "CI_SUMMARY": "Prerequisite gate",
            "TEST": "PHPUnit",
            "DOCKER_BUILD": "Docker builds",
            "DOCKER_SCAN": "Trivy image scans",
            "REPORT": "External reporting",
        }
        for job in ("ci-summary", "ci-result"):
            result_names = re.findall(
                r"^          (\w+): \$\{\{ needs\.[\w-]+\.result \}\}$",
                self.jobs[job], re.MULTILINE,
            )
            self.assertTrue(result_names, f"Missing result bindings for {job}")
            for event in ("pull_request", "push", "merge_group",
                          "workflow_dispatch", "schedule"):
                environment = dict.fromkeys(result_names, "success")
                environment.update(EVENT_NAME=event,
                                   DOCUMENTATION_CHANGED="true", REF="refs/heads/master")
                flags = re.findall(
                    r"^          (\w+): \$\{\{ needs\.changes\.outputs\.\w+ \}\}$",
                    self.jobs[job], re.MULTILINE,
                )
                environment.update(dict.fromkeys(flags, "true"))
                if job == "ci-result" and event in ("merge_group", "workflow_dispatch"):
                    environment["REPORT"] = "skipped"
                if event == "schedule":
                    periodic = {"SECURITY_CHECK", "DOCKER_UPDATES", "CI_SUMMARY"}
                    for name in result_names:
                        if name not in periodic:
                            environment[name] = "skipped"
                with self.subTest(job=job, event=event):
                    result = self.run_gate(job, environment)
                    self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                    self.assertNotIn("::error::", result.stderr)
                for name in result_names:
                    for status in ("success", "failure", "cancelled", "skipped", "", "unknown"):
                        if status == environment[name]:
                            continue
                        with self.subTest(job=job, event=event, name=name, status=status):
                            result = self.run_gate(job, dict(environment, **{name: status}))
                            self.assertNotEqual(result.returncode, 0, result.stdout)
                            self.assertEqual(
                                result.stderr,
                                f"::error::{labels[name]}: expected result "
                                f"'{environment[name]}', got '{status}'.\n",
                            )


if __name__ == "__main__":
    unittest.main()
