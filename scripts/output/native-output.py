#!/usr/bin/env python3
"""Filter SGR decoration or indent output without hiding TTYs or partial lines."""

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


def run(command, filter_type=SgrFilter):
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
                    (process.stdout if destination == 1 else process.stderr).close()

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
