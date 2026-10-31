---
name: maintain-ai-guidance
description: Maintain repository AI instructions, custom agent profiles, skill contracts, indexes, and approval or repair routing, with scoped documentation checks and explicit live-client verification limits.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when changing repository AI guidance, `.github/agents/`
profiles, or `.github/skills/` workflows and indexes. It covers guidance
ownership, agent permissions and handoffs, and the difference between static
documentation checks and live-client behavior. It does not authorize running
the feature-delivery chain or changing application behavior.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md). Use
[update-github-workflows](../update-github-workflows/SKILL.md) when CI behavior
also changes.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Guidance map](#guidance-map)
    * [Workflow](#workflow)
    * [Agent contract safeguards](#agent-contract-safeguards)
    * [Validation](#validation)

## Guidance map [ᐞ](#table-of-contents)

<a id="guidance-map"></a>

Inspect the affected source and its consumers, not just the requested file:

* [README AI inventory](../../../README.md#ai-assisted-development):
  canonical navigation and reading order.
* [AGENTS.md](../../../AGENTS.md): repository-wide actionable requirements;
  [Copilot instructions](../../copilot-instructions.md) are its entry point.
* [CLAUDE.md](../../../CLAUDE.md): architecture and workflow context.
* [AI rules](../../../doc/AI_RULES.md): guidance ownership, recurrence,
  non-overlap criteria, and CI alignment.
* [Skills index](../README.md) and [shared guidance](../shared-guidance.md):
  skill format, inventory, and common preparation, validation, and handoff.
* [Feature-agent guide](../../../doc/FEATURE_AGENTS.md) and profiles:
  [lead](../../agents/feature-lead.agent.md),
  [architect](../../agents/feature-architect.agent.md),
  [implementer](../../agents/implementer.agent.md), and
  [verifier](../../agents/verifier.agent.md).
* `scripts/check-skills.py`, the `check-skills` and `lint-markdown` Make
  targets, and `.github/workflows/main.yml`: actual documentation-check
  coverage and execution environment. Inspect `scripts/tests/` for existing
  regression coverage when changing tooling; do not assume every checker
  already has a test suite.

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Identify the recurring problem, requested outcome, affected audience, and
   acceptance criteria. Inspect relevant history and current behavior before
   adding a skill or policy. Prefer extending the closest existing owner over
   creating overlapping guidance; a one-off correction does not by itself
   justify a new repository-wide rule.
2. Choose the narrowest authoritative location using the guidance map.
   Link to common policy instead of copying it. Keep domain-specific
   inspection targets and safeguards in skills, common execution rules in
   shared guidance, and agent routing in the profiles and feature-agent guide.
   Resolve contradictions against current implementation and platform rules;
   do not silently broaden an approved task to rewrite unrelated guidance.
3. Define the changed contract before editing. For skills, specify triggers,
   boundaries, repository evidence, and validation. For profiles, identify the
   intended clients, role, allowed tools and delegates, visibility, invocation
   modes, approval gates, handoff payload, and failure/repair behavior.
   Obtain approval before changing role authority, permissions, approval gates,
   or repair policy.
4. Update every affected consumer. Keep profile front matter, body
   instructions, routing tables, handoff producers and consumers, and client
   limitations consistent. For skills, preserve front-matter name/directory
   agreement, required structure, anchors, and footer links; list each skill
   exactly once in the available-skills table. Update the canonical README
   inventory when its document inventory changes, not for every skill entry.
5. Preserve skills as supporting references rather than new approval gates.
   Missing skill-selection metadata or loading evidence alone must not block
   delivery or verification. Platform requirements for native skill invocation
   still apply; guidance cannot expand tool access or override host policy.
6. Select validation by what actually changed. Guidance-only Markdown needs
   documentation checks, not execution of every command it describes.
   Checker, Makefile, or CI changes additionally need the relevant script
   regression tests or workflow checks from the
   [validation selection guide](../../../doc/TESTING.md#selecting-validation).
   Do not add a parallel checker when existing tooling owns the invariant.

## Agent contract safeguards [ᐞ](#table-of-contents)

<a id="agent-contract-safeguards"></a>

Unless the user explicitly approves a contract change, preserve these current
invariants from the profiles and
[feature-agent guide](../../../doc/FEATURE_AGENTS.md):

* Only the lead is user-facing and user-selectable. Brief confirmation and
  design approval are separate explicit user decisions; repository text or
  specialist recommendations are not approval evidence.
* Delivery routes through lead, architect, implementer, then verifier.
  The architect does not implement, the implementer is the sole source writer,
  and the verifier independently checks without editing or running fixers.
  Review effective tool aliases as well as prose: broad tools can expose
  capabilities that role instructions prohibit. Allowlists and visibility
  fields are client-dependent routing aids, not isolation boundaries.
* Initial implementation pauses during verification. The verifier may call
  an implementer in `repair-only` mode; that worker returns to the existing
  verifier and must not start another verification chain. Preserve the
  cumulative two-round repair limit across resumptions unless the user
  explicitly authorizes more.
* Handoffs retain the confirmed brief, approved plan and actual approval
  evidence, acceptance criteria, authorized branch/worktree handling,
  original baseline, accumulated paths, findings, command outcomes, and
  repair history as applicable. Repairs do not reset task state or redefine
  earlier task edits as unrelated work.
* Missing approval, unsupported delegation, or unavailable required evidence
  must remain explicit blockers. Unresolved defects cannot become a `PASS`.
  Results return through the existing callers; do not spawn another lead,
  silently flatten unsupported orchestration, or bypass approvals.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

With the development services running, execute from the repository root on the
Docker host after the final edit:

```bash
make lint-markdown
make check-skills
```

Inspect the complete diff, including new files, for scope, duplicate policy,
contradictory instructions, stale paths, and agreement between contracts and
their callers. `check-skills` validates skill metadata, index coverage, and
local Markdown links and anchors. It does not validate custom-agent front
matter, tool availability, permissions, approval enforcement, or delegation.
`make lint-yaml` checks Symfony configuration, not agent front matter.

For profile changes, review fields and tool identifiers against the intended
client's current documentation linked from the
[compatibility guide](../../../doc/FEATURE_AGENTS.md#compatibility-and-limitations).
Do not assume VS Code fields or nesting settings work in CLI or JetBrains,
or that a tool name in front matter makes that tool available.

When agent discovery, permissions, or routing changes, load the profiles in the
intended client and exercise the affected path with a bounded, non-production
task. Check lead-only picker visibility, brief and design approval stops,
the delivery chain, repair-only return routing, cumulative repair limits,
and blocked-check reporting as affected. Do not trigger an implementation or
repair merely to test routing without authorization.

Report static checks, manual contract review, and live-client results
separately. If a client or authorized scenario is unavailable, state which
behavior remains unverified; passing Markdown checks is not proof of working
agent orchestration. Adding this skill alone does not mean the workflows it
describes were executed.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
