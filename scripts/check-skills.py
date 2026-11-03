#!/usr/bin/env python3
"""Validate skill metadata, index coverage, and repository Markdown links."""

from __future__ import annotations

import argparse
import html
import os
import re
import sys
import unicodedata
from collections import Counter
from pathlib import Path
from urllib.parse import unquote, urlsplit


LINK_PATTERN = re.compile(
    r"!? \[[^\]]*\] \( \s* (?:<([^>]+)>|([^\s)]+)) [^)]* \)",
    re.VERBOSE,
)
HEADING_PATTERN = re.compile(r"^\s{0,3}#{1,6}\s+(.+?)\s*#*\s*$")
ANCHOR_PATTERN = re.compile(r"""<a\b[^>]*\sid=["']([^"']+)["'][^>]*>""", re.IGNORECASE)
FRONT_MATTER_FIELD_PATTERN = re.compile(r"^([A-Za-z0-9_-]+):\s*(.*)$")
FENCE_PATTERN = re.compile(r"^\s{0,3}(`{3,}|~{3,})")
CANONICAL_AI_DOCS = (
    "README.md",
    "AGENTS.md",
    "CLAUDE.md",
    ".github/copilot-instructions.md",
    "doc/AI_RULES.md",
    ".github/pull_request_template.md",
)
IGNORED_MARKDOWN_DIRECTORIES = {
    ".git",
    ".idea",
    "node_modules",
    "tools",
    "var",
    "vendor",
}


def relative_path(path: Path, root: Path) -> str:
    return path.relative_to(root).as_posix()


def strip_fenced_code(content: str) -> str:
    output = []
    fence_char = None
    fence_length = 0

    for line in content.splitlines(keepends=True):
        match = FENCE_PATTERN.match(line)
        if fence_char is None and match:
            fence_char = match.group(1)[0]
            fence_length = len(match.group(1))
            output.append("\n" if line.endswith("\n") else "")
            continue

        if fence_char is not None:
            closing = re.match(r"^\s{0,3}(" + re.escape(fence_char) + r"{3,})\s*$", line.rstrip("\n"))
            if closing and len(closing.group(1)) >= fence_length:
                fence_char = None
                fence_length = 0
            output.append("\n" if line.endswith("\n") else "")
            continue

        output.append(line)

    return "".join(output)


def parse_front_matter(skill_file: Path, root: Path, errors: list[str]) -> dict[str, str]:
    display_path = relative_path(skill_file, root)
    content = skill_file.read_text(encoding="utf-8")
    lines = content.splitlines()
    if not lines or lines[0] != "---":
        errors.append(f"{display_path}:1: missing opening YAML front matter delimiter")
        return {}

    try:
        closing = lines.index("---", 1)
    except ValueError:
        errors.append(f"{display_path}:1: missing closing YAML front matter delimiter")
        return {}

    fields: dict[str, str] = {}
    for line_number, line in enumerate(lines[1:closing], start=2):
        if not line.strip() or line.lstrip().startswith("#"):
            continue
        match = FRONT_MATTER_FIELD_PATTERN.match(line)
        if not match:
            errors.append(f"{display_path}:{line_number}: invalid front matter field")
            continue

        key, value = match.groups()
        if key in fields:
            errors.append(f"{display_path}:{line_number}: duplicate front matter field '{key}'")
            continue
        value = value.strip()
        if value.startswith(("'", '"')):
            if len(value) < 2 or value[-1] != value[0]:
                errors.append(f"{display_path}:{line_number}: invalid quoted front matter value")
                continue
            value = value[1:-1]
        fields[key] = value

    for key in ("name", "description"):
        if not fields.get(key, "").strip():
            errors.append(f"{display_path}: missing non-empty '{key}' front matter field")

    expected_name = skill_file.parent.name
    if fields.get("name") and fields["name"] != expected_name:
        errors.append(
            f"{display_path}: front matter name '{fields['name']}' "
            f"does not match directory '{expected_name}'"
        )

    return fields


def markdown_slug(text: str) -> str:
    text = html.unescape(text)
    text = re.sub(r"<[^>]+>", "", text)
    text = re.sub(r"!?\[([^\]]*)\]\([^)]*\)", r"\1", text)
    text = re.sub(r"`+", "", text)
    characters = []
    for character in text.lower():
        category = unicodedata.category(character)
        if character.isspace() or character == "-" or category[0] in {"L", "N"}:
            characters.append(character)

    slug = "-".join("".join(characters).split())
    return re.sub(r"-+", "-", slug).strip("-")


def markdown_anchors(markdown_file: Path) -> set[str]:
    content = strip_fenced_code(markdown_file.read_text(encoding="utf-8"))
    anchors = set(ANCHOR_PATTERN.findall(content))
    slug_counts: Counter[str] = Counter()

    for line in content.splitlines():
        match = HEADING_PATTERN.match(line)
        if not match:
            continue
        base_slug = markdown_slug(match.group(1))
        if not base_slug:
            continue
        duplicate_count = slug_counts[base_slug]
        slug_counts[base_slug] += 1
        anchors.add(base_slug if duplicate_count == 0 else f"{base_slug}-{duplicate_count}")

    return anchors


def resolve_link(source: Path, destination: str, root: Path) -> tuple[Path, str]:
    parsed = urlsplit(destination)
    link_path = unquote(parsed.path)
    fragment = unquote(parsed.fragment)

    if not link_path:
        target = source
    elif link_path.startswith("/"):
        target = root / link_path.lstrip("/")
    else:
        target = source.parent / link_path

    return target.resolve(), fragment


def check_markdown_links(markdown_files: list[Path], root: Path, errors: list[str]) -> None:
    anchor_cache: dict[Path, set[str]] = {}

    for markdown_file in markdown_files:
        content = strip_fenced_code(markdown_file.read_text(encoding="utf-8"))
        content = re.sub(r"`+[^`\n]*`+", "", content)
        for line_number, line in enumerate(content.splitlines(), start=1):
            for match in LINK_PATTERN.finditer(line):
                destination = match.group(1) or match.group(2)
                parsed = urlsplit(destination)
                if parsed.scheme or destination.startswith("//"):
                    continue

                target, fragment = resolve_link(markdown_file, destination, root)
                try:
                    target.relative_to(root)
                except ValueError:
                    errors.append(
                        f"{relative_path(markdown_file, root)}:{line_number}: "
                        f"relative link escapes repository: {destination}"
                    )
                    continue

                if not target.exists() or not target.is_file():
                    errors.append(
                        f"{relative_path(markdown_file, root)}:{line_number}: "
                        f"broken relative link: {destination}"
                    )
                    continue

                if fragment and target.suffix.lower() == ".md":
                    if target not in anchor_cache:
                        anchor_cache[target] = markdown_anchors(target)
                    if fragment not in anchor_cache[target]:
                        errors.append(
                            f"{relative_path(markdown_file, root)}:{line_number}: "
                            f"missing anchor '#{fragment}' in "
                            f"{relative_path(target, root)}"
                        )


def repository_markdown_files(root: Path) -> list[Path]:
    markdown_files = []
    for directory, subdirectories, filenames in os.walk(root):
        subdirectories[:] = sorted(
            subdirectory
            for subdirectory in subdirectories
            if subdirectory not in IGNORED_MARKDOWN_DIRECTORIES
        )
        markdown_files.extend(
            Path(directory) / filename
            for filename in filenames
            if filename.endswith(".md")
        )

    return sorted(markdown_files)


def available_skills_section(index_file: Path) -> tuple[str | None, int]:
    lines = index_file.read_text(encoding="utf-8").splitlines()
    start = None
    for index, line in enumerate(lines):
        if re.match(r"^## Available skills\b", line):
            start = index + 1
            break
    if start is None:
        return None, 0

    end = len(lines)
    for index in range(start, len(lines)):
        if re.match(r"^## ", lines[index]):
            end = index
            break
    return "\n".join(lines[start:end]), start + 1


def check_index_coverage(root: Path, skill_files: list[Path], errors: list[str]) -> None:
    index_file = root / ".github/skills/README.md"
    if not index_file.is_file():
        errors.append(f"{relative_path(index_file, root)}: missing skills index")
        return
    section, first_line = available_skills_section(index_file)
    if section is None:
        errors.append(f"{relative_path(index_file, root)}: missing 'Available skills' section")
        return

    counts: Counter[Path] = Counter()
    known_files = {path.resolve() for path in skill_files}
    skills_root = (root / ".github/skills").resolve()

    for line_number, line in enumerate(section.splitlines(), start=first_line):
        for match in LINK_PATTERN.finditer(line):
            destination = match.group(1) or match.group(2)
            target, _ = resolve_link(index_file, destination, root)
            if target.name != "SKILL.md":
                continue
            try:
                target.relative_to(skills_root)
            except ValueError:
                continue
            if target not in known_files:
                errors.append(
                    f"{relative_path(index_file, root)}:{line_number}: "
                    f"index references an unknown skill: {destination}"
                )
                continue
            counts[target] += 1

    for skill_file in skill_files:
        count = counts[skill_file.resolve()]
        if count != 1:
            skill_name = skill_file.parent.name
            errors.append(
                f"{relative_path(index_file, root)}: skill '{skill_name}' "
                f"must be listed exactly once (found {count})"
            )


def check_documentation(root: Path) -> list[str]:
    errors: list[str] = []
    skills_dir = root / ".github/skills"
    if not skills_dir.is_dir():
        errors.append(f"{relative_path(skills_dir, root)}: missing skills directory")
        return errors
    skill_files = []

    for directory in sorted(path for path in skills_dir.iterdir() if path.is_dir()):
        skill_file = directory / "SKILL.md"
        if not skill_file.is_file():
            errors.append(f"{relative_path(directory, root)}: missing SKILL.md")
            continue
        skill_files.append(skill_file)
        parse_front_matter(skill_file, root, errors)

    if not skill_files:
        errors.append(f"{relative_path(skills_dir, root)}: no skills found")
        return errors

    check_index_coverage(root, skill_files, errors)
    for relative_doc in CANONICAL_AI_DOCS:
        markdown_file = root / relative_doc
        if not markdown_file.is_file():
            errors.append(f"{relative_doc}: missing canonical AI documentation file")

    check_markdown_links(repository_markdown_files(root), root, errors)
    return errors


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="repository root (defaults to this script's repository)",
    )
    args = parser.parse_args()
    root = args.root.resolve()

    errors = check_documentation(root)
    if errors:
        color = os.environ.get("COLOR", "auto")
        use_color = not os.environ.get("NO_COLOR") and (
            color == "always"
            or (color == "auto" and sys.stderr.isatty() and os.environ.get("TERM") != "dumb")
        )
        error_label = "\033[31m[FAIL]\033[0m" if use_color else "[FAIL]"
        for error in errors:
            print(f"{error_label} {error}", file=sys.stderr)
        print(f"Documentation check failed with {len(errors)} issue(s).", file=sys.stderr)
        return 1

    print("Documentation check passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
