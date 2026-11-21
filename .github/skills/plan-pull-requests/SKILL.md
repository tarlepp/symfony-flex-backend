---
name: plan-pull-requests
description: Recommend a direct pull request, independent pull requests, or a stack based on task dependencies, reviewability, and available branch evidence.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when choosing how to organize a user request into pull requests
and recommending their bases. It is a planning reference, not an authorization
to create branches, change Git history, commit, push, or open pull requests. For
authorized restacking or rebase conflicts, use [resolve-git-conflicts](../resolve-git-conflicts/SKILL.md).

Follow [shared guidance](../shared-guidance.md) for branch selection, pre-existing
work, approvals, and handoff. The default base is `master`; do not infer a parent
from the checked-out branch.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow](#workflow)
    * [Choosing a PR structure](#choosing-a-pr-structure)
    * [Recommendation](#recommendation)
    * [Validation](#validation)

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Clarify the requested outcome, acceptance criteria, constraints, and any
   user-specified base or delivery order. Identify cohesive changes and whether
   each can be reviewed, tested, and merged independently.
2. Inspect relevant implementation, tests, and documentation for actual
   dependencies. Where branch context matters, inspect Git state and available
   branch history read-only; distinguish verified facts from assumptions and do
   not assume local or remote references are current. Inspect PR information when
   available and relevant. Report unavailable evidence.
3. Use the criteria below to compare feasible structures, including reviewability,
   validation, merge order, deployment compatibility, and restacking cost. If the
   necessary dependency or base is uncertain, explain the uncertainty and ask
   the user rather than guessing.
4. Present the recommendation and obtain the decisions required by
   [shared guidance](../shared-guidance.md#preparing-a-change) before any Git mutation.
   For a stack, confirm the intended parent with the user. Respect existing work
   and any stricter agent approval or handoff requirements.

## Choosing a PR structure [ᐞ](#table-of-contents)

<a id="choosing-a-pr-structure"></a>

Direct and stacked PRs are both valid: choose the structure that fits the change,
not a blanket preference for or against stacks. Plan work in meaningful, reviewable
steps; a single PR can also be implemented and validated step by step without
splitting its merge boundary.

A direct PR or any PR in a stack may contain multiple commits when useful for
organizing and reviewing its changes. A commit is not automatically a separate
PR, and neither a single commit nor multiple commits are required. Commit
organization does not change the PR's review or merge boundary, and the final
history depends on the chosen merge method. Follow the
[commit and push approval rules](../shared-guidance.md#preparing-a-change); planning
commits does not authorize creating, amending, or pushing them.

Use one direct PR to `master` for a cohesive change, including code, tests, and
documentation that must land together. Use separate direct PRs when changes can
be reviewed, tested, and merged in either order without relying on each other.
Sharing files, a theme, or a release date alone does not establish a dependency.

Recommend a stack when all of the following hold:

* A child needs specific unmerged parent changes to build, test, or implement its
  intended behavior; identify those changes and why `master` is insufficient.
* Each PR has a meaningful review and validation boundary. Each parent can be
  merged and deployed safely before its child, including any compatibility and
  migration requirements, or is safe with the project's established release
  controls. A prerequisite need not deliver a complete user-facing feature.
* Earlier review or development of the child is worth the extra dependency,
  potential restacks, repeated checks, and coordination of parent-first merges.

Do not recommend a stack just because a change is large, touches many layers,
shares files with another task, or the current branch is not `master`. Keep a
change together if splitting would leave an unsafe or broken intermediate state.
If the parent will merge soon and early child review has little value, consider
waiting and opening the child directly against `master` instead. Do not invent
dependencies or silently override a user-specified base: discuss the tradeoffs
and follow the branch confirmation rules in shared guidance.

When a stack is justified, group related changes into cohesive, reviewable PRs
instead of creating a separate stack entry for every small edit or single-line
change. Include the tests and documentation needed for each PR's behavior in
that same PR. Combine a trivial prerequisite with the related parent or child
when the resulting boundary remains safe and understandable; do not add
unrelated work or split changes that must land together just to increase PR
size. If a small standalone PR has a specific review or merge-order benefit,
explain that exception rather than applying a fixed line-count threshold. Plan
each step with its scope, prerequisites, validation, and safe merge point;
reassess later steps if earlier decisions or evidence change. A plan for a stack
does not authorize executing every step, publishing, or rewriting history.

## Recommendation [ᐞ](#table-of-contents)

<a id="recommendation"></a>

For each proposed PR, state its purpose, intended base and dependency, expected
grouped changes, review and validation boundary, and proposed merge order. For a
stack, explain the parent-child relationship and intended retargeting after the
parent merges. Flag proposed branches that do not yet exist separately from
verified branches. Summarize why the recommended structure is preferable, a
reasonable alternative, and any open decisions or missing evidence.

Examples:

* A self-contained fix and its tests form one direct PR to `master`. Implement
  and validate it in steps while keeping its required changes together; use
  multiple commits in that PR if useful.
* Two unrelated fixes that can merge separately form two independent PRs.
* A backwards-compatible shared abstraction can be a parent, followed by a
  feature that needs it, when both are separately reviewable and early review is
  worthwhile. Group the abstraction's small supporting edits and tests with that
  parent rather than proposing a separate one-line PR for each edit.
* A schema change and application code that must deploy atomically belong in one
  PR, not a stack with an unsafe intermediate state.
* Two unrelated fixes touching the same file remain independent if neither needs
  the other; possible conflicts alone do not justify a stack.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

Check the proposed dependency and base for every PR against available evidence.
Confirm that each proposed merge point is coherent and that the validation
boundary covers its changes. State assumptions and checks that could not be
performed; a recommendation is not proof that branches, CI, or deployment are
ready. Planning alone does not run the implementation or its tests.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
