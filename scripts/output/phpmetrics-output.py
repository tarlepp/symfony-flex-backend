#!/usr/bin/env python3
"""Color PhpMetrics console output without buffering partial lines."""

import sys
from io import BufferedReader
from typing import cast


def main():
    line_start = True
    indented = False
    heading = False
    number = False
    escape = False
    csi = False
    output = sys.stdout.buffer

    input_stream = cast(BufferedReader, sys.stdin.buffer)
    while chunk := input_stream.read1(4096):
        rendered = bytearray()
        for byte in chunk:
            if escape:
                rendered.append(byte)
                if byte == 91 and not csi:
                    csi = True
                elif not csi or 64 <= byte <= 126:
                    escape = False
                    csi = False
                continue
            if byte == 27:
                if heading or number:
                    rendered.extend(b"\x1b[0m")
                    heading = number = False
                rendered.append(byte)
                escape = True
                line_start = False
                continue
            if byte in (10, 13):
                if heading or number:
                    rendered.extend(b"\x1b[0m")
                rendered.append(byte)
                line_start = True
                indented = heading = number = False
                continue
            if line_start:
                indented = byte in (9, 32)
                if not indented:
                    rendered.extend(b"\x1b[1;34m")
                    heading = True
                line_start = False
            if indented:
                numeric = 48 <= byte <= 57 or (number and byte == 46)
                if numeric and not number:
                    rendered.extend(b"\x1b[34m")
                    number = True
                elif not numeric and number:
                    rendered.extend(b"\x1b[0m")
                    number = False
            rendered.append(byte)
        output.write(rendered)
        output.flush()

    if heading or number:
        output.write(b"\x1b[0m")
        output.flush()


if __name__ == "__main__":
    main()
