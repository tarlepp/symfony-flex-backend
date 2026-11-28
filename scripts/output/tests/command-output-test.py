#!/usr/bin/env python3
"""Test command output, streaming, and regression-suite reporting contracts."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make test-command-output
#   To run only this suite from the repository root inside the container:
#     python3 scripts/output/tests/command-output-test.py
#
# Requirements:
#   Python 3, Bash, jq, and Linux pseudo-terminal support. The nested Docker
#   image suite uses jq even though Docker and registry responses are mocked.
#
# Details:
#   Exercises real output helpers and an offline Docker image test suite, using
#   temporary fixtures and mocked Docker and registry responses. Checks cover
#   color, stream separation, partial lines, signals, progress, and nested test
#   reporting. The mocked Docker suite does not verify actual image pins.
#
# Callers:
#   Scripts:
#     - `scripts/tests/command-output-contract-test.sh` loads this suite through
#       `scripts/tests/run-workflow-tests.py`.

import os
from pathlib import Path
import pty
import re
import select
import shutil
import signal
import subprocess
import tempfile
import time
import unittest


ROOT = Path(__file__).resolve().parents[3]
HELPER = ROOT / "scripts/output/command-output.sh"
SUITE = ROOT / "scripts/docker/tests/docker-images-test.sh"
NATIVE = ROOT / "scripts/output/native-output.sh"
DOCKER_IMAGE_TEST_GROUPS = (
    ("Manifest validation", 8),
    ("Pinned tag/digest parity", 6),
    ("External COPY pin verification", 8),
    ("Version discovery and output formatting", 19),
    ("Registry responses and pagination", 7),
    ("Dockerfile discovery", 4),
    ("Registry lookup failures and retries", 8),
    ("Update-pin validation", 11),
    ("Aggregate results and empty discovery", 4),
    ("Applying minor/patch updates and digest refreshes", 21),
    ("Major reporting stays separate from automatic pin updates", 5),
)
DOCKER_IMAGE_TEST_COUNT = sum(count for _, count in DOCKER_IMAGE_TEST_GROUPS)


class CommandOutputTest(unittest.TestCase):
    def environment(self, **overrides):
        """Isolate color, locale, and progress settings before applying overrides."""
        environment = dict(os.environ, COLOR="auto", TERM="xterm", PROGRESS="never", LANG="C")

        for name in ("NO_COLOR", "NATIVE_COLOR", "CI", "LC_ALL", "LC_CTYPE", "OUTPUT_PROGRESS_DELAY", "OUTPUT_PROGRESS_STARTED",
                     "OUTPUT_PROGRESS_SHARED"):
            environment.pop(name, None)

        environment.update(overrides)

        return environment

    def run_terminal(
        self, command, stdout_terminal=True, extra_environment=None, **environment,
    ):
        """Run with stderr (and optionally stdout) on a pseudo-terminal; return status, terminal and stdout bytes."""

        if extra_environment is not None:
            environment.update(extra_environment)

        master, slave = pty.openpty()
        process = subprocess.Popen(
            command, env=self.environment(**environment), cwd=ROOT,
            stdout=slave if stdout_terminal else subprocess.PIPE, stderr=slave,
        )

        os.close(slave)
        output = b""

        while True:
            try:
                chunk = os.read(master, 4096)
            except OSError:
                break

            if not chunk:
                break

            output += chunk

        os.close(master)

        if stdout_terminal:
            stdout = b""
        else:
            assert process.stdout is not None
            stdout = process.stdout.read()

        return process.wait(timeout=10), output, stdout

    def run_command(self, command, **environment):
        """Capture stdout, stderr, and status under the controlled test environment."""

        return subprocess.run(
            command, env=self.environment(**environment), cwd=ROOT,
            text=True, capture_output=True, check=False,
        )

    def test_wrapper_preserves_streams_and_success(self):
        result = self.run_command([
            "bash", str(HELPER), "example", "bash", "-c",
            "printf payload; printf diagnostic >&2",
        ])

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, "payload")
        self.assertIn("[RUN] example", result.stderr)
        self.assertIn("diagnostic", result.stderr)
        self.assertIn("[OK] example", result.stderr)
        self.assertNotIn("\033", result.stderr)

    def test_make_target_label_does_not_change_child_stream_indentation(self):
        command = ["bash", str(HELPER), "example", "bash", "-c",
                   "printf '  payload\\n::notice::fixture\\n'; printf '  diagnostic\\n' >&2"]
        direct = self.run_command(command, OUTPUT_RUN_TARGET="", OUTPUT_RUN_GROUPED="1")
        grouped = self.run_command(command, OUTPUT_RUN_TARGET="example", OUTPUT_RUN_GROUPED="1")
        self.assertEqual(direct.returncode, 0)
        self.assertEqual(grouped.returncode, 0)
        self.assertEqual(direct.stdout, "  payload\n::notice::fixture\n")
        self.assertEqual(grouped.stdout, direct.stdout)
        self.assertEqual(grouped.stderr.replace(" (make example)", ""), direct.stderr)

    def test_grouped_runs_keep_outcomes_with_their_checks(self):
        for status, outcome in ((0, "OK"), (7, "FAIL"), (130, "WARN"), (143, "WARN")):
            with self.subTest(status=status):
                result = self.run_command([
                    "bash", str(HELPER), "example", "bash", "-c",
                    f"printf payload; printf 'diagnostic\\n' >&2; exit {status}",
                ], OUTPUT_RUN_GROUPED="1")

                self.assertEqual(result.returncode, status)
                self.assertEqual(result.stdout, "payload")
                suffix = "" if status == 0 else (
                    f": cancelled (exit {status})" if status in (130, 143)
                    else f" (exit {status})"
                )

                self.assertEqual(
                    result.stderr,
                    f"[RUN] example\ndiagnostic\n[{outcome}] example{suffix}\n",
                )

        result = self.run_command([
            "bash", "-c",
            'source scripts/output/command-output.sh; OUTPUT_RUN_GROUPED=1; '
            'output_run first true; output_run second true',
        ])

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stderr,
                         "[RUN] first\n[OK] first\n\n[RUN] second\n[OK] second\n")
        result = self.run_command([
            "bash", "-c",
            'source scripts/output/command-output.sh; OUTPUT_RUN_GROUPED=1; '
            'OUTPUT_STATUS_INDENT="  "; '
            'OUTPUT_STATUS_INDENT="" output_status RUN stage; '
            'output_run check bash -c \'echo diagnostic >&2; exit 7\'',
        ])

        self.assertEqual(result.returncode, 7)
        self.assertEqual(
            result.stderr,
            "[RUN] stage\n  [RUN] check\ndiagnostic\n  [FAIL] check (exit 7)\n",
        )

    def test_failure_status_is_not_swallowed(self):
        for status in (1, 2, 7, 127):
            with self.subTest(status=status):
                result = self.run_command([
                    "bash", str(HELPER), "example", "bash", "-c", f"exit {status}",
                ], COLOR="always")

                self.assertEqual(result.returncode, status)
                self.assertIn("\033[31m[FAIL]\033[0m", result.stderr)
                self.assertIn(f"exit {status}", result.stderr)
                self.assertNotIn("[OK]", result.stderr)

    def test_pre_pr_deprecation_filter_preserves_other_streams_and_failures(self):
        deprecation = (
            "[2026-11-24T11:32:04.469665+00:00] deprecation.INFO: noisy log"
        )
        warning = "[2026-11-24T11:32:04+00:00] deprecation.WARNING: keep"
        for status in (0, 7):
            with self.subTest(status=status):
                result = self.run_command([
                    "python3", str(ROOT / "scripts/output/native-output.py"),
                    "--suppress-deprecation-info", "bash", "-c",
                    f"printf '%s\\n' '{deprecation}'; "
                    f"printf '  %s\\n%s\\n' '{deprecation}' '{warning}' >&2; "
                    f"printf 'ordinary diagnostic\\npartial' >&2; exit {status}",
                ])
                self.assertEqual(result.returncode, status)
                self.assertEqual(result.stdout, f"{deprecation}\n")
                self.assertEqual(result.stderr, f"{warning}\nordinary diagnostic\npartial")

    def test_pre_pr_deprecation_filter_handles_split_records_and_live_output(self):
        result = self.run_command([
            "python3", str(ROOT / "scripts/output/native-output.py"),
            "--suppress-deprecation-info", "bash", "-c",
            "printf '[2026-11-24T11:32:04' >&2; sleep 0.1; "
            "printf '+00:00] deprecation.INFO: hidden\\n' >&2; "
            "printf '[2026-11-24T11:32:04+00:00] other.INFO: kept\\n' >&2; "
            "printf '[2026-11' >&2",
        ])
        self.assertEqual(result.returncode, 0)
        self.assertEqual(
            result.stderr, "[2026-11-24T11:32:04+00:00] other.INFO: kept\n[2026-11",
        )

    def test_default_run_mode_preserves_structured_output_and_fail_fast_steps(self):
        for status in (0, 7):
            result = self.run_command([
                "bash", str(HELPER), "report", "bash", "-e", "-c",
                f'printf \'{{"report":true}}\\n\'; (exit {status}); printf completed >&2',
            ])

            self.assertEqual(result.returncode, status, result.stderr)
            self.assertEqual(result.stdout, '{"report":true}\n')
            self.assertEqual("[OK]" in result.stderr, status == 0)
            self.assertEqual("completed" in result.stderr, status == 0)

            if status:
                self.assertIn("[FAIL] report (exit 7)", result.stderr)

    def test_native_indentation_preserves_streams_colors_and_exit_status(self):
        for status in (0, 7, 130, 143):
            with self.subTest(status=status):
                result = self.run_command([
                    "python3", str(ROOT / "scripts/output/native-output.py"), "--indent",
                    "bash", "-c",
                    "printf '\\033[32mfirst\\033[0m\\n\\npartial'; "
                    f"printf 'warning\\n' >&2; exit {status}",
                ])

                self.assertEqual(result.returncode, status)
                self.assertEqual(result.stdout, "  \033[32mfirst\033[0m\n\n  partial\n")
                self.assertEqual(result.stderr, "  warning\n")
        status, terminal, stdout = self.run_terminal([
            "python3", str(ROOT / "scripts/output/native-output.py"), "--indent",
            "bash", "-c", 'test -t 1 && test -t 2 && printf "terminal\\n"',
        ])

        self.assertEqual(status, 0)
        self.assertIn(b"  terminal\r\n", terminal)
        self.assertEqual(stdout, b"")
        result = self.run_command([
            "python3", str(ROOT / "scripts/output/native-output.py"), "--indent",
            "/nonexistent-workflow-validator",
        ])

        self.assertEqual(result.returncode, 127)
        self.assertIn("command not found", result.stderr)

    def test_native_indentation_preserves_github_commands_at_column_zero(self):
        result = self.run_command([
            "python3", str(ROOT / "scripts/output/native-output.py"), "--indent",
            "bash", "-c",
            "printf ':'; sleep 0.1; "
            "printf ':notice file=src/Example.php::annotation\\nordinary output\\n'; "
            "printf ':' >&2; sleep 0.1; "
            "printf ':warning::annotation\\nordinary diagnostic\\n' >&2",
        ])
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(
            result.stdout,
            "::notice file=src/Example.php::annotation\n  ordinary output\n",
        )
        self.assertEqual(
            result.stderr,
            "::warning::annotation\n  ordinary diagnostic\n",
        )

    def test_compact_status_spacing_removes_blank_padding_around_success(self):
        result = self.run_command([
            "python3", str(ROOT / "scripts/output/native-output.py"),
            "--indent", "--compact-status-spacing",
            "bash", "-c",
            "printf 'detail\\n\\n   \\033[32m[OK]\\033[0m success   \\n \\t\\n\\n'; "
            "printf 'first\\n\\nsecond\\n' >&2",
        ])
        self.assertEqual(result.returncode, 0)
        self.assertEqual(result.stdout, "  detail\n     \033[32m[OK]\033[0m success\n")
        self.assertEqual(result.stderr, "  first\n\n  second\n")

        status, terminal, stdout = self.run_terminal([
            "python3", str(ROOT / "scripts/output/native-output.py"),
            "--compact-status-spacing", "--indent",
            "bash", "-c",
            "printf 'detail\\n\\n   [OK] success   \\r\\n \\t\\r\\n\\n'",
        ])
        self.assertEqual(status, 0)
        self.assertIn(b"[OK] success", terminal)
        self.assertNotIn(b"\r\n\r\n", terminal)
        self.assertEqual(stdout, b"")

    def test_native_indentation_keeps_partial_lines_visible(self):
        process = subprocess.Popen([
            "python3", str(ROOT / "scripts/output/native-output.py"), "--indent",
            "bash", "-c", "printf partial; sleep 1; printf '\\n'",
        ], cwd=ROOT, env=self.environment(), stdout=subprocess.PIPE,
            stderr=subprocess.PIPE)

        try:
            assert process.stdout is not None
            ready, _, _ = select.select([process.stdout], [], [], 0.8)

            self.assertTrue(ready, "Partial output must stream before the command exits")
            self.assertEqual(os.read(process.stdout.fileno(), 4096), b"  partial")
            stdout, stderr = process.communicate(timeout=5)

            self.assertEqual(process.returncode, 0)
            self.assertEqual(stdout, b"\n")
            self.assertEqual(stderr, b"")
        finally:
            if process.poll() is None:
                process.terminate()
                process.wait(timeout=5)

    def test_native_decoration_filter_preserves_streams_and_exit_status(self):
        for color, no_color, enabled in (
            ("always", "", True), ("never", "", False),
            ("always", "1", False), ("auto", "", False),
        ):
            for status in (0, 7, 130, 143):
                result = subprocess.run([
                    "bash", str(NATIVE), "bash", "-c",
                    "printf '\\033[32m{\"report\":true}\\033[0m'; "
                    "printf '\\033[31m::error file=src/Test.php::diagnostic\\033[0m\\r\\033[2K' >&2; "
                    f"exit {status}",
                ], env=self.environment(COLOR=color, NO_COLOR=no_color), cwd=ROOT,
                    capture_output=True, check=False)

                self.assertEqual(result.returncode, status)
                self.assertEqual(result.stdout, b'\033[32m{"report":true}\033[0m' if enabled
                                 else b'{"report":true}')
                self.assertEqual(result.stderr,
                                 (b"\033[31m::error file=src/Test.php::diagnostic\033[0m"
                                  if enabled else b"::error file=src/Test.php::diagnostic") + b"\r\033[2K")
                self.assertNotIn(b"[OK]", result.stderr)

        result = self.run_command(["bash", str(NATIVE)])

        self.assertEqual(result.returncode, 64)
        self.assertIn("[FAIL] Usage:", result.stderr)
        result = self.run_command(["bash", str(NATIVE), "copilot-output-missing-tool"], COLOR="never")

        self.assertEqual(result.returncode, 127)
        self.assertIn("command not found", result.stderr)

    def test_native_decoration_filter_streams_before_command_completion(self):
        # A separate process group lets cleanup stop the whole long-running fixture.
        process = subprocess.Popen(
            ["bash", str(NATIVE), "bash", "-c",
             "printf '\\033['; sleep 0.1; printf '32mready'; "
             "printf '\\033[31merror\\033[0m' >&2; sleep 30"],
            env=self.environment(COLOR="never"), stdout=subprocess.PIPE,
            stderr=subprocess.PIPE, start_new_session=True,
        )

        try:
            assert process.stdout is not None
            assert process.stderr is not None
            self.assertTrue(select.select([process.stdout, process.stderr], [], [], 5)[0])
            self.assertEqual(process.stdout.read(5), b"ready")
            self.assertTrue(select.select([process.stderr], [], [], 5)[0])
            self.assertEqual(process.stderr.read(5), b"error")
            self.assertIsNone(process.poll())
        finally:
            os.killpg(process.pid, signal.SIGTERM)
            process.communicate(timeout=5)

    def test_native_plain_filter_preserves_terminal_detection(self):
        master, slave = pty.openpty()

        try:
            process = subprocess.Popen(
                ["bash", str(NATIVE), "bash", "-c",
                 'test -t 1 && test -t 2 && printf "\\033[32mterminal\\033[0m"'],
                env=self.environment(COLOR="never"), stdin=subprocess.DEVNULL,
                stdout=slave, stderr=slave,
            )

            os.close(slave)
            self.assertEqual(process.wait(timeout=5), 0)
            self.assertEqual(os.read(master, 4096), b"terminal")
        finally:
            os.close(master)

    def test_native_auto_color_uses_terminal_and_respects_dumb_and_no_color(self):
        for environment, enabled in (
            ({}, True), ({"TERM": "dumb"}, False),
            ({"COLOR": "always", "NO_COLOR": "1"}, False),
        ):
            master, slave = pty.openpty()

            try:
                process = subprocess.Popen(
                    ["bash", str(NATIVE), "bash", "-c",
                     "printf '\\033[32mpayload\\033[0m'"],
                    env=self.environment(**environment), stdout=slave, stderr=slave,
                )

                self.assertEqual(process.wait(timeout=5), 0)
                output = os.read(master, 4096)

                self.assertEqual(output, b"\033[32mpayload\033[0m" if enabled else b"payload")
            finally:
                os.close(slave)
                os.close(master)

    def test_native_filter_requires_python_and_fails_explicitly(self):
        with tempfile.TemporaryDirectory(prefix="native-filter-test-") as directory:
            root = Path(directory)
            result = self.run_command(
                ["/bin/bash", str(NATIVE), "/bin/bash", "-c", "printf payload"],
                PATH=str(root), COLOR="never",
            )

            self.assertEqual(result.returncode, 127)
            self.assertIn("python3: not found", result.stderr)
            self.assertNotIn("[OK]", result.stderr)

    def test_stream_mode_preserves_streams_and_has_no_completion_banner(self):
        for status in (0, 7, 130, 143):
            result = self.run_command([
                "bash", str(HELPER), "--stream", "session", "bash", "-c",
                f"printf payload; printf diagnostic >&2; exit {status}",
            ], COLOR="always")

            self.assertEqual(result.returncode, status)
            self.assertEqual(result.stdout, "payload")
            self.assertEqual(result.stderr, "\033[34m[RUN]\033[0m session\ndiagnostic")

        for mode in ("run", "stream"):
            result = self.run_command(
                ["bash", str(HELPER), f"--{mode}", "missing command"],
            )

            self.assertEqual(result.returncode, 64)
            self.assertIn("[FAIL] Usage:", result.stderr)

        for label in ("--run", "--stream"):
            result = self.run_command(["bash", str(HELPER), "--run", label, "true"])

            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn(f"[RUN] {label}", result.stderr)
            self.assertIn(f"[OK] {label}", result.stderr)

        result = self.run_command([
            "bash", str(HELPER), "--stream", "missing tool",
            "copilot-output-contract-missing-executable",
        ])

        self.assertEqual(result.returncode, 127)
        self.assertIn("[RUN] missing tool", result.stderr)
        self.assertIn("not found", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_stream_mode_executes_in_place_with_terminal_descriptors(self):
        master, slave = pty.openpty()

        try:
            process = subprocess.Popen(
                ["bash", str(HELPER), "--stream", "terminal", "bash", "-c",
                 'test -t 0 && test -t 1 && test -t 2 && printf "%s" "$$"'],
                env=self.environment(COLOR="never"), stdin=slave,
                stdout=slave, stderr=slave,
            )

            self.assertEqual(process.wait(timeout=5), 0)
            output = os.read(master, 4096)

            self.assertIn(str(process.pid).encode(), output)
            self.assertNotIn(b"[OK]", output)
        finally:
            os.close(slave)
            os.close(master)

    def test_missing_executable_fails_without_success(self):
        result = self.run_command(
            ["bash", str(HELPER), "missing tool", "copilot-output-contract-missing-executable"],
            COLOR="never",
        )

        self.assertEqual(result.returncode, 127)
        self.assertIn("command not found", result.stderr)
        self.assertIn("[FAIL] missing tool (exit 127)", result.stderr)
        self.assertNotIn("[OK]", result.stderr)
        result = self.run_command(["bash", str(HELPER)])

        self.assertEqual(result.returncode, 64)
        self.assertIn("[FAIL] Usage:", result.stderr)
        self.assertNotIn("[OK]", result.stderr)

    def test_success_overrides_never_hide_failure_or_cancellation(self):
        for status in (0, 7, 130, 143):
            result = self.run_command(
                ["bash", str(HELPER), "execute", "bash", "-c", f"exit {status}"],
                OUTPUT_SUCCESS_STATUS="SKIP", OUTPUT_SUCCESS_MESSAGE="completion only",
            )

            self.assertEqual(result.returncode, status)
            self.assertIn("[RUN] execute", result.stderr)
            self.assertNotIn("[OK]", result.stderr)

            if status == 0:
                self.assertIn("[SKIP] completion only", result.stderr)
            else:
                self.assertNotIn("completion only", result.stderr)
                self.assertIn("[FAIL]" if status == 7 else "[WARN]", result.stderr)

    def test_status_palette_and_plain_overrides(self):
        command = [
            "bash", "-c",
            'source "$1"; for status in RUN INFO OK WARN SKIP FAIL; do '
            'output_status "$status" example; done', "test", str(HELPER),
        ]
        result = self.run_command(command, COLOR="always")

        for label, color in (("RUN", 34), ("INFO", 34), ("OK", 32), ("WARN", 33),
                             ("SKIP", 33), ("FAIL", 31)):
            self.assertIn(f"\033[{color}m[{label}]\033[0m", result.stderr)

        self.assertTrue(result.stderr.startswith("\033[34m[RUN]\033[0m"))

        for environment in ({"COLOR": "never"}, {"COLOR": "always", "NO_COLOR": "1"}):
            result = self.run_command(command, **environment)

            self.assertEqual(result.returncode, 0)
            self.assertNotIn("\033", result.stderr)
            self.assertIn("[FAIL] example", result.stderr)

    def test_status_entrypoint_preserves_literal_message_and_color_policy(self):
        message = 'Two words; $(printf injected) "quoted" *'

        for status in ("RUN", "OK", "WARN", "SKIP", "FAIL", "INFO"):
            for color, no_color, enabled in (
                ("always", "", True), ("never", "", False), ("always", "1", False),
            ):
                result = self.run_command(
                    ["bash", str(HELPER), "--status", status, message],
                    COLOR=color, NO_COLOR=no_color,
                )

                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout, "")
                self.assertIn(message, result.stderr)
                self.assertEqual("\033" in result.stderr, enabled)

                if not enabled:
                    self.assertEqual(result.stderr, f"[{status}] {message}\n")

        for arguments in (
            ("--status",), ("--status", "WARN"), ("--status", "invalid", "message"),
            ("--status", "WARN", "message", "extra"),
            ("--status", "PASS", "message"), ("--status", "ERROR", "message"),
        ):
            result = self.run_command(["bash", str(HELPER), *arguments])

            self.assertEqual(result.returncode, 64)
            self.assertIn("[FAIL] Usage:", result.stderr)
            self.assertNotIn("[OK]", result.stderr)

    def test_report_notifications_require_every_nonempty_file(self):
        helper = ROOT / "scripts/output/report-output.sh"

        with tempfile.TemporaryDirectory(prefix="report-output-") as directory:
            first = Path(directory) / "first report.json"
            second = Path(directory) / "second.xml"

            first.write_text("{}")

            for content in (None, "", "<report/>"):
                if content is not None:
                    second.write_text(content)

                result = self.run_command(
                    ["bash", str(helper), "Local files; no upload", str(first), str(second)],
                )

                self.assertEqual(result.stdout, "")

                if content:
                    self.assertEqual(result.returncode, 0)
                    self.assertEqual(result.stderr, "[INFO] Local files; no upload\n")
                else:
                    self.assertEqual(result.returncode, 1)
                    self.assertIn(str(second), result.stderr)
                    self.assertNotIn("[INFO]", result.stderr)

        result = self.run_command(["bash", str(helper), "no files"])

        self.assertEqual(result.returncode, 64)
        self.assertIn("[FAIL] Usage:", result.stderr)

    def test_auto_color_uses_status_terminal(self):
        master, slave = pty.openpty()

        try:
            result = subprocess.run(
                ["bash", str(HELPER), "terminal", "true"],
                env=self.environment(), stdout=subprocess.PIPE, stderr=slave,
                check=False,
            )
            output = os.read(master, 4096)

            self.assertEqual(result.returncode, 0)
            self.assertIn(b"\033[32m[OK]", output)
        finally:
            os.close(slave)
            os.close(master)

        result = self.run_command(["bash", str(HELPER), "dumb", "true"], TERM="dumb")

        self.assertNotIn("\033", result.stderr)

    def test_streaming_and_interruption(self):
        for interruption in (signal.SIGINT, signal.SIGTERM):
            with self.subTest(signal=interruption):
                process = subprocess.Popen(
                    ["bash", str(HELPER), "stream", "bash", "-c",
                     "printf ready; sleep 30"],
                    env=self.environment(), stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE, start_new_session=True,
                )

                try:
                    assert process.stdout is not None
                    self.assertTrue(select.select([process.stdout], [], [], 5)[0])
                    self.assertEqual(process.stdout.read(5), b"ready")
                    os.killpg(process.pid, interruption)
                    stdout, stderr = process.communicate(timeout=5)

                    self.assertEqual(process.returncode, 128 + interruption)
                    self.assertIn(b"[WARN]", stderr)
                    self.assertNotIn(b"[OK]", stderr)
                finally:
                    if process.poll() is None:
                        os.killpg(process.pid, signal.SIGKILL)
                        process.communicate()

    def test_stream_mode_directly_propagates_real_signals(self):
        child = (
            "import signal, sys, time; "
            "signal.signal(signal.SIGINT, signal.SIG_DFL); "
            "signal.signal(signal.SIGTERM, signal.SIG_DFL); "
            "sys.stdout.write('ready'); sys.stdout.flush(); "
            "sys.stderr.write('diagnostic'); sys.stderr.flush(); "
            "time.sleep(30)"
        )

        for interruption in (signal.SIGINT, signal.SIGTERM):
            with self.subTest(signal=interruption):
                process = subprocess.Popen(
                    ["bash", str(HELPER), "--stream", "stream", "python3", "-c", child],
                    env=self.environment(COLOR="never"),
                    stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                    start_new_session=True,
                )

                assert process.stdout is not None
                assert process.stderr is not None
                observed = {process.stdout: bytearray(), process.stderr: bytearray()}
                expected = {
                    process.stdout: b"ready",
                    process.stderr: b"[RUN] stream\ndiagnostic",
                }

                try:
                    deadline = time.monotonic() + 5

                    while any(expected[stream] not in data for stream, data in observed.items()):
                        remaining = deadline - time.monotonic()

                        self.assertGreater(remaining, 0, "stream-mode child did not produce both streams")
                        ready, _, _ = select.select(list(observed), [], [], remaining)

                        self.assertTrue(ready, "stream-mode child did not produce both streams")

                        for stream in ready:
                            observed[stream].extend(os.read(stream.fileno(), 4096))

                    os.killpg(process.pid, interruption)
                    stdout, stderr = process.communicate(timeout=5)
                    actual_stdout = bytes(observed[process.stdout]) + stdout
                    actual_stderr = bytes(observed[process.stderr]) + stderr

                    self.assertEqual(process.returncode, -interruption)
                    self.assertEqual(actual_stdout, b"ready")
                    self.assertEqual(actual_stderr, b"[RUN] stream\ndiagnostic")
                    self.assertNotIn(b"[OK]", actual_stderr)
                    self.assertNotIn(b"[WARN]", actual_stderr)
                    self.assertNotIn(b"[FAIL]", actual_stderr)
                finally:
                    if process.poll() is None:
                        os.killpg(process.pid, signal.SIGKILL)
                        process.communicate()

    def test_progress_indicator_draws_while_silent_and_clears_before_output(self):
        status, output, _ = self.run_terminal(
            ["bash", str(HELPER), "slow step", "bash", "-c",
             "echo first; sleep 0.8; echo second; [ -t 1 ] && echo stdout-terminal; "
             "echo nested=$PROGRESS ctype=${LC_CTYPE-unset} coerce=${PYTHONCOERCECLOCALE-unset}; exit 3"],
            PROGRESS="auto", OUTPUT_PROGRESS_DELAY="0.2", COLOR="never",
        )

        self.assertEqual(status, 3)
        self.assertIn(b"first\r\n\r\033[K| slow step (", output)
        self.assertIn(b"\r\033[Ksecond\r\n", output)
        self.assertIn(b"stdout-terminal\r\n", output)
        self.assertIn(b"nested=never ctype=unset coerce=unset\r\n", output)
        self.assertNotIn(b"\r\r\n", output)
        self.assertRegex(output, rb"\r\n\[FAIL\] slow step \(exit 3, took [0-9]+\.[0-9]s\)\r\n$")

    def test_progress_indicator_waits_for_line_start_and_uses_status_color(self):
        status, output, _ = self.run_terminal(
            ["bash", str(HELPER), "prompt", "bash", "-c", "printf 'Continue? '; sleep 0.6"],
            PROGRESS="auto", OUTPUT_PROGRESS_DELAY="0.1", LANG="C.UTF-8",
        )

        self.assertEqual(status, 0)
        self.assertNotIn(b"\033[K", output)
        self.assertRegex(output, rb"\033\[32m\[OK\]\033\[0m prompt \(took [0-9]+\.[0-9]s\)")
        status, output, _ = self.run_terminal(
            ["bash", str(HELPER), "unicode", "sleep", "0.4"],
            PROGRESS="auto", OUTPUT_PROGRESS_DELAY="0.1", LANG="C.UTF-8",
        )

        self.assertEqual(status, 0)
        self.assertIn("\r\033[K\033[34m⠋\033[0m unicode (".encode(), output)

    def test_progress_policy_disables_indicator_and_durations(self):
        command = ["bash", str(HELPER), "quiet", "sleep", "0.4"]

        for environment in (
            {"PROGRESS": "never"},
            {"PROGRESS": "auto", "CI": "true"},
            {"PROGRESS": "auto", "NO_COLOR": "1"},
            {"PROGRESS": "auto", "TERM": "dumb"},
            {"PROGRESS": "invalid"},
        ):
            with self.subTest(environment=environment):
                status, output, _ = self.run_terminal(
                    command, OUTPUT_PROGRESS_DELAY="0.1", extra_environment=environment,
                )

                self.assertEqual(status, 0)
                self.assertNotIn(b"\033[K", output)
                self.assertNotIn(b"took", output)

        result = self.run_command(command, PROGRESS="auto", OUTPUT_PROGRESS_DELAY="0.1")

        self.assertEqual(result.returncode, 0)
        self.assertEqual(result.stderr, "[RUN] quiet\n\n[OK] quiet\n")

    def test_forced_progress_keeps_piped_streams_separate(self):
        result = self.run_command([
            "bash", str(HELPER), "piped", "bash", "-c",
            "printf '{\"ok\":true}\\n'; sleep 0.5; [ -t 1 ] || printf 'not-terminal\\n'; printf diagnostic >&2",
        ], PROGRESS="always", OUTPUT_PROGRESS_DELAY="0.1", COLOR="never")

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, '{"ok":true}\nnot-terminal\n')
        self.assertIn("\033[K| piped (", result.stderr)
        self.assertIn("diagnostic", result.stderr)
        self.assertRegex(result.stderr, r"\n\[OK\] piped \(took [0-9]+\.[0-9]s\)\n$")

    def test_progress_launch_errors_match_for_pipes_and_distinct_terminals(self):
        with tempfile.TemporaryDirectory(prefix="progress-launch-test-") as directory:
            missing = Path(directory) / "missing"
            denied = Path(directory) / "denied"

            denied.write_text("not executable")
            denied.chmod(0o644)

            for command, status, message in (
                (missing, 127, "command not found"),
                (denied, 126, "Permission denied"),
            ):
                arguments = ["python3", str(ROOT / "scripts/output/command-progress.py"),
                             "example", "plain", str(command)]

                with self.subTest(command=command, streams="pipes"):
                    result = self.run_command(arguments)

                    self.assertEqual(result.returncode, status, result.stderr)
                    self.assertEqual(result.stderr, f"{command}: {message}\n")

                with self.subTest(command=command, streams="distinct terminals"):
                    stdout_master, stdout_slave = pty.openpty()
                    stderr_master, stderr_slave = pty.openpty()

                    try:
                        result = subprocess.run(
                            arguments, env=self.environment(), cwd=ROOT, stdin=subprocess.DEVNULL,
                            stdout=stdout_slave, stderr=stderr_slave, timeout=5, check=False,
                        )

                        self.assertEqual(result.returncode, status)
                        self.assertTrue(select.select([stderr_master], [], [], 5)[0])
                        self.assertEqual(os.read(stderr_master, 4096), f"{command}: {message}\r\n".encode())
                    finally:
                        for descriptor in (stdout_master, stdout_slave, stderr_master, stderr_slave):
                            os.close(descriptor)

    def test_progress_distinct_terminals_execute_in_place(self):
        stdout_master, stdout_slave = pty.openpty()
        stderr_master, stderr_slave = pty.openpty()
        process = None

        try:
            process = subprocess.Popen(
                ["python3", str(ROOT / "scripts/output/command-progress.py"), "example", "plain",
                 "bash", "-c", 'test -t 1 && test -t 2 || exit 1; '
                 'printf "%s:%s" "$$" "$PROGRESS"; printf diagnostic >&2'],
                env=self.environment(PROGRESS="always"), cwd=ROOT, stdin=subprocess.DEVNULL,
                stdout=stdout_slave, stderr=stderr_slave,
            )

            self.assertEqual(process.wait(timeout=5), 0)
            self.assertTrue(select.select([stdout_master], [], [], 5)[0])
            self.assertEqual(os.read(stdout_master, 4096), f"{process.pid}:always".encode())
            self.assertTrue(select.select([stderr_master], [], [], 5)[0])
            self.assertEqual(os.read(stderr_master, 4096), b"diagnostic")
        finally:
            if process is not None and process.poll() is None:
                process.kill()
                process.wait(timeout=5)

            for descriptor in (stdout_master, stdout_slave, stderr_master, stderr_slave):
                os.close(descriptor)

    def test_shared_terminal_progress_orders_stdout_with_spinner_clears(self):
        result = self.run_command([
            "bash", str(HELPER), "--progress", "shared", "bash", "-c",
            "sleep 0.5; printf 'result\\n'; sleep 0.5; printf 'note\\n' >&2; "
            "echo shared=${OUTPUT_PROGRESS_SHARED-unset}",
        ], PROGRESS="always", OUTPUT_PROGRESS_DELAY="0.1", COLOR="never", OUTPUT_PROGRESS_SHARED="1")

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, "")
        # Every spinner frame is cleared in the same stream before the next output.
        self.assertRegex(result.stderr, r"\[K[|/\\-] shared \([0-9.]+s\)\n\033\[Kresult\n")
        self.assertRegex(result.stderr, r"\n\033\[Knote\n")
        self.assertTrue(result.stderr.endswith("shared=unset\n"))
        self.assertNotRegex(result.stderr, r"s\)(result|note|shared=)")

    def test_progress_preserves_shell_functions_and_success_overrides(self):
        result = subprocess.run(
            ["bash", "-c", f"source {HELPER}; step() {{ echo \"function:$PROGRESS\"; }}; output_run step step"],
            env=self.environment(PROGRESS="always", COLOR="never", OUTPUT_SUCCESS_MESSAGE="custom"),
            cwd=ROOT, text=True, capture_output=True, check=False,
        )

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, "function:never\n")
        self.assertRegex(result.stderr, r"\[OK\] custom \(took [0-9]+\.[0-9]s\)\n$")

    def test_progress_interruption_keeps_cancellation_status(self):
        for interruption in (signal.SIGINT, signal.SIGTERM):
            with self.subTest(signal=interruption):
                process = subprocess.Popen(
                    ["bash", str(HELPER), "stream", "bash", "-c", "printf ready; sleep 30"],
                    env=self.environment(PROGRESS="always", COLOR="never"),
                    stdout=subprocess.PIPE, stderr=subprocess.PIPE, start_new_session=True,
                )

                try:
                    assert process.stdout is not None
                    self.assertTrue(select.select([process.stdout], [], [], 5)[0])
                    self.assertEqual(process.stdout.read(5), b"ready")
                    os.killpg(process.pid, interruption)
                    _, stderr = process.communicate(timeout=5)

                    self.assertEqual(process.returncode, 128 + interruption)
                    self.assertRegex(stderr, rb"\[WARN\] stream: [a-z]+ \(took [0-9]+\.[0-9]s\)\n$")
                finally:
                    if process.poll() is None:
                        os.killpg(process.pid, signal.SIGKILL)
                        process.communicate()

    def test_duration_format(self):
        cases = {0: "0.0s", 49999: "0.0s", 1250000: "1.3s", 59940000: "59.9s",
                 59960000: "1m 00s", 65000000: "1m 05s", 3723000000: "1h 02m 03s"}

        for microseconds, expected in cases.items():
            with self.subTest(microseconds=microseconds):
                result = subprocess.run(
                    ["bash", "-c", f"source {HELPER}; output_duration {microseconds}"],
                    env=self.environment(), text=True, capture_output=True, check=True,
                )

                self.assertEqual(result.stdout, expected)

    def test_progress_mode_relays_self_reporting_commands(self):
        script = (
            f"source {HELPER}; output_status RUN own; sleep 0.5; echo detail; "
            "output_outcome WARN 'own outcome'; exit 1"
        )
        status, output, _ = self.run_terminal(
            ["bash", str(HELPER), "--progress", "checking", "bash", "-c", script],
            PROGRESS="auto", OUTPUT_PROGRESS_DELAY="0.1", COLOR="never",
        )

        self.assertEqual(status, 1)
        self.assertTrue(output.startswith(b"[RUN] own\r\n"), output)
        self.assertIn(b"\r\033[K| checking (", output)
        self.assertRegex(output, rb"\r\033\[Kdetail\r\n\r\n\[WARN\] own outcome \(took [0-9]+\.[0-9]s\)\r\n$")
        self.assertEqual(output.count(b"[RUN]"), 1)

        for environment in ({"PROGRESS": "never"}, {"PROGRESS": "auto", "CI": "true"}):
            with self.subTest(environment=environment):
                result = self.run_command(
                    ["bash", str(HELPER), "--progress", "checking", "bash", "-c", script],
                    OUTPUT_PROGRESS_STARTED="1", **environment,
                )

                self.assertEqual(result.returncode, 1)
                self.assertEqual(result.stdout, "detail\n")
                self.assertEqual(result.stderr, "[RUN] own\n\n[WARN] own outcome\n")

    def test_run_mode_does_not_leak_progress_start_to_commands(self):
        result = subprocess.run(
            ["bash", str(HELPER), "outer", "bash", "-c",
             f"source {HELPER}; output_outcome OK inner"],
            env=self.environment(PROGRESS="always", COLOR="never", OUTPUT_PROGRESS_STARTED="1"),
            cwd=ROOT, text=True, capture_output=True, check=False,
        )

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("\n[OK] inner\n", result.stderr)
        self.assertRegex(result.stderr, r"\[OK\] outer \(took [0-9]+\.[0-9]s\)\n$")

    def test_pre_pr_selection_fixture_isolates_inherited_output_settings(self):
        result = self.run_command(
            ["bash", "scripts/make/tests/pre-pr-checks-test.sh"],
            COLOR="always", PROGRESS="always", OUTPUT_STATUS_INDENT="  ",
            OUTPUT_RUN_TARGET="test-command-output", OUTPUT_RUN_TARGET_ACTIVE="1",
            OUTPUT_RUN_GROUPED="1", OUTPUT_RUN_GROUPED_SEPARATOR="0",
            OUTPUT_RUN_GROUPED_STARTED="1",
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn("Pre-PR check selection tests passed.", result.stdout)

    def test_suite_colors_are_independent_of_helper_fixture(self):
        result = self.run_command(["bash", str(SUITE)], COLOR="always")

        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn(
            "Offline regression tests using temporary Dockerfiles and mocked Docker/registry responses.",
            result.stderr,
        )
        self.assertIn(
            "Actual repository Dockerfile pins are NOT checked. "
            "To validate them, run: make docker-image-verify-pins",
            result.stderr,
        )
        self.assertIn("\033[32m[OK]\033[0m empty index", result.stderr)

        for scenario in (
            "single-platform image manifest",
            "index with only one supported platform",
            "malformed manifest JSON",
        ):
            self.assertEqual(
                result.stderr.count(f"[OK]\033[0m reject update: {scenario}\n"), 1,
                result.stderr,
            )
        self.assertIn("[OK]\033[0m empty candidate index is skipped\n", result.stderr)
        self.assertIn(
            "[OK]\033[0m empty candidate index is rejected without editing files\n",
            result.stderr,
        )
        self.assertNotIn("[PASS]", result.stderr)
        self.assertIn("scenarios passed", result.stderr)
        self.assertTrue(
            result.stderr.endswith(
                f"\n\033[32m[OK]\033[0m Docker image tests: {DOCKER_IMAGE_TEST_COUNT} scenarios passed\n"
            ),
            result.stderr,
        )
        self.assertNotIn("[FAIL]", result.stderr)

    def test_docker_suite_isolates_inherited_pre_pr_grouping(self):
        result = self.run_command(
            ["bash", str(SUITE)], COLOR="never", OUTPUT_RUN_GROUPED="1",
            OUTPUT_RUN_TARGET="test-docker-images", OUTPUT_RUN_TARGET_ACTIVE="1",
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn("[OK] Docker image tests:", result.stderr)
        self.assertNotIn("[FAIL]", result.stderr)

    def test_suite_groups_keep_individual_results_in_order(self):
        result = self.run_command(["bash", str(SUITE)], COLOR="never")

        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        groups = DOCKER_IMAGE_TEST_GROUPS
        lines = result.stderr.splitlines()
        headings = [line.strip() for line in lines if line.lstrip().startswith("[RUN]")]
        self.assertEqual(headings, ["[RUN] Docker image tests"] + [
            f"[RUN] {name}" for name, _ in groups
        ])
        for name, count in groups:
            with self.subTest(group=name):
                start = lines.index(f"  [RUN] {name}")
                end = lines.index(f"  [OK] {name}", start + 1)
                scenarios = [
                    line.strip() for line in lines[start + 1:end]
                    if line.startswith("    [OK]")
                ]
                self.assertEqual(len(scenarios), count)
        self.assertEqual(
            lines[-1],
            f"[OK] Docker image tests: {DOCKER_IMAGE_TEST_COUNT} scenarios passed",
        )
        self.assertEqual(lines[-2], "  [OK] Major reporting stays separate from automatic pin updates")
        self.assertNotIn("\033", result.stderr)

    def test_suite_assertion_failure_has_context_and_captured_output(self):
        with tempfile.TemporaryDirectory(prefix="command-output-test-") as directory:
            scripts = Path(directory) / "scripts"
            tests = scripts / "docker" / "tests"

            tests.mkdir(parents=True)
            (scripts / "output").mkdir()
            (scripts / "make").mkdir()
            shutil.copyfile(ROOT / "scripts/make/pre-pr-checks.sh", scripts / "make/pre-pr-checks.sh")
            shutil.copyfile(HELPER, scripts / "output/command-output.sh")
            shutil.copyfile(ROOT / "scripts/output/native-output.py", scripts / "output/native-output.py")
            shutil.copyfile(ROOT / "Makefile", Path(directory) / "Makefile")
            shutil.copy(
                ROOT / "scripts/docker/docker-images.sh",
                scripts / "docker/docker-images.sh",
            )
            content = SUITE.read_text()
            assertion = "grep -Fxq '  IMAGE input (no matching Dockerfile)'"
            self.assertEqual(content.count(assertion), 1)
            changed = content.replace(assertion, "grep -Fxq 'deliberately missing assertion'")
            suite = tests / SUITE.name

            suite.write_text(changed)
            result = self.run_command(["bash", str(suite)], COLOR="always")

            self.assertNotEqual(result.returncode, 0)
            self.assertIn("\033[31m[FAIL]\033[0m stable major upgrade", result.stderr)
            self.assertIn("deliberately missing assertion", result.stderr)
            self.assertIn("Expected the assertion to succeed; actual exit: 1", result.stderr)
            self.assertIn("Captured helper output:", result.stderr)
            self.assertIn("test:1.9.9 -> test:2.0.0", result.stderr)
            self.assertNotIn("[OK] stable major upgrade", result.stderr)
            self.assertIn("scenarios passed before failure", result.stderr)

    def test_suite_explicit_failure_is_not_reported_as_success(self):
        with tempfile.TemporaryDirectory(prefix="command-output-test-") as directory:
            scripts = Path(directory) / "scripts"
            tests = scripts / "docker" / "tests"

            tests.mkdir(parents=True)
            (scripts / "output").mkdir()
            (scripts / "make").mkdir()
            shutil.copyfile(ROOT / "scripts/make/pre-pr-checks.sh", scripts / "make/pre-pr-checks.sh")
            shutil.copyfile(HELPER, scripts / "output/command-output.sh")
            shutil.copyfile(ROOT / "scripts/output/native-output.py", scripts / "output/native-output.py")
            shutil.copyfile(ROOT / "Makefile", Path(directory) / "Makefile")
            shutil.copy(
                ROOT / "scripts/docker/docker-images.sh",
                scripts / "docker/docker-images.sh",
            )
            content = SUITE.read_text()
            marker = '    IMAGE="$image" bash "$SCRIPT" check-updates'

            self.assertEqual(content.count(marker), 1)
            suite = tests / SUITE.name

            suite.write_text(content.replace(marker, '    exit 9\n' + marker))
            result = self.run_command(["bash", str(suite)])

            self.assertEqual(result.returncode, 9)
            self.assertIn("[FAIL] stable major upgrade", result.stderr)
            self.assertNotIn("[OK] stable major upgrade", result.stderr)
            self.assertIn("scenarios passed before failure", result.stderr)

    def test_contract_runner_stops_on_failure_and_missing_python(self):
        with tempfile.TemporaryDirectory(prefix="output-contract-runner-") as directory:
            root = Path(directory)

            (root / "scripts/tests").mkdir(parents=True)
            (root / "scripts/output").mkdir(parents=True)
            (root / "scripts/checks").mkdir(parents=True)
            (root / "scripts/make/tests").mkdir(parents=True)
            (root / "bin").mkdir()
            shutil.copyfile(HELPER, root / "scripts/output/command-output.sh")
            shutil.copyfile(ROOT / "scripts/tests/command-output-contract-test.sh",
                            root / "scripts/tests/command-output-contract-test.sh")
            python = root / "bin/python3"

            shutil.copyfile(ROOT / "scripts/output/native-output.py", root / "scripts/output/native-output.py")
            shutil.copyfile(
                ROOT / "scripts/tests/run-workflow-tests.py",
                root / "scripts/tests/run-workflow-tests.py",
            )
            # Run the real output relay while recording or failing suite invocations.
            python.write_text(
                '#!/bin/bash\n'
                f'if [[ "$1" == scripts/output/native-output.py ]]; then exec {shutil.which("python3")} "$@"; fi\n'
                'printf "%s\\n" "$2" >> "$CALLS"\n'
                'if [[ "$2" == *"$FAIL_SUITE"* ]] && [ -n "$FAIL_SUITE" ]; then exit 7; fi\n'
            )
            python.chmod(0o755)
            routing = root / "scripts/make/tests/make-container-test.sh"

            routing.write_text(
                '#!/bin/bash\nprintf "routing\\n" >> "$CALLS"\n'
                'if [ "$FAIL_SUITE" = routing ]; then exit 7; fi\n'
            )
            pre_pr_test = root / "scripts/make/tests/pre-pr-checks-test.sh"

            pre_pr_test.write_text(
                '#!/bin/bash\nprintf "pre-pr\\n" >> "$CALLS"\n'
                'if [ "$FAIL_SUITE" = pre-pr ]; then exit 7; fi\n'
            )

            for failed in (
                "", "command-output-test.py", "make-information-output-test.py",
                "routing", "pre-pr",
            ):
                calls = root / "calls"

                calls.write_text("")
                result = self.run_command(
                    ["/bin/bash", str(root / "scripts/tests/command-output-contract-test.sh")],
                    PATH=f"{root / 'bin'}:{os.environ['PATH']}", CALLS=str(calls), FAIL_SUITE=failed,
                )

                self.assertEqual(result.returncode, 7 if failed else 0, result.stderr)

                if failed:
                    self.assertIn("[FAIL]", result.stderr)
                    self.assertNotIn("[OK]", result.stderr.split("[FAIL]", 1)[1])
                    self.assertNotIn("10 regression suites passed", result.stderr)

                    if failed == "routing":
                        self.assertEqual(len(calls.read_text().splitlines()), 9)
                    elif failed == "pre-pr":
                        self.assertEqual(len(calls.read_text().splitlines()), 10)
                    else:
                        self.assertNotIn("routing", calls.read_text())
                else:
                    self.assertEqual(len(calls.read_text().splitlines()), 10)
                    self.assertIn("[OK] Command output contracts: 10 regression suites passed", result.stderr)

            python.unlink()
            dirname = shutil.which("dirname")

            assert dirname is not None, "dirname is required for this test"
            (root / "bin/dirname").symlink_to(dirname)
            result = self.run_command(
                ["/bin/bash", str(root / "scripts/tests/command-output-contract-test.sh")],
                PATH=str(root / "bin"),
            )

            self.assertEqual(result.returncode, 127)
            self.assertIn("python3: command not found", result.stderr)
            self.assertIn("[FAIL] command-output regression suite", result.stderr)
            self.assertNotIn("[OK]", result.stderr)

    def test_contract_runner_uses_grouped_nested_output(self):
        suites = (
            "scripts/output/tests/command-output-test.py",
            "scripts/make/tests/make-launcher-test.py",
            "scripts/make/tests/make-check-output-test.py",
            "scripts/make/tests/make-quality-output-test.py",
            "scripts/make/tests/make-lifecycle-output-test.py",
            "scripts/make/tests/make-information-output-test.py",
            "scripts/checks/tests/check-outcomes-test.py",
            "scripts/checks/tests/lint-workflows-output-test.py",
        )

        with tempfile.TemporaryDirectory(prefix="nested-contract-runner-") as directory:
            root = Path(directory)

            (root / "scripts/tests").mkdir(parents=True)
            (root / "scripts/output").mkdir(parents=True)
            (root / "scripts/checks").mkdir(parents=True)
            (root / "scripts/make/tests").mkdir(parents=True)
            for name in (
                "command-output.sh", "command-progress.py", "native-output.py"
            ):
                shutil.copyfile(
                    ROOT / "scripts/output" / name,
                    root / "scripts/output" / name,
                )

            shutil.copyfile(
                ROOT / "scripts/tests/run-workflow-tests.py",
                root / "scripts/tests/run-workflow-tests.py",
            )
            runner = root / "scripts/tests/command-output-contract-test.sh"

            shutil.copyfile(ROOT / "scripts/tests" / runner.name, runner)

            for suite in suites:
                (root / suite).parent.mkdir(parents=True, exist_ok=True)
                (root / suite).write_text(
                    "import os\nimport unittest\n"
                    "class Example(unittest.TestCase):\n"
                    "    def test_grouping_is_not_inherited(self):\n"
                    "        self.assertIsNone(os.environ.get('OUTPUT_RUN_GROUPED'))\n"
                    "    def test_example(self):\n"
                    "        self.assertNotEqual(os.environ.get('FAIL_NESTED'), '1', 'fixture assertion')\n"
                )

            (root / "scripts/make/tests/make-container-test.sh").write_text(
                "printf 'routing detail\\n'\nprintf 'routing warning\\n' >&2\n"
            )
            (root / "scripts/make/tests/pre-pr-checks-test.sh").write_text(
                "printf 'pre-PR detail\\n'\n"
            )

            for failed in ("0", "1"):
                with self.subTest(failed=failed):
                    result = self.run_command(
                        ["bash", str(runner)], FAIL_NESTED=failed, COLOR="never",
                    )

                    self.assertEqual(result.returncode, int(failed), result.stderr)
                    self.assertIn("\n  [RUN] command-output regression suite\n", result.stderr)
                    self.assertIn("    Ran 2 tests", result.stderr)
                    self.assertNotIn("------", result.stderr)
                    self.assertNotIn("\nOK\n", result.stderr)
                    self.assertNotIn("\n    OK\n", result.stderr)

                    if failed == "1":
                        self.assertIn("    FAILED (failures=1)\n", result.stderr)
                        self.assertIn("fixture assertion", result.stderr)
                        self.assertIn("  [FAIL] command-output regression suite (exit 1)", result.stderr)
                        self.assertNotIn("make-launcher regression suite", result.stderr)
                    else:
                        self.assertIn("    routing detail\n", result.stdout)
                        self.assertIn("    routing warning\n", result.stderr)
                        self.assertIn("  [RUN] Pre-PR check selection regression suite\n", result.stderr)
                        self.assertIn("    pre-PR detail\n", result.stdout)
                        self.assertIn(
                            "\n  [OK] command-output regression suite\n"
                            "\n  [RUN] make-launcher regression suite\n", result.stderr,
                        )
                        self.assertRegex(
                            result.stderr,
                            r"\n *\[OK\] Pre-PR check selection regression suite"
                            r"(?: \(took [0-9.]+s\))?\n"
                            r" *\[OK\] Command output contracts: 10 regression suites passed "
                            r"\(took [0-9.]+s\)\n$",
                        )

            result = self.run_command(
                ["bash", str(runner)],
                FAIL_NESTED="0",
                COLOR="never",
                OUTPUT_RUN_GROUPED="1",
                OUTPUT_RUN_TARGET="test-command-output",
                OUTPUT_RUN_TARGET_ACTIVE="",
                PROGRESS="always",
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn(
                "[RUN] Command output contracts (offline fixtures) "
                "(make test-command-output)\n",
                result.stderr,
            )
            self.assertIn(
                "  [INFO] Docker, registries and uploaders are mocked;",
                result.stderr,
            )
            self.assertIn(
                "  [RUN] command-output regression suite\n",
                result.stderr,
            )
            self.assertIn("    Ran 2 tests", result.stderr)
            self.assertIn("    routing detail\n", result.stdout)
            self.assertTrue(
                re.search(
                    r"\n *\[OK\] Pre-PR check selection regression suite"
                    r"(?: \(took [0-9.]+s\))?\n"
                    r" *\[OK\] Command output contracts: 10 regression suites passed "
                    r"\(make test-command-output\) \(took [0-9.]+s\)\n$",
                    result.stderr,
                ),
                result.stderr,
            )


if __name__ == "__main__":
    unittest.main()
