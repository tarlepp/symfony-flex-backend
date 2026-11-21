#!/usr/bin/env python3
"""Compare digest-only Dockerfile updates using registry index metadata."""

#
# Usage:
#   From the repository root on the Docker host, after updating image pins:
#     python3 scripts/docker/docker-image-digest-summary.py
#
# Requirements:
#   Python 3, Git, and Docker Buildx with access to the relevant registries.
#
# Details:
#   Reads unstaged Dockerfile diffs and compares old and new index digests only
#   for unchanged image tags. Multiple digests for a tag require manual review.
#   Writes a Markdown report to stdout; unavailable or invalid index data is
#   reported without guessing why an image was rebuilt. No images are pulled or
#   run. A failed Git diff exits nonzero; unavailable indexes do not.
#
# Callers:
#   GitHub Actions:
#     - `.github/workflows/docker-image-updates.yml` captures the report for the
#       image-update pull request and workflow summary.

from collections import defaultdict
import html
import json
import re
import subprocess
import sys


PIN = re.compile(
    r"(?<![A-Za-z0-9._/-])"
    r"([A-Za-z0-9._/-]+(?::[0-9]+)?/[A-Za-z0-9._/-]+|[A-Za-z0-9._-]+)"
    r":([A-Za-z0-9._-]+)@(sha256:[a-f0-9]{64})"
)
METADATA = (
    "org.opencontainers.image.created",
    "org.opencontainers.image.version",
    "org.opencontainers.image.revision",
    "org.opencontainers.image.source",
    "org.opencontainers.image.base.name",
    "org.opencontainers.image.base.digest",
)


def changed_pins(diff):
    """Group changed digests by unchanged tag, retaining ambiguity for reporting."""
    removed, added = defaultdict(set), defaultdict(set)
    for line in diff.splitlines():
        if line.startswith(("---", "+++")) or not line.startswith(("-", "+")):
            continue
        target = removed if line[0] == "-" else added
        for match in PIN.finditer(line[1:]):
            target[f"{match[1]}:{match[2]}"].add(match[3])
    return [
        (tag, sorted(removed[tag]), sorted(added[tag]))
        for tag in sorted(removed.keys() & added.keys())
        if removed[tag] != added[tag]
    ]


def inspect(reference):
    """Fetch and validate an index, returning None when comparison is unavailable."""
    try:
        result = subprocess.run(
            ["docker", "buildx", "imagetools", "inspect", "--raw", reference],
            capture_output=True, text=True, timeout=60, check=True,
        )
        index = json.loads(result.stdout)
        if not isinstance(index, dict) or not isinstance(index.get("manifests"), list) or not index["manifests"]:
            raise ValueError("not a multi-platform image index")
        for descriptor in index["manifests"]:
            if (not isinstance(descriptor, dict)
                    or not re.fullmatch(r"sha256:[a-f0-9]{64}", str(descriptor.get("digest")))
                    or not isinstance(descriptor.get("platform", {}), dict)):
                raise ValueError("invalid index descriptor")
        return index
    except (OSError, subprocess.SubprocessError, ValueError, UnicodeError):
        return None


def platform(descriptor):
    """Return a runnable platform key, or None for unknown or invalid platforms."""
    value = descriptor.get("platform", {})
    if (not isinstance(value.get("os"), str) or value["os"] in ("", "unknown")
            or not isinstance(value.get("architecture"), str)
            or value["architecture"] in ("", "unknown")):
        return None
    components = [value[key] for key in ("os", "architecture", "variant") if value.get(key)]
    if any(not isinstance(item, str) or not re.fullmatch(r"[A-Za-z0-9._-]+", item) for item in components):
        return None
    return "/".join(components)


def render(value):
    """Serialize and bound registry-controlled values before escaping PR markup."""
    if value is None:
        return "(not supplied)"
    text = json.dumps(value, ensure_ascii=True, sort_keys=True)
    return html.escape(text[:500] + ("..." if len(text) > 500 else ""))


def report(tag, old_digest, new_digest):
    """Print an overview and detailed index comparison, or an unavailable notice."""
    print(f"\n### <code>{html.escape(tag)}</code>\n")
    old, new = inspect(f"{tag}@{old_digest}"), inspect(f"{tag}@{new_digest}")
    if old is None or new is None:
        missing = "old and new" if old is None and new is None else "old" if old is None else "new"
        print(f"Comparison unavailable: the {missing} index could not be retrieved or parsed.")
        print(f"Old index: <code>{html.escape(old_digest)}</code>  ")
        print(f"New index: <code>{html.escape(new_digest)}</code>")
        return

    def descriptors(index):
        result = defaultdict(list)
        for entry in index["manifests"]:
            key = platform(entry)
            if key:
                result[key].append(entry)
        return result

    before, after = descriptors(old), descriptors(new)
    added = sorted(after.keys() - before.keys())
    removed = sorted(before.keys() - after.keys())
    common = before.keys() & after.keys()
    # Duplicate platform descriptors cannot be paired without guessing.
    comparable = [name for name in common if len(before[name]) == len(after[name]) == 1]
    changed = [name for name in comparable if before[name][0]["digest"] != after[name][0]["digest"]]
    ambiguous = sorted(common - set(comparable))
    print(f"* Runnable platform manifests: {len(changed)} changed, "
          f"{len(comparable) - len(changed)} unchanged, {len(added)} added, {len(removed)} removed.")
    if ambiguous:
        print(f"* {len(ambiguous)} platform(s) have multiple descriptors and require manual comparison.")
    for label, key in (
        ("Base image", "org.opencontainers.image.base.name"),
        ("Source revision", "org.opencontainers.image.revision"),
    ):
        transitions = defaultdict(int)
        for name in comparable:
            first = before[name][0].get("annotations") or {}
            second = after[name][0].get("annotations") or {}
            if not isinstance(first, dict) or not isinstance(second, dict):
                continue
            previous, current = first.get(key), second.get(key)
            if previous != current:
                transitions[(render(previous), render(current))] += 1
        for (previous, current), count in sorted(transitions.items()):
            print(f"* {label} metadata ({count} platform(s)): "
                  f"<code>{previous}</code> → <code>{current}</code>.")
    if old != new and not changed and not added and not removed:
        print("* The index changed without a confirmed runnable manifest change; see details.")

    print("\n<details>\n<summary>Show index and platform details</summary>\n")
    print(f"Old index: <code>{html.escape(old_digest)}</code>  ")
    print(f"New index: <code>{html.escape(new_digest)}</code>\n")
    if not before and not after:
        print("* No runnable platform descriptors were available for comparison.")
    for name in sorted(before.keys() | after.keys()):
        previous, current = before.get(name, []), after.get(name, [])
        safe_name = html.escape(name)
        if not previous or not current:
            print(f"* <code>{safe_name}</code>: {'added' if current else 'removed'}.")
            continue
        if len(previous) != 1 or len(current) != 1:
            print(f"* <code>{safe_name}</code>: multiple descriptors; manual comparison required.")
            continue
        first, second = previous[0], current[0]
        status = "manifest changed" if first.get("digest") != second.get("digest") else "manifest unchanged"
        print(f"* <code>{safe_name}</code>: {status}.")
        old_annotations = first.get("annotations") or {}
        new_annotations = second.get("annotations") or {}
        if not isinstance(old_annotations, dict) or not isinstance(new_annotations, dict):
            print("  * Descriptor annotations are malformed; metadata comparison unavailable.")
            continue
        for key in METADATA:
            if old_annotations.get(key) != new_annotations.get(key):
                print(f"  * <code>{html.escape(key)}</code>: <code>{render(old_annotations.get(key))}</code> "
                      f"→ <code>{render(new_annotations.get(key))}</code>")
        if first != second and first.get("digest") == second.get("digest"):
            print("  * Platform descriptor metadata changed.")

    other_old = [entry for entry in old["manifests"] if not platform(entry)]
    other_new = [entry for entry in new["manifests"] if not platform(entry)]
    if other_old != other_new:
        print("* Non-runnable descriptors (such as attestations) changed.")
    if {k: v for k, v in old.items() if k != "manifests"} != {
        k: v for k, v in new.items() if k != "manifests"
    }:
        print("* Index-level metadata changed.")
    if old != new and before == after and other_old == other_new:
        print("* Index ordering or metadata changed; runnable platform descriptors are unchanged.")
    if old == new:
        print("* Retrieved index contents are identical despite different index digests.")
    print("\n</details>")


def main():
    """Summarize unstaged pin changes, inspecting registries only when needed."""
    result = subprocess.run(
        ["git", "diff", "--no-ext-diff", "--unified=0", "--", "Dockerfile", "docker/*/Dockerfile*"],
        text=True, capture_output=True, check=True,
    )
    pins = changed_pins(result.stdout)
    print("## Digest-only image comparisons\n")
    print("For digest-only updates, the tag is unchanged but its upstream index digest moved. "
          "Index metadata can indicate what changed, not the publisher's reason or exact package changes.")
    if not pins:
        print("\nNo digest-only image updates.")
    for tag, old, new in pins:
        if len(old) != 1 or len(new) != 1:
            print(f"\n### <code>{html.escape(tag)}</code>\n\n"
                  "Multiple old or new digests were found; manual comparison is required.")
        else:
            report(tag, old[0], new[0])


if __name__ == "__main__":
    try:
        main()
    except subprocess.CalledProcessError:
        print("Could not read the Dockerfile diff for the digest comparison.", file=sys.stderr)
        sys.exit(1)
