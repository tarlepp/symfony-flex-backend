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
    * [Project skills](#project-skills)
    * [Approvals and repair loop](#approvals-and-repair-loop)
    * [Using the agents](#using-the-agents)
    * [Compatibility and limitations](#compatibility-and-limitations)
    * [Validation](#validation)

## Agents and routing [ᐞ](#table-of-contents)

<a id="agents-and-routing"></a>

| Agent | Responsibility | Delegates to |
| --- | --- | --- |
| [feature-lead](../.github/agents/feature-lead.agent.md) | Only user-facing entry; requirements, approvals, final report. | feature-architect |
| [feature-architect](../.github/agents/feature-architect.agent.md) | Evidence-based design; approved implementation handoff. | implementer |
| [implementer](../.github/agents/implementer.agent.md) | Sole writer; backend implementation, tests, documentation. | verifier, initial mode only |
| [verifier](../.github/agents/verifier.agent.md) | Independent checks; defect handoff; report addressed to lead. | implementer, repair-only mode |

The delivery chain is `feature-lead → feature-architect → implementer →
verifier`. Results return through the existing callers to the feature lead;
reporting to the lead does not require invoking another lead agent.
Only the lead is user-selectable. Specialists return clarification needs through
the call chain instead of starting conversations with the user.

## Project skills [ᐞ](#table-of-contents)

<a id="project-skills"></a>

The [project skills](../.github/skills/README.md) are supporting references for
common tasks. Agents may consult relevant skills through native skill support
or read their `SKILL.md` files when useful. They do not need to preload skills
for every invocation, select an unrelated skill, or stop because no skill
matches or a supporting reference is unavailable.

Useful skill links can travel as ordinary handoff context. There are no
mandatory skill-selection fields, loading records, usage reports, or historical
compliance checks. Missing skill metadata or loading evidence alone must not
block design, implementation, repair, or a verification `PASS`.

Repository instructions, user approvals, scope boundaries, security rules,
and required checks remain binding. Skills cannot expand role permissions,
authorize unapproved work, or replace current repository evidence. Platform
requirements for native skill invocation still apply where provided by the
host; these profiles do not override them.

The verifier evaluates the approved behavior, repository conventions, and
required checks against the final changes. A real defect or missing required
check remains actionable even if a skill also describes it; merely not
consulting a skill is not a defect. If a supporting reference is unavailable,
use the repository's authoritative instructions, source, tests, and tooling.
Report a blocker only when an actual requirement or necessary evidence cannot
be resolved, not solely because a skill could not be loaded.

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

Profiles use `.github/agents/*.agent.md`, tool aliases, and `user-invocable`.
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
