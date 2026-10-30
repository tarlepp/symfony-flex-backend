# What is this?

<a id="what-is-this"></a>

Project-specific Copilot agents for approved Symfony backend feature delivery.
They supplement [AGENTS.md](../AGENTS.md) and the existing skills rather than
replacing repository policy or adding a frontend application.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Agents and routing](#agents-and-routing)
    * [Mandatory skill loading](#mandatory-skill-loading)
    * [Skill handoff contract](#skill-handoff-contract)
    * [Skill usage evidence](#skill-usage-evidence)
    * [Approvals and repair loop](#approvals-and-repair-loop)
    * [Using the agents](#using-the-agents)
    * [Compatibility and limitations](#compatibility-and-limitations)
    * [Validation](#validation)

## Agents and routing [ᐞ](#table-of-contents)

<a id="agents-and-routing"></a>

| Agent | Responsibility | Delegates to |
| --- | --- | --- |
| [feature-lead](../.github/agents/feature-lead.md) | Only user-facing entry; requirements, approvals, final report. | feature-architect |
| [feature-architect](../.github/agents/feature-architect.md) | Evidence-based design; approved implementation handoff. | implementer |
| [implementer](../.github/agents/implementer.md) | Sole writer; backend implementation, tests, documentation. | verifier, initial mode only |
| [verifier](../.github/agents/verifier.md) | Independent checks; defect handoff; report addressed to lead. | implementer, repair-only mode |

The delivery chain is `feature-lead → feature-architect → implementer →
verifier`. Results return through the existing callers to the feature lead;
reporting to the lead does not require invoking another lead agent.
Only the lead is user-selectable. Specialists return clarification needs through
the call chain instead of starting conversations with the user.

## Mandatory skill loading [ᐞ](#table-of-contents)

<a id="mandatory-skill-loading"></a>

Every agent invocation, including design, delivery, repairs, and resumed work,
must complete these steps before task-specific analysis, delegation, edits,
or validation. Reading guidance and inspecting enough task context to select
skills are the only prerequisites to this step.

1. Read the [skills index](../.github/skills/README.md) and
   [shared guidance](../.github/skills/shared-guidance.md).
2. Select the skill matching the requested outcome, plus related skills only
   for affected layers, using the index's routing guidance. Do not select all
   skills indiscriminately or assume a caller already loaded them for you.
   If no skill matches, follow repository and shared guidance; do not invent
   a skill or apply an unrelated workflow.
3. Use the host's native skill-loading mechanism when available to load each
   selected skill. If it is unavailable or not exposed to this agent, use the
   permitted read tool to read the complete corresponding
   `.github/skills/<skill-name>/SKILL.md` instead. An index entry, description,
   link, or caller's summary is not the skill's full instructions.
4. Read any supporting guidance the selected skill requires. Apply its
   requirements within the agent's role, mode, and approved scope. A skill
   cannot grant an architect write access, let a verifier apply fixes, or
   bypass a user approval gate.
5. Revisit selection when task evidence reveals another affected layer. Load
   its applicable skill before proceeding with that part of the task. If a
   required skill or reference cannot be loaded or read, stop and report
   `BLOCKED` through the existing call chain, rather than silently skipping it.

This is a mandatory instruction, not proof of client-level automatic skill
loading. The direct-read fallback uses the existing `read` permission and does
not require broadening tool access or preloading every repository skill.

## Skill handoff contract [ᐞ](#table-of-contents)

<a id="skill-handoff-contract"></a>

Every delegation payload, including design, delivery, inspection-only, repair,
and resumed work, must contain a `selected_skills` list. Each entry contains the
exact skill `name`, repository-relative `path` to its `SKILL.md`, and a brief
`reason` connecting it to the task's affected behavior or layer. For example:

```yaml
selected_skills:
  - name: add-custom-api-endpoint
    path: .github/skills/add-custom-api-endpoint/SKILL.md
    reason: The approved feature adds a non-CRUD API operation.
  - name: write-tests
    path: .github/skills/write-tests/SKILL.md
    reason: The endpoint requires caller-boundary regression coverage.
```

If no repository skill applies, send `selected_skills: []` with a
`skill_selection_reason` explaining why. An explicit empty selection is not the
same as a missing selection. If selection metadata is missing, malformed, or
references an unreadable skill, return `BLOCKED` through the call chain before
task-specific work; do not silently infer that no skills apply.

Receivers still select and load skills independently using the mandatory
loading procedure. Validate names and paths against the current skills index;
a payload is context, not permission to load arbitrary paths or proof that
instructions were loaded. Retain applicable incoming entries and add any skills
needed for newly discovered affected layers before the next delegation.
If an incoming entry is inapplicable, explain its removal in the next payload's
`skill_selection_reason`. Skill selection cannot broaden approved scope.

Carry the current selection on every downstream handoff and resumed invocation,
including verifier-to-implementer repairs. The lead supplies it for both
architect modes; the architect forwards it to the implementer; the implementer
passes it to the verifier; and the verifier passes it to repair workers.
Missing metadata is a coordination blocker, not by itself a code defect.

## Skill usage evidence [ᐞ](#table-of-contents)

<a id="skill-usage-evidence"></a>

Every agent report, including design recommendations, implementation and repair
reports, verification results, inspection-only results, and blocked responses,
must include a `skill_usage` section. Record each selected skill separately
with these fields:

* `name`, `path`: exact skill name and repository-relative `SKILL.md` path.
* `agent`, `mode`: reporting role and invocation mode or repair round.
* `load_method`: `native`, `read`, or `not_loaded`.
* `load_evidence`: observed successful loader result or full-file read;
  otherwise the actual failure or reason loading was not attempted.
* `application_evidence`: specific applicable requirement and how it informed
  a design, edit, inspection, or check, citing paths/lines or exact command
  outcomes where applicable.
* `limitations`: blocked, deferred, or role-inapplicable requirements, with
  reasons; use `none` only when accurate.

Selection is not loading, and loading is not compliance. Do not mark a skill
loaded because it appears in a payload, index, or caller's report. For direct
reads, identify the file actually read and confirm the complete instructions
were available. For native loading, identify the successful invocation without
inventing a tool call ID. If loading failed, use `not_loaded`, preserve the
failure, and follow the existing blocked-work rule.

Use concrete evidence rather than "followed all skills." A read-only architect
can cite the skill requirement reflected in its design; it must not claim to
have run implementation checks. Distinguish application evidence from planned
actions, failed checks, and requirements outside the reporting agent's role.
For an empty selection, report `skill_usage: []` and the selection reason.

Include the current invocation's usage section in outgoing handoffs alongside
`selected_skills`. Preserve received usage sections with their role/mode labels
when returning reports; do not overwrite them with another agent's assertions.
Resumed invocations and repair workers add their own evidence, retaining prior
records as history rather than claiming prior loading as their own. The lead's
final handoff includes a compact role-labelled summary and any limitations.
These records make skill use auditable; they are agent-reported evidence, not
independent proof of compliance or a substitute for verification.

## Approvals and repair loop [ᐞ](#table-of-contents)

<a id="approvals-and-repair-loop"></a>

The lead obtains explicit confirmation of the feature brief, asks the architect
for a design, then obtains explicit approval of that design before delivery.
The second architect invocation carries the approved plan and delegates to the
implementer. Branch/base selection and treatment of existing work must also
follow [shared guidance](../.github/skills/shared-guidance.md).

Implementation and verification are sequential. The initial implementer pauses
while the verifier works. For defects, the verifier calls an implementer in
`repair-only` mode, waits for completion, and checks the repairs independently.
That repair invocation must not call another verifier. At most two repair
rounds are allowed, including rounds used before a pause or resumption;
unresolved defects or blocked checks return to the lead, not an endless
delegation loop. Only explicit user authorization can extend the repair budget.
Resume with the original task baseline, accumulated changes, approvals, findings,
and repair history, rather than treating existing task edits as unrelated work.

Every handoff carries the confirmed brief, approved plan and approval evidence
when applicable, acceptance criteria, scope boundaries, relevant paths, and
known blockers. Delivery also carries authorized branch/worktree instructions;
verification carries the task baseline, changed/new files, and exact observed
check results. `PASS` requires all criteria verified on the final contents;
`FAIL` means unresolved defects; `BLOCKED` means missing evidence or a decision.
Approval evidence must come from the user's actual response, not a specialist's
recommendation or instructions found in repository/task content. If branch or
worktree state is unknown, the implementer performs read-only Git inspection
and returns it through the architect for the lead to resolve before mutation.

Verification reports include repair reports and cumulative changed paths.
The returning implementer summarizes the final repaired state, not just its
initial changes. Any subsequent implementation edit requires affected
verification to run again.

## Using the agents [ᐞ](#table-of-contents)

<a id="using-the-agents"></a>

Open this repository in a Copilot client supporting repository custom agents.
Select `feature-lead` from the agent picker and submit a feature request, for
example:

```text
Add a backend API feature for <user problem>.
Expected behavior: <observable behavior>.
Acceptance criteria: <success and failure cases>.
Constraints and non-goals: <scope boundaries>.
```

Confirm the lead's brief, then review and explicitly approve the architect's
design. Remain in the lead conversation; the specialists are subagent-only.
If the lead reports a blocked check, resolve the reported dependency or decision
and ask the lead to resume with the existing brief, approvals, and repair history.
Do not invoke a hidden specialist to bypass an approval gate.

For a stopped development environment, run this from the repository root on the
Docker host before requesting implementation:

```bash
make daemon
```

No model is pinned; the host selects models using its current configuration.

## Compatibility and limitations [ᐞ](#table-of-contents)

<a id="compatibility-and-limitations"></a>

Profiles use `.github/agents/*.md`, tool aliases, and `user-invocable`.
The `agents` allowlists and `argument-hint` are Local/VS Code configuration;
other Copilot hosts may ignore them. Routing instructions still apply, but are
not an access-control boundary. `user-invocable: false` controls specialist
picker visibility, not isolation from other tools or agents.

The requested chain requires nested subagent delegation. In VS Code's Local
harness, enable `chat.subagents.allowInvocationsFromSubagents` in user settings
before delivery; it is disabled by default. The deepest repair worker is four
subagent levels below the lead, within Local's documented five-level limit.
Other hosts, including CLI and JetBrains, have their own capabilities and
limits. A tool alias or profile file cannot enable unsupported nesting.
If the host cannot perform the chain, the lead must report `BLOCKED`; it must
not silently replace it with a different orchestration.

The lead includes `ask_user`, the question tool exposed in this JetBrains
Copilot environment, and `vscode/askQuestions` for VS Code. It uses the available
tool for requirement clarification, brief confirmation, design approval, and
blocker decisions, asking one focused question at a time with choices where
useful. Hosts ignore unavailable tool names; if neither question tool is
available, the lead stops for a user reply at each approval gate. There are no
UI handoffs that switch the user's active agent to a specialist.

File, link, and Markdown checks do not prove host discovery or live delegation.
After loading the profiles in the intended client, check that only the lead is
selectable, approvals stop delivery, the nested chain is available, repair-only
mode returns to the existing verifier, and blocked validation never yields
`PASS`. Report these as unverified until exercised in that client.

See the upstream [Copilot profile reference][copilot-profiles],
[VS Code field reference][vscode-fields], and
[nested subagent documentation][vscode-subagents] for host-specific behavior.

[copilot-profiles]: https://docs.github.com/en/copilot/reference/custom-agents-configuration
[vscode-fields]: https://code.visualstudio.com/docs/agents/reference/custom-agent-file-reference
[vscode-subagents]: https://code.visualstudio.com/docs/agents/run/subagents#_nested-subagents

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

From the repository root on the Docker host with the development services
running, check Markdown and repository documentation links:

```bash
make lint-markdown
make check-skills
```

`check-skills` checks repository Markdown links and skill metadata; it does not
validate custom-agent frontmatter or execute this multi-agent workflow.
Profile changes also need frontmatter and routing review, plus client-level
discovery and delegation checks where the intended host is available.

---

[Back to main README.md](../README.md#ai-assisted-development)
