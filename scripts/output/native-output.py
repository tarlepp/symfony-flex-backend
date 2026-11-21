#!/usr/bin/env python3
"""Filter SGR decoration or indent command output while preserving streaming.

Usage:
  From the repository root on the Docker host or in the PHP development container:
    make test-command-output
  Direct invocation from the repository root inside the container:
    python3 scripts/output/native-output.py printf example
    python3 scripts/output/native-output.py --indent printf example

Requirements:
  Python 3.10 or later and pseudo-terminal support.

Details:
  The default mode removes SGR color and style sequences, leaving other escape
  sequences intact. Indent mode adds four spaces to nonempty lines and a final
  newline to unterminated output. Both modes relay stdout and stderr separately,
  using a pseudo-terminal for each terminal destination and a pipe otherwise.
  The supplied command is executed; its side effects are not restricted.

Exit Status:
  Returns the command status, or 128 plus its terminating signal number.
  Missing commands return 127 and missing arguments return 64.

Callers:
  Scripts:
    - `scripts/output/native-output.sh` uses the default filter when native
      color is disabled.
    - `scripts/checks/lint-workflows.sh` and
      `scripts/tests/command-output-contract-test.sh` use indent mode.
"""

import errno
import fcntl
import os
import pty
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


class IndentFilter:
    def __init__(self):
        self.line_start = True

    def feed(self, data, final=False):
        """Indent nonempty lines across chunks and finish a partial final line."""
        output = bytearray()
        for value in data:
            if self.line_start and value not in (10, 13):
                output.extend(b"    ")
            output.append(value)
            self.line_start = value in (10, 13)
        if final and not self.line_start:
            output.extend(b"\n")
            self.line_start = True
        return bytes(output)


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


def run(command, filter_type: type[SgrFilter] | type[IndentFilter] = SgrFilter):
    """Run a command and relay each output stream through its own filter."""
    readers = {}
    child_streams = {}
    terminal_masters = []
    for destination in (1, 2):
        if os.isatty(destination):
            master, slave = _terminal_pair(destination)
            readers[master] = (destination, filter_type())
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
            readers[stream.fileno()] = (destination, filter_type())

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
    if arguments[:1] == ["--indent"]:
        filter_type = IndentFilter
        arguments.pop(0)
    if not arguments:
        print("Usage: native-output.py [--indent] <command> [arguments...]", file=sys.stderr)
        sys.exit(64)
    sys.exit(run(arguments, filter_type))
