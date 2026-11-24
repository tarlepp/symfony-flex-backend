#!/usr/bin/env python3
"""Filter SGR decoration or indent command output while preserving streaming."""

#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make test-command-output
#   Direct invocation from the repository root inside the container:
#     python3 scripts/output/native-output.py printf example
#     python3 scripts/output/native-output.py --indent printf example
#     python3 scripts/output/native-output.py --indent --compact-status-spacing printf example
#     python3 scripts/output/native-output.py --suppress-deprecation-info command
#
# Requirements:
#   Python 3.10 or later and pseudo-terminal support.
#
# Details:
#   The default mode removes SGR color and style sequences, leaving other escape
#   sequences intact. Indent mode adds two spaces to nonempty ordinary lines,
#   preserves GitHub workflow commands at column zero, and finishes unterminated
#   output with a newline. Compact status spacing also removes blank padding
#   immediately before `[OK]` lines and at end of stream. Both modes relay stdout
#   and stderr separately, using a pseudo-terminal for each terminal destination
#   and a pipe otherwise.
#   Deprecation suppression passes stdout unchanged and removes timestamped
#   Monolog deprecation.INFO records from stderr, retaining other diagnostics.
#   The supplied command is executed; its side effects are not restricted.
#
# Exit Status:
#   Returns the command status, or 128 plus its terminating signal number.
#   Missing commands return 127 and missing arguments return 64.
#
# Callers:
#   Scripts:
#     - `scripts/output/native-output.sh` uses the default filter when native
#       color is disabled.
#     - `scripts/checks/lint-workflows.sh` and
#       `scripts/tests/command-output-contract-test.sh` use indent mode.

import errno
import fcntl
import os
import pty
import re
import selectors
import signal
import subprocess
import sys
import termios


class SgrFilter:
    def __init__(self):
        self.pending = b""

    def feed(self, data, final=False):
        """Remove complete SGR sequences, buffering a possible split sequence."""
        data = self.pending + data
        self.pending = b""
        output = bytearray()
        index = 0

        while index < len(data):
            if data[index] != 0x1B:
                output.append(data[index])
                index += 1

                continue

            if index + 1 >= len(data):
                if final:
                    output.extend(data[index:])
                else:
                    self.pending = data[index:]

                break

            if data[index + 1] != ord("["):
                output.append(data[index])
                index += 1

                continue

            end = index + 2

            while end < len(data) and (
                ord("0") <= data[end] <= ord("9") or data[end] == ord(";")
            ):
                end += 1

            if end >= len(data):
                if final:
                    output.extend(data[index:])
                else:
                    self.pending = data[index:]

                break

            if data[end] == ord("m"):
                index = end + 1
            else:
                output.extend(data[index : end + 1])
                index = end + 1

        return bytes(output)


class PassThroughFilter:
    def feed(self, data, final=False):
        return data


class DeprecationInfoFilter:
    """Drop timestamped Monolog deprecation.INFO lines from stderr."""

    timestamp = re.compile(rb"\[\d{4}-[0-9T:.\-+]+\]")
    marker = b" deprecation.INFO:"

    def __init__(self):
        self.pending = bytearray()
        self.state = "start"

    def feed(self, data, final=False):
        output = bytearray()
        for value in data:
            if self.state == "skip":
                if value == 10:
                    self.state = "start"
                continue
            if self.state == "pass":
                output.append(value)
                if value == 10:
                    self.state = "start"
                continue

            self.pending.append(value)
            candidate = bytes(self.pending).lstrip(b" \t")
            possible = True
            if candidate:
                if candidate[:1] != b"[":
                    possible = False
                elif b"]" not in candidate:
                    date = candidate[1:]
                    year = date[:4]
                    possible = year.isdigit() or not year
                    if len(date) > 4:
                        possible = possible and date[4:5] == b"-" and all(
                            byte in b"0123456789T:.-+" for byte in date[4:]
                        )
                else:
                    end = candidate.index(b"]") + 1
                    suffix = candidate[end:]
                    possible = bool(self.timestamp.fullmatch(candidate[:end])) and (
                        self.marker.startswith(suffix) or suffix == self.marker
                    )
                    if possible and suffix == self.marker:
                        self.pending.clear()
                        self.state = "skip"
                        continue

            if not possible or value == 10 or len(self.pending) > 128:
                output.extend(self.pending)
                self.pending.clear()
                self.state = "start" if value == 10 else "pass"

        if final:
            output.extend(self.pending)
            self.pending.clear()
        return bytes(output)


class IndentFilter:
    def __init__(self):
        self.line_start = True
        self.pending = bytearray()

    def feed(self, data, final=False):
        """Indent output lines but preserve GitHub workflow commands at column zero."""
        output = bytearray()

        for value in data:
            if self.line_start:
                if self.pending:
                    self.pending.append(value)
                    if bytes(self.pending) == b"::":
                        output.extend(self.pending)
                        self.pending.clear()
                        self.line_start = False
                    elif not b"::".startswith(bytes(self.pending)):
                        output.extend(b"  ")
                        output.extend(self.pending)
                        self.line_start = self.pending[-1] in (10, 13)
                        self.pending.clear()
                    continue
                if value in (10, 13):
                    output.append(value)
                elif value == ord(":"):
                    self.pending.append(value)
                else:
                    output.extend(b"  ")
                    output.append(value)
                    self.line_start = False
            else:
                output.append(value)

            if value in (10, 13):
                self.line_start = True

        if final:
            if self.pending:
                output.extend(b"  ")
                output.extend(self.pending)
                self.pending.clear()
                self.line_start = False
            if not self.line_start:
                output.extend(b"\n")
                self.line_start = True
        return bytes(output)


class CompactStatusIndentFilter:
    """Indent output while removing blank padding adjacent to success statuses."""

    success_status = b"[OK]"
    sgr = re.compile(rb"\x1b\[[0-9;]*m")

    def __init__(self):
        self.indent = IndentFilter()
        self.pending_blank_lines = 0
        self.prefix = bytearray()
        self.candidate = bytearray()
        self.trailing = bytearray()
        self.line_start = True
        self.checking_status = False
        self.status_line = False

    def _status_state(self):
        """Return whether the buffered prefix is a complete or possible [OK] line."""
        position = 0
        while self.candidate[position : position + 1] == b"\x1b":
            if self.candidate[position : position + 2] != b"\x1b[":
                return None if position + 1 == len(self.candidate) else False
            match = self.sgr.match(self.candidate, position)
            if match is None:
                partial = self.candidate[position:]
                return None if re.fullmatch(rb"\x1b\[[0-9;]*", partial) else False
            position = match.end()

        text = self.candidate[position:]
        if text == self.success_status:
            return True
        if len(text) <= len(self.success_status) and self.success_status.startswith(text):
            return None
        return False

    def feed(self, data, final=False):
        """Stream output, holding only blank padding and line-edge whitespace."""
        output = bytearray()

        def flush_blanks():
            output.extend(b"\n" * self.pending_blank_lines)
            self.pending_blank_lines = 0

        for value in data:
            if self.checking_status:
                self.candidate.append(value)
                state = self._status_state()
                if state is True:
                    self.pending_blank_lines = 0
                    output.extend(self.prefix)
                    output.extend(self.candidate)
                    self.prefix.clear()
                    self.candidate.clear()
                    self.checking_status = False
                    self.line_start = False
                    self.status_line = True
                elif state is False or value == 10:
                    flush_blanks()
                    output.extend(self.prefix)
                    output.extend(self.candidate)
                    self.prefix.clear()
                    self.candidate.clear()
                    self.checking_status = False
                    self.status_line = False
                    if value == 10:
                        output.append(value)
                        self.line_start = True
                    else:
                        self.line_start = False
                continue

            if self.line_start:
                if value in (32, 9, 13):
                    self.prefix.append(value)
                elif value == 10:
                    self.prefix.clear()
                    self.pending_blank_lines += 1
                elif value in (ord("["), 27):
                    self.candidate.append(value)
                    self.checking_status = True
                else:
                    flush_blanks()
                    output.extend(self.prefix)
                    self.prefix.clear()
                    output.append(value)
                    self.line_start = value == 13
                    self.status_line = False
            elif value == 10:
                if self.status_line:
                    self.trailing.clear()
                else:
                    output.extend(self.trailing)
                    self.trailing.clear()
                output.append(value)
                self.line_start = True
                self.status_line = False
            elif value in (32, 9, 13) and self.status_line:
                self.trailing.append(value)
            else:
                output.extend(self.trailing)
                self.trailing.clear()
                output.append(value)

        if final:
            if self.checking_status:
                flush_blanks()
                output.extend(self.prefix)
                output.extend(self.candidate)
                self.prefix.clear()
                self.candidate.clear()
                self.checking_status = False
            elif not self.line_start:
                output.extend(self.trailing)
            self.pending_blank_lines = 0
            self.prefix.clear()
            self.trailing.clear()

        return self.indent.feed(bytes(output), final=final)


def _terminal_pair(fd):
    master, slave = pty.openpty()
    settings = termios.tcgetattr(slave)
    # The outer terminal handles output processing; avoid translating newlines twice.
    settings[1] &= ~termios.OPOST

    termios.tcsetattr(slave, termios.TCSANOW, settings)

    try:
        size = fcntl.ioctl(fd, termios.TIOCGWINSZ, b"\0" * 8)

        fcntl.ioctl(slave, termios.TIOCSWINSZ, size)
    except OSError:
        pass

    return master, slave


def _write_all(fd, data):
    while data:
        written = os.write(fd, data)
        data = data[written:]


def run(
    command,
    filter_type: type[SgrFilter] | type[IndentFilter] | type[CompactStatusIndentFilter] = SgrFilter,
    suppress_deprecation_info=False,
):
    """Run a command and relay each output stream through its own filter."""
    readers = {}
    child_streams = {}
    terminal_masters = []

    for destination in (1, 2):
        if os.isatty(destination):
            master, slave = _terminal_pair(destination)
            output_filter = (
                DeprecationInfoFilter() if destination == 2 else PassThroughFilter()
            ) if suppress_deprecation_info else filter_type()
            readers[master] = (destination, output_filter)
            child_streams[destination] = slave

            terminal_masters.append(master)
        else:
            child_streams[destination] = subprocess.PIPE

    try:
        process = subprocess.Popen(
            command,
            stdin=None,
            stdout=child_streams[1],
            stderr=child_streams[2],
            close_fds=True,
        )
    except FileNotFoundError:
        print(f"{command[0]}: command not found", file=sys.stderr)

        return 127
    finally:
        for stream in child_streams.values():
            if isinstance(stream, int) and stream >= 0:
                os.close(stream)

    for destination in (1, 2):
        if child_streams[destination] == subprocess.PIPE:
            stream = process.stdout if destination == 1 else process.stderr

            if stream is None:
                raise RuntimeError(f"Child process stream {destination} was not captured")
            output_filter = (
                DeprecationInfoFilter() if destination == 2 else PassThroughFilter()
            ) if suppress_deprecation_info else filter_type()
            readers[stream.fileno()] = (destination, output_filter)

    def forward_signal(signum, _frame):
        if process.poll() is None:
            process.send_signal(signum)

    for signum in (signal.SIGINT, signal.SIGTERM, signal.SIGHUP):
        signal.signal(signum, forward_signal)

    with selectors.DefaultSelector() as selector:
        for fd in readers:
            selector.register(fd, selectors.EVENT_READ)

        while selector.get_map():
            for key, _ in selector.select():
                fd = key.fd
                destination, filter_output = readers[fd]

                try:
                    data = os.read(fd, 4096)
                except OSError as error:
                    # PTY masters can report EIO when their slave closes.
                    if error.errno != errno.EIO:
                        raise

                    data = b""

                if data:
                    filtered = filter_output.feed(data)

                    if filtered:
                        _write_all(destination, filtered)

                    continue

                selector.unregister(fd)
                filtered = filter_output.feed(b"", final=True)

                if filtered:
                    _write_all(destination, filtered)

                if fd in terminal_masters:
                    os.close(fd)
                else:
                    stream = process.stdout if destination == 1 else process.stderr

                    if stream is None:
                        raise RuntimeError(
                            f"Child process stream {destination} was not captured",
                        )

                    stream.close()

    returncode = process.wait()

    return 128 - returncode if returncode < 0 else returncode


if __name__ == "__main__":
    arguments = sys.argv[1:]
    filter_type = SgrFilter
    indent = False
    compact_status_spacing = False
    suppress_deprecation_info = False
    while arguments and arguments[0] in (
        "--indent", "--compact-status-spacing", "--suppress-deprecation-info",
    ):
        option = arguments.pop(0)
        if option == "--indent":
            indent = True
        elif option == "--compact-status-spacing":
            compact_status_spacing = True
        else:
            suppress_deprecation_info = True
    if suppress_deprecation_info and (indent or compact_status_spacing):
        print("--suppress-deprecation-info cannot be combined with indentation", file=sys.stderr)
        sys.exit(64)
    if compact_status_spacing:
        if not indent:
            print("--compact-status-spacing requires --indent", file=sys.stderr)
            sys.exit(64)
        filter_type = CompactStatusIndentFilter
    elif indent:
        filter_type = IndentFilter
    if not arguments:
        print(
            "Usage: native-output.py [--indent] [--compact-status-spacing] "
            "[--suppress-deprecation-info] "
            "<command> [arguments...]",
            file=sys.stderr,
        )
        sys.exit(64        )
    sys.exit(run(arguments, filter_type, suppress_deprecation_info))
