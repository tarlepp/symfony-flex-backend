#!/usr/bin/env python3
"""Relay a command's output and draw an idle progress indicator on stderr.

Usage: command-progress.py <label> <color|plain> <command> [arguments...]

Terminal descriptors are relayed through a pseudo-terminal, and other
descriptors through pipes, so the command sees the same descriptor kinds as
when it runs directly. The indicator is drawn only while the command is
silent with the cursor at the start of a line, and is cleared before any
relayed output. With OUTPUT_PROGRESS_SHARED=1 (stdout and stderr were the same
terminal before redirection), stdout joins stderr's pipe to keep them ordered.
"""

import errno
import fcntl
import os
import select
import signal
import struct
import subprocess
import sys
import termios
import time

UTF8_FRAMES = "⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏"
ASCII_FRAMES = "|/-\\"
CLEAR = b"\r\033[K"
INTERVAL = 0.1
DRAIN_IDLE = 0.2


def duration(seconds):
    tenths = int(seconds * 10 + 0.5)
    if tenths < 600:
        return f"{tenths // 10}.{tenths % 10}s"
    whole = int(seconds + 0.5)
    hours, rest = divmod(whole, 3600)
    minutes, seconds = divmod(rest, 60)
    if hours:
        return f"{hours}h {minutes:02d}m {seconds:02d}s"
    return f"{minutes}m {seconds:02d}s"


def utf8_locale():
    for name in ("LC_ALL", "LC_CTYPE", "LANG"):
        value = os.environ.get(name)
        if value:
            return "utf-8" in value.lower() or "utf8" in value.lower()
    return False


def terminal_width(descriptor):
    try:
        _, columns, _, _ = struct.unpack("HHHH", fcntl.ioctl(descriptor, termios.TIOCGWINSZ, b"\0" * 8))
        if columns:
            return columns
    except OSError:
        pass
    try:
        return max(int(os.environ.get("COLUMNS", "80")), 20)
    except ValueError:
        return 80


def write_all(descriptor, data):
    while data:
        written = os.write(descriptor, data)
        data = data[written:]


class Relay:
    def __init__(self, label, color, command):
        self.label = label
        self.color = color
        self.command = command
        self.frames = UTF8_FRAMES if utf8_locale() else ASCII_FRAMES
        self.delay = float(os.environ.get("OUTPUT_PROGRESS_DELAY", "1"))
        self.sources = {}
        self.broken = set()
        self.drawn = False
        self.line_start = True
        self.frame = 0
        self.child = None

    def descriptors(self):
        """Return the child's stdout and stderr, registering relayed sources."""
        stdout_tty, stderr_tty = os.isatty(1), os.isatty(2)
        if stderr_tty:
            if stdout_tty and not os.path.samestat(os.fstat(1), os.fstat(2)):
                return None
            master, slave = os.openpty()
            try:
                attributes = termios.tcgetattr(2)
                # The outer terminal already translates output; avoid doubling carriage returns.
                attributes[1] &= ~termios.OPOST
                termios.tcsetattr(slave, termios.TCSANOW, attributes)
                fcntl.ioctl(slave, termios.TIOCSWINSZ, fcntl.ioctl(2, termios.TIOCGWINSZ, b"\0" * 8))
            except OSError:
                pass
            self.sources[master] = 2
            return (slave if stdout_tty else None), slave
        error_read, error_write = os.pipe()
        self.sources[error_read] = 2
        output_write = None
        if os.environ.get("OUTPUT_PROGRESS_SHARED") == "1":
            # Both streams end on one terminal; separate pipes could reorder output and spinner clears.
            return error_write, error_write
        if not stdout_tty:
            output_read, output_write = os.pipe()
            self.sources[output_read] = 1
        return output_write, error_write

    def draw(self, elapsed):
        frame = self.frames[self.frame % len(self.frames)]
        self.frame += 1
        text = f"{self.label} ({duration(elapsed)})"
        room = terminal_width(2) - 3
        if len(text) > room:
            text = text[: max(room - 3, 0)] + "..."
        if self.color:
            frame = f"\033[34m{frame}\033[0m"
        self.screen(CLEAR + f"{frame} {text}".encode())
        self.drawn = True

    def clear(self):
        if self.drawn:
            self.screen(CLEAR)
            self.drawn = False

    def screen(self, data):
        self.forward(2, data)

    def forward(self, target, data):
        if target in self.broken:
            return
        try:
            write_all(target, data)
        except BrokenPipeError:
            self.broken.add(target)

    def forward_signal(self, number, _frame):
        if self.child is not None and self.child.poll() is None:
            self.child.send_signal(number)

    def run(self):
        descriptors = self.descriptors()
        if descriptors is None:
            os.execvp(self.command[0], self.command)
        stdout, stderr = descriptors
        environment = dict(os.environ, PROGRESS="never")
        environment.pop("OUTPUT_PROGRESS_SHARED", None)
        coerce = environment.pop("OUTPUT_PROGRESS_COERCE", "")
        if coerce.startswith("set:"):
            environment["PYTHONCOERCECLOCALE"] = coerce[4:]
        else:
            environment.pop("PYTHONCOERCECLOCALE", None)
        try:
            self.child = subprocess.Popen(self.command, stdout=stdout, stderr=stderr, env=environment)
        except FileNotFoundError:
            sys.stderr.write(f"{self.command[0]}: command not found\n")
            return 127
        except PermissionError:
            sys.stderr.write(f"{self.command[0]}: Permission denied\n")
            return 126
        finally:
            for descriptor in {stdout, stderr} - {None}:
                os.close(descriptor)
        # Terminal interrupts reach the command's process group directly; wait for its outcome.
        signal.signal(signal.SIGINT, signal.SIG_IGN)
        for number in (signal.SIGTERM, signal.SIGHUP):
            signal.signal(number, self.forward_signal)
        try:
            self.relay()
        finally:
            self.clear()
            for descriptor in self.sources:
                os.close(descriptor)
        status = self.child.wait()
        return 128 - status if status < 0 else status

    def relay(self):
        started = last_output = time.monotonic()
        finished = None
        while self.sources:
            try:
                ready, _, _ = select.select(list(self.sources), [], [], INTERVAL)
            except InterruptedError:
                ready = []
            now = time.monotonic()
            for source in ready:
                try:
                    data = os.read(source, 65536)
                except OSError as error:
                    if error.errno != errno.EIO:
                        raise
                    data = b""
                if not data:
                    os.close(source)
                    del self.sources[source]
                    continue
                self.clear()
                self.forward(self.sources[source], data)
                self.line_start = data.endswith(b"\n")
                last_output = now
            if self.child.poll() is not None:
                finished = finished or now
                if now - max(finished, last_output) >= DRAIN_IDLE:
                    break
                continue
            if self.line_start and now - last_output >= self.delay and 2 not in self.broken:
                self.draw(now - started)


def main(arguments):
    if len(arguments) < 3 or arguments[1] not in ("color", "plain"):
        sys.stderr.write("Usage: command-progress.py <label> <color|plain> <command> [arguments...]\n")
        return 64
    return Relay(arguments[0], arguments[1] == "color", arguments[2:]).run()


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
