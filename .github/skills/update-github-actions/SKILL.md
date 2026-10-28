---
name: update-github-actions
description: Inspect and update SHA-pinned GitHub Actions in workflows and local composite actions using the repository's update checker, upstream tag verification, and CI validation.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when reviewing or updating external GitHub Action versions and
commit pins. It covers workflow references and dependencies inside local
composite actions, not arbitrary CI redesign or Composer and Docker upgrades.
It also covers release tag/commit declarations for standalone workflow tools
in `scripts/lint-workflows.sh`; preserve their separate binary checksum pins.
For job, trigger, or shared bootstrap behavior changes, use
[update-github-workflows](../update-github-workflows/SKILL.md).

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md). Read the
[Actions update guide](../../../doc/ACTIONS_UPDATE.md) for command details.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Inspecting current pins](#inspecting-current-pins)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Inspecting current pins [ᐞ](#table-of-contents)

<a id="inspecting-current-pins"></a>

Use the existing `scripts/check-action-updates.sh`; it reports findings but
does not edit files. From the host, `make check-action-updates` delegates to
the PHP development container. Inside that container, run:

```bash
bash scripts/check-action-updates.sh --current-pins-md
bash scripts/check-action-updates.sh
bash scripts/check-action-updates.sh --tools-only
```

The inventory mode needs no upstream lookup. The check mode needs network
access to GitHub through `git ls-remote` and uses `timeout`. It scans
`.github/workflows/*.yml` and `.github/actions/*/action.yml`, groups action
references by path and version comment, and reports source locations.
It also inventories workflow-tool `tool-pin` declarations. Tool-only mode
verifies exact tag/commit parity, excludes prerelease tags, and fails upstream
lookup errors; the Action-specific limitations below do not describe that
stricter mode. Verify release-asset checksums separately for every platform.

Read warnings and individual lookup results, not only the exit code:

* `0` means no updates or discovery warnings were recorded, not that every
  remote lookup or current tag/SHA pair was verified.
* `1` means updates were found; `2` means discovery warnings, which take
  precedence over available updates. Unsupported arguments return `64`.
* Candidate selection sorts version-like tags across major versions and may
  suggest a major upgrade. It does not enforce compatibility or reliably
  exclude prerelease tags; treat the result as a candidate, not approval.
* Discovery is line-based, not a complete YAML parser. Local action references
  and reusable-workflow references are skipped. Alternate `.yaml` names,
  deeper action directories, unusual quoting, and other reference forms need
  manual inspection when present.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Confirm the requested actions and acceptable version range. Run inventory
   and check modes, inspect the script's current behavior, and search affected
   workflows and composite actions for every reference in scope. The checker
   can suggest a version from a later major line; do not treat that suggestion
   as approval to upgrade.
2. Review upstream release notes, migration instructions, and the candidate's
   `action.yml` or `action.yaml`. Verify input/output changes, Node or container
   runtime requirements, minimum runner versions, and self-hosted runner
   compatibility where relevant. A major-version upgrade requires explicit
   approval and compatibility review; ask before crossing an unspecified major
   version or changing workflow behavior or permissions.
3. Verify the full commit SHA against the intended upstream repository and
   exact release tag. For a subpath action such as `github/codeql-action/init`,
   resolve the tag from `github/codeql-action`, retaining `/init` in `uses`.
   Use `git ls-remote` with both the exact tag and its peeled ref:

   ```bash
   git ls-remote --tags https://github.com/OWNER/REPO.git \
     'refs/tags/TAG' 'refs/tags/TAG^{}'
   ```

   For an annotated tag, use the peeled commit, not the tag-object SHA. If
   needed, verify its object type through the upstream Git API. A release's
   `target_commitish` can be a branch name and is not sufficient proof of the
   release commit. Do not trust a comment or checker suggestion alone.
4. Update the immutable full 40-character commit SHA and version comment
   together at every in-scope occurrence, including composite actions such as
   `.github/actions/php-bootstrap/action.yml`. Preserve action subpaths,
   YAML anchors/aliases, inputs, conditions, permissions, and job structure
   unless compatibility requires a reviewed change. If different major lines
   are intentionally used, preserve that distinction rather than replacing
   every occurrence indiscriminately.
5. Do not replace pins with floating tags, silently grant token permissions,
   expose secrets, or change trigger trust boundaries to make an update pass.
   Treat local `./...` actions, reusable workflows, and Docker references
   according to their own contracts instead of applying action-tag rules
   blindly. Keep unrelated dependency updates outside the patch.
6. Report old and new tags/SHAs, affected workflows and composite actions,
   compatibility adjustments, checker warnings or unresolved lookups, and
   validation evidence. Do not commit, push, dispatch workflows, or open a PR
   unless requested. Use the repository PR template if a PR is requested.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Rerun inventory and check modes. Review remaining warnings and updates:
  unrelated candidates or unapproved major upgrades may remain legitimately.
  Verify tag/SHA parity separately, including same-version tag moves; the
  checker compares version labels and does not establish current pin parity.
* Search all in-scope references for stale pins/comments and inspect the diff
  for unintended workflow or permission changes. Confirm each new action path
  exists at the pinned commit.
* `make lint-yaml` covers `config/`, not workflow or action YAML. Use an
  existing workflow validator if available and applicable; do not claim that
  Symfony YAML lint validates GitHub Actions semantics. Do not install a new
  validator merely for a pin-only change.
* When an authorized PR or push triggers CI, inspect relevant job results and
  logs. A scheduled-only, protected-branch, or otherwise skipped workflow is
  not validated by an unrelated green job. State which workflows were not
  exercised; local inventory and pin verification do not prove CI runtime
  compatibility.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
