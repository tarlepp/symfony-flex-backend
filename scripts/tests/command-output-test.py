#!/usr/bin/env python3
"""Offline regression checks for command status output and the Docker test pilot."""

import os
from pathlib import Path
import pty
import select
import shutil
import signal
import subprocess
import tempfile
import time
import unittest


ROOT = Path(__file__).resolve().parents[2]
HELPER = ROOT / "scripts/command-output.sh"
SUITE = ROOT / "scripts/tests/docker-images-test.sh"
NATIVE = ROOT / "scripts/native-output.sh"
DOCKER_IMAGE_TEST_GROUPS = (
    ("Manifest validation", 8),
    ("Pinned tag/digest parity", 5),
    ("Version discovery and output formatting", 18),
    ("Registry responses and pagination", 7),
    ("Dockerfile discovery", 3),
    ("Registry lookup failures and retries", 8),
    ("Update-pin validation", 10),
    ("Aggregate results and empty discovery", 4),
)
DOCKER_IMAGE_TEST_COUNT = sum(count for _, count in DOCKER_IMAGE_TEST_GROUPS)


class CommandOutputTest(unittest.TestCase):
    def environment(self, **overrides):
        environment = dict(os.environ, COLOR="auto", TERM="xterm", PROGRESS="never", LANG="C")
        for name in ("NO_COLOR", "NATIVE_COLOR", "CI", "LC_ALL", "LC_CTYPE", "OUTPUT_PROGRESS_DELAY", "OUTPUT_PROGRESS_STARTED",
                     "OUTPUT_PROGRESS_SHARED"):
            environment.pop(name, None)
        environment.update(overrides)
        return environment

    def run_terminal(self, command, stdout_terminal=True, **environment):
        """Run with stderr (and optionally stdout) on a pseudo-terminal; return status, terminal and stdout bytes."""
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
        stdout = b"" if stdout_terminal else process.stdout.read()
        return process.wait(timeout=10), output, stdout

    def run_command(self, command, **environment):
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
        process = subprocess.Popen(
            ["bash", str(NATIVE), "bash", "-c",
             "printf '\\033['; sleep 0.1; printf '32mready'; "
             "printf '\\033[31merror\\033[0m' >&2; sleep 30"],
            env=self.environment(COLOR="never"), stdout=subprocess.PIPE,
            stderr=subprocess.PIPE, start_new_session=True,
        )
        try:
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
        helper = ROOT / "scripts/report-output.sh"
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
                    command, OUTPUT_PROGRESS_DELAY="0.1", **environment,
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
            "empty image index",
            "malformed manifest JSON",
        ):
            self.assertEqual(
                result.stderr.count(f"[OK]\033[0m reject update: {scenario}\n"), 1,
                result.stderr,
            )
        self.assertNotIn("[PASS]", result.stderr)
        self.assertIn("scenarios passed", result.stderr)
        self.assertIn(
            "[OK]\033[0m empty image discovery fails explicitly\n\n"
            f"\033[32m[OK]\033[0m Docker image tests: {DOCKER_IMAGE_TEST_COUNT} scenarios passed\n",
            result.stderr,
        )
        self.assertNotIn("[FAIL]", result.stderr)

    def test_suite_groups_keep_individual_results_in_order(self):
        result = self.run_command(["bash", str(SUITE)], COLOR="never")
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        groups = DOCKER_IMAGE_TEST_GROUPS
        headings = [line for line in result.stderr.splitlines() if line.startswith("[RUN]")]
        self.assertEqual(headings, ["[RUN] Docker image tests"] + [
            f"[RUN] {name}" for name, _ in groups
        ])
        for index, (name, count) in enumerate(groups):
            section = result.stderr.split(f"[RUN] {name}\n", 1)[1]
            section = section.split("\n[RUN]", 1)[0]
            results = [line for line in section.splitlines() if line.startswith("[OK]")]
            if index == len(groups) - 1:
                self.assertEqual(
                    results.pop(),
                    f"[OK] Docker image tests: {DOCKER_IMAGE_TEST_COUNT} scenarios passed",
                )
            self.assertEqual(len(results), count, section)
        self.assertNotIn("\033", result.stderr)

    def test_suite_assertion_failure_has_context_and_captured_output(self):
        with tempfile.TemporaryDirectory(prefix="command-output-test-") as directory:
            scripts = Path(directory) / "scripts"
            tests = scripts / "tests"
            tests.mkdir(parents=True)
            for name in ("command-output.sh", "docker-images.sh"):
                shutil.copyfile(ROOT / "scripts" / name, scripts / name)
            content = SUITE.read_text()
            assertion = "grep -Fxq 'IMAGE input (no matching Dockerfile)'"
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
            tests = scripts / "tests"
            tests.mkdir(parents=True)
            for name in ("command-output.sh", "docker-images.sh"):
                shutil.copyfile(ROOT / "scripts" / name, scripts / name)
            content = SUITE.read_text()
            marker = '    start_case "$name"\n    : > "$TEST_REQUESTS"\n'
            self.assertEqual(content.count(marker), 1)
            suite = tests / SUITE.name
            suite.write_text(content.replace(marker, marker + "    exit 9\n"))
            result = self.run_command(["bash", str(suite)])
            self.assertEqual(result.returncode, 9)
            self.assertIn("[FAIL] stable major upgrade", result.stderr)
            self.assertNotIn("[OK] stable major upgrade", result.stderr)
            self.assertIn("scenarios passed before failure", result.stderr)

    def test_contract_runner_stops_on_failure_and_missing_python(self):
        with tempfile.TemporaryDirectory(prefix="output-contract-runner-") as directory:
            root = Path(directory)
            (root / "scripts/tests").mkdir(parents=True)
            (root / "bin").mkdir()
            shutil.copyfile(HELPER, root / "scripts/command-output.sh")
            shutil.copyfile(ROOT / "scripts/tests/command-output-contract-test.sh",
                            root / "scripts/tests/command-output-contract-test.sh")
            python = root / "bin/python3"
            python.write_text(
                '#!/bin/bash\nprintf "%s\\n" "$1" >> "$CALLS"\n'
                'if [[ "$1" == *"$FAIL_SUITE"* ]] && [ -n "$FAIL_SUITE" ]; then exit 7; fi\n'
            )
            python.chmod(0o755)
            routing = root / "scripts/tests/make-container-test.sh"
            routing.write_text(
                '#!/bin/bash\nprintf "routing\\n" >> "$CALLS"\n'
                'if [ "$FAIL_SUITE" = routing ]; then exit 7; fi\n'
            )
            for failed in ("", "command-output-test.py", "make-information-output-test.py", "routing"):
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
                    self.assertNotIn("9 regression suites passed", result.stderr)
                    if failed == "routing":
                        self.assertEqual(len(calls.read_text().splitlines()), 9)
                    else:
                        self.assertNotIn("routing", calls.read_text())
                else:
                    self.assertEqual(len(calls.read_text().splitlines()), 9)
                    self.assertIn("[OK] Command output contracts: 9 regression suites passed", result.stderr)
            python.unlink()
            (root / "bin/dirname").symlink_to(shutil.which("dirname"))
            result = self.run_command(
                ["/bin/bash", str(root / "scripts/tests/command-output-contract-test.sh")],
                PATH=str(root / "bin"),
            )
            self.assertEqual(result.returncode, 127)
            self.assertIn("python3: command not found", result.stderr)
            self.assertIn("[FAIL] command-output regression suite", result.stderr)
            self.assertNotIn("[OK]", result.stderr)


if __name__ == "__main__":
    unittest.main()
