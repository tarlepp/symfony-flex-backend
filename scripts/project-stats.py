#!/usr/bin/env python3
"""Summarize tracked working-tree files by extension."""

import os
from pathlib import Path
import subprocess
import sys


GENERATED_FILES = {
    "config/reference.php",
    "docker/nginx/ssl/rootCA.key",
    "docker/nginx/ssl/rootCA.pem",
    "docker/nginx/ssl/tls.crt",
    "docker/nginx/ssl/tls.csr",
    "docker/nginx/ssl/tls.key",
}
LOCKFILE_NAMES = {"package-lock.json", "npm-shrinkwrap.json", "pnpm-lock.yaml", "bun.lockb"}
GENERATED_ASSET_SUFFIXES = (".min.js", ".min.css", ".js.map", ".css.map")


def excluded_from_statistics(relative_path):
    name = relative_path.name.lower()
    return (
        relative_path.as_posix() in GENERATED_FILES
        or name.endswith((".lock", *GENERATED_ASSET_SUFFIXES))
        or name in LOCKFILE_NAMES
    )


def file_type(path):
    name = path.name
    if name.startswith(".") and name.count(".") == 1:
        return name.lower()
    suffix = path.suffix.lower()
    return suffix if suffix else "(no extension)"


def text_line_count(path):
    if path.is_symlink():
        return 0
    count = 0
    with path.open("rb") as source:
        while chunk := source.read(65536):
            if b"\0" in chunk:
                return 0
            count += chunk.count(b"\n")
    return count


def color_enabled():
    if os.environ.get("NO_COLOR"):
        return False
    native_color = os.environ.get("NATIVE_COLOR")
    if native_color is not None:
        return native_color == "always"
    color = os.environ.get("COLOR", "auto")
    return color == "always" or (
        color == "auto" and sys.stdout.isatty() and os.environ.get("TERM") != "dumb"
    )


def main():
    root = subprocess.run(
        ["git", "rev-parse", "--show-toplevel"],
        check=True, stdout=subprocess.PIPE,
    ).stdout.removesuffix(b"\n")
    files = subprocess.run(
        ["git", "-C", os.fsdecode(root), "ls-files", "--cached", "--full-name", "-z"],
        check=True, stdout=subprocess.PIPE,
    ).stdout.split(b"\0")

    statistics = {}
    for name in sorted(set(files) - {b""}):
        relative_path = Path(os.fsdecode(name))
        if excluded_from_statistics(relative_path):
            continue
        path = Path(os.fsdecode(root)) / relative_path
        extension = file_type(path)
        files_count, lines_count = statistics.get(extension, (0, 0))
        statistics[extension] = (files_count + 1, lines_count + text_line_count(path))

    # Escape control characters in unusual filenames before displaying their suffixes.
    rows = [
        ("".join(char if char.isprintable() else f"\\x{ord(char):02x}" for char in extension), *counts)
        for extension, counts in sorted(statistics.items())
    ]
    total_files = sum(counts[0] for counts in statistics.values())
    total_lines = sum(counts[1] for counts in statistics.values())
    type_width = max(len("File type"), *(len(row[0]) for row in rows), len("(no extension)"))
    files_width = max(len("Files"), len(f"{total_files:,}"))
    lines_width = max(len("Text lines"), len(f"{total_lines:,}"))
    separator = f"{'-' * type_width}  {'-' * files_width}  {'-' * lines_width}"

    use_color = color_enabled()

    def highlight(text, code):
        return f"\033[{code}m{text}\033[0m" if use_color else text

    print(highlight("Project file statistics", "1;34"))
    print("Tracked working-tree files (excluding generated artifacts)")
    print()
    print(highlight(f"{'File type':<{type_width}}  {'Files':>{files_width}}  {'Text lines':>{lines_width}}", "1"))
    print(separator)
    for extension, files_count, lines_count in rows:
        print(f"{extension:<{type_width}}  {files_count:>{files_width},}  {lines_count:>{lines_width},}")
    print(separator)
    print(highlight(f"{'Total':<{type_width}}  {total_files:>{files_width},}  {total_lines:>{lines_width},}", "1"))
    print()
    print(highlight("Summary", "1;34"))
    print(f"  Included files: {total_files:,}")
    print(f"  File types:    {len(statistics):,}")
    print(f"  Text lines:    {total_lines:,}")
    for heading, index, unit in (
        ("Most files", 1, "files"),
        ("Most text lines", 2, "lines"),
    ):
        leaders = sorted((row for row in rows if row[index] > 0), key=lambda row: -row[index])[:3]
        print(f"\n  {highlight(f'{heading}:', '1')}")
        if not leaders:
            print("    None")
        for extension, files_count, lines_count in leaders:
            value = (files_count, lines_count)[index - 1]
            print(f"    {extension}: {value:,} {unit if value != 1 else unit[:-1]}")


if __name__ == "__main__":
    try:
        main()
    except (OSError, subprocess.CalledProcessError) as error:
        print(f"Project statistics failed: {error}", file=sys.stderr)
        sys.exit(1)
