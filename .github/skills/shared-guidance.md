# What is this?

<a id="what-is-this"></a>

Shared workflow guidance for repository skills. Individual skills should link
here for common preparation, validation, and handoff rules, and keep only their
task-specific workflow and checks.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Using repository instructions](#using-repository-instructions)
    * [Preparing a change](#preparing-a-change)
    * [Defining the contract](#defining-the-contract)
    * [Running and validating changes](#running-and-validating-changes)
    * [Updating documentation](#updating-documentation)
    * [Handoff](#handoff)

## Using repository instructions [ᐞ](#table-of-contents)

<a id="using-repository-instructions"></a>

Before applying a skill, read the
[repository instructions](../copilot-instructions.md) and
[AI rules](../../doc/AI_RULES.md), following the canonical inventory and
reading order in the [README](../../README.md#ai-assisted-development). Follow
those repository-wide rules alongside the task-specific workflow in the skill.

## Preparing a change [ᐞ](#table-of-contents)

<a id="preparing-a-change"></a>

* Inspect staged, unstaged, and untracked changes before editing. Preserve
  unrelated work; ask if it conflicts with the task. Tool-specific clean-tree
  requirements and generated-file handling belong in the relevant skill.
* Before making task changes, propose the pull request title and create a new
  task branch following the
  [repository naming conventions](../../doc/DEVELOPMENT.md#branch-names-and-pull-request-titles).
  The branch and title use the same type, scope, and change summary in their
  respective formats. Do not stage pre-existing work.
* When a change touches only documentation (Markdown files and docs-only
  guidance text, with no code, workflow, configuration, or script changes),
  the branch must be named `chore(docs)/<short-kebab-case-description>` and
  the pull request title must be `Chore(docs) - <concise change summary>`.
  When that documentation is only AI guidance (`AGENTS.md`, `CLAUDE.md`,
  `.github/copilot-instructions.md`, `.github/skills/`, `doc/AI_RULES.md`),
  use `chore(ai)/<short-kebab-case-description>` and
  `Chore(ai) - <concise change summary>` instead. Do not use `docs/...` or
  another type or scope for such changes. Changes that mix documentation
  with other areas, or AI guidance with other documentation, use the scope
  of the broader area (`docs` for the latter) or the functional change.
* Decide the base branch before creating the task branch and state it in the
  proposal. The default base is `master`. If the user names another base
  branch, or the current branch is not `master` and has unmerged work for
  another task, treat the change as a stacked PR: confirm the intended parent
  branch with the user instead of guessing, and report it at handoff with the
  PR base to use (the parent branch, retargeted to `master` once the parent
  merges). Never base a task on a branch just because it is checked out.
* For a sequence of stacked PRs, treat each PR as a separate task: create a
  fresh branch for each step, based on its intended parent branch. Do not
  continue a later step on a branch already used for an earlier PR. When the
  parent is rebased or amended, rebase the children with
  `git rebase --onto <new-parent> <old-parent-tip>`.
* Apply the new-branch rule to separate follow-up enhancements too, even when
  they concern the same files or topic as the previous task. Reserve branch
  reuse for corrections to the same PR or an explicit user request to reuse
  it for the new task. Prior approval to reuse a branch does not carry over.
  When asking about existing work, propose a fresh branch for a new task
  rather than recommending continued work on an already published PR branch.
* If the worktree already contains uncommitted changes, or the current branch
  already has commits for this task that are present on its remote, stop before
  switching branches or changing files and ask the user how to continue. State
  what you found and offer safe choices appropriate to the situation; do not
  stash, commit, reset, or move existing work without approval.
* Before framework-dependent work, check `composer.json` for supported PHP
  and package constraints, `composer.lock` for locked versions, and
  `symfony.lock` for Flex recipe metadata. Do not treat recipe versions as
  installed package versions or assume an uninstalled component is available.
  If a command's behavior depends on the installed tooling, inspect its help
  or installed implementation; the vendor directory can differ from the lock.
* Read the affected implementation, configuration, callers, and nearby tests.
  Trace the actual behavior boundary before editing; use existing code and
  supported package contracts over remembered defaults or outdated docs.
  Individual skills identify the concrete files and boundaries to inspect.
* Reuse existing helpers and extend the appropriate implementation rather than
  adding a parallel pattern. Keep changes within the requested scope.

## Defining the contract [ᐞ](#table-of-contents)

<a id="defining-the-contract"></a>

Establish the inputs, outputs, defaults, failure behavior, and compatibility
requirements affected by the change. Ask before resolving unspecified choices
that alter API, CLI, schema, security, or deployment behavior. Preserve existing
contracts and security guards unless the requested change explicitly requires
an approved adjustment. Each skill lists its domain-specific decisions and
safeguards; do not replace them with generic assumptions.

## Running and validating changes [ᐞ](#table-of-contents)

<a id="running-and-validating-changes"></a>

* Run project commands from the repository root inside the running PHP
  development container or IDE Dev Container. Host Make targets forward
  application tooling into the container, including in CI. From the host, use
  `make bash` or `make fish` for direct commands; if containers are stopped,
  start them with `make start`. Non-interactive commands can use
  `docker compose exec -T php <command>`. Never silently run project tooling
  on the host when Docker is unavailable.
  Docker inspection, Compose validation, and image builds run from the host,
  as described by the relevant skill.
* Add or update tests for behavior changes. Use
  [write-tests](write-tests/SKILL.md) as the single source for test
  selection, fixtures, test doubles, runner commands, and test-writing
  practice whenever adding or changing tests. Other skills should state only
  task-specific behaviors, references, and boundaries that tests must cover.
* Run the smallest relevant tests first. For wiring or registration changes,
  verify behavior through the real caller or configured component: direct
  method calls alone do not prove that wiring works. Preserve domain-specific
  assertions and negative cases specified by the skill.
* For Symfony service wiring or package configuration changes, run
  `bin/console lint:container` and check the affected environments. Container
  linting is not a substitute for caller-boundary tests or runtime env
  resolution. Use `make lint-yaml` when configuration YAML changes.
* Run relevant quality checks for the changed code. For PHP changes, these
  include `make ecs`, `make phplint`, `make phpstan`, and `make psalm`. Run
  `make lint-markdown` when Markdown files change.
  For documentation-only changes, including AI instructions and skill text,
  Markdown lint and documentation checks are sufficient through handoff and
  commit; do not run PHPUnit or PHP static analysis merely because a skill
  documents test commands. Select other checks using
  [the validation selection guide](../../doc/TESTING.md#selecting-validation).
* Run checks against the final file contents, after the last edit. For
  Markdown changes, rerun `make lint-markdown` and
  `python3 scripts/check-skills.py`; an earlier passing run does not validate
  later edits. Inspect staged and unstaged diffs before handoff or commit,
  including first-line headings and the 80-character Markdown line limit.
* Before a commit, follow the change-scoped validation requirements in
  [`AGENTS.md`](../../AGENTS.md#testing-and-validation). The broader application
  suite is not mandatory for changes that do not affect application behavior.
* If a required check cannot run, identify the blocker in the handoff.

## Updating documentation [ᐞ](#table-of-contents)

<a id="updating-documentation"></a>

Update existing documentation when behavior, architecture, configuration, or
contributor/deployment commands change. Keep public API metadata and CLI help
aligned with the implemented contract where affected. Before editing:

* Identify the intended audience and the canonical document for the information.
  Prefer updating that document over creating duplicate guidance, and update
  repository indexes when their inventories are affected.
* Verify commands against the Makefile, scripts, or current tool help, and
  verify referenced paths and examples against files that exist in the
  repository.
* Use relative links to existing Markdown files and valid heading or explicit
  anchors. Run `python3 scripts/check-skills.py` from the repository root
  inside the PHP development container (`make bash`, `make fish`, or an IDE
  Dev Container terminal), never directly on the host, to check local links
  and anchors across repository Markdown files, as well as
  `make lint-markdown` for Markdown style.
* Follow [the copy-paste instruction requirements](../../AGENTS.md#clarification-and-documentation)
  for every use case, including troubleshooting and CI reproduction. Label the
  execution environment, show setup separately from subsequent commands, and
  keep fenced command blocks directly runnable without shell prompt prefixes.

Individual skills identify domain-specific documents or metadata; avoid
creating parallel guidance or duplicating fast-changing versions from
manifests.

## Handoff [ᐞ](#table-of-contents)

<a id="handoff"></a>

State what changed, which files were affected, and each validation command with
its result or reason it was skipped or blocked. Distinguish checks of skill
documentation from execution of the workflow it describes; writing a recipe
skill, for example, does not imply that recipes were updated.

For database changes, state whether a migration was generated, reviewed, or
applied to a non-production database.

After each AI task that changes files, end the response with proposed commit
messages using `Type(scope): short description`. These are proposals only:
do not commit unless explicitly asked, and never insert proposed commit
messages into source or documentation files. Place this section after any
copyable Markdown version and follow these rules:

* Provide an individual proposal for each logical change and explicitly list
  the changed file paths it covers.
* Combine related files changed for the same task into one logical proposal;
  do not split a cohesive change into separate proposals merely by file.
* After the individual proposals, provide one combined commit message covering
  all current uncommitted changes for the task, including earlier steps.
  Exclude unrelated pre-existing changes and update proposals as the changeset
  grows.
* Put every complete message in its own fenced code block for copying and
  pasting. Keep the final combined message last, with no content following it.

---

[Back to previous](README.md)

[Back to main README.md](../../README.md)
