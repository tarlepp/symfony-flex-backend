<a id="what-is-this"></a>

# What is this?

This directory contains different scripts that are used during development.

<a id="table-of-contents"></a>

## Table of Contents

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Resources](#resources)
    * [Project stats script](#project-stats-script)
    * [GitHub Actions update checker](#github-actions-update-checker)

<a id="resources"></a>

## Resources [ᐞ](#table-of-contents)

* [Project stats script](project-stats.sh)
  * This script is used to generate simple project stats. It will generate
    output with some basic stats about project.
* [GitHub Actions update checker](check-action-updates.sh)
  * Checks pinned GitHub Actions from `.github/workflows/*.yml`, reports
    discovery issues (unpinned refs/conflicting versions), and checks for
    available updates in the current major version line.

### Project stats script

File: `scripts/project-stats.sh`

```bash
make project-stats
```

If you are already inside the container, you can still run `bash scripts/project-stats.sh` directly.

### GitHub Actions update checker

File: `scripts/check-action-updates.sh`

```bash
make check-action-updates
```

If you are already inside the container, you can still run `bash scripts/check-action-updates.sh` directly.

Print current pins as markdown:

```bash
bash scripts/check-action-updates.sh --current-pins-md
```

Exit codes:
- `0` = no discovery issues and no updates found
- `1` = updates available
- `2` = discovery issues found (for example unpinned refs or conflicting versions)

---

[Back to main README.md](../README.md)
