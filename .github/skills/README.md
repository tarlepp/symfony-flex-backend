# What is this?

<a id="what-is-this"></a>

This directory contains task-specific AI skills for this repository. Each skill
provides a workflow grounded in the existing implementation, plus relevant
validation checks. Skills supplement the repository instructions; they do not
replace them.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Using skills](#using-skills)
    * [Available skills](#available-skills)
    * [Maintaining skills](#maintaining-skills)

## Using skills [ᐞ](#table-of-contents)

<a id="using-skills"></a>

Before starting a task, select the matching skill below and follow the
[shared guidance](shared-guidance.md), including
[repository instructions](../copilot-instructions.md) and
[AI rules](../../doc/AI_RULES.md). Use related skills together when a task spans
multiple concerns. Keep common execution, validation, and handoff rules in the
shared guidance rather than duplicating them in every skill.

Start with the skill matching the requested outcome, and follow additional
skills only for layers the task actually changes:

* For a complete CRUD feature, start with
  [add-rest-resource](add-rest-resource/SKILL.md); use the entity, DTO, query,
  and testing skills for the affected parts. A DTO-only or query-only change
  does not require scaffolding a new resource.
* For authentication flows or broad access policy, use
  [implement-api-authentication](implement-api-authentication/SKILL.md).
  For subject-based permissions, start with
  [add-security-voter](add-security-voter/SKILL.md).
* For package versions, start with
  [update-dependencies](update-dependencies/SKILL.md); for installed recipe
  scaffolding, use [update-symfony-recipes](update-symfony-recipes/SKILL.md);
  for settings and service wiring, use
  [update-application-configuration](update-application-configuration/SKILL.md).
  Docker image changes use [update-docker-images](update-docker-images/SKILL.md).
  GitHub Action pins use [update-github-actions](update-github-actions/SKILL.md).
* Use [write-tests](write-tests/SKILL.md) whenever tests change. For event
  handlers, use the [event-handling guide](../../doc/EVENT_HANDLING.md) to
  choose the listener or subscriber skill.

## Available skills [ᐞ](#table-of-contents)

<a id="available-skills"></a>

| Skill | Use when |
| --- | --- |
| [add-api-dto-mapping](add-api-dto-mapping/SKILL.md) | Changing DTOs, validation, request mapping, or argument resolution. |
| [add-console-command](add-console-command/SKILL.md) | Adding or changing Symfony console commands and their CLI contracts. |
| [add-doctrine-entity](add-doctrine-entity/SKILL.md) | Changing persisted entities, ORM mappings, or migrations. |
| [add-doctrine-query](add-doctrine-query/SKILL.md) | Changing repository queries, filtering, search, sorting, or pagination. |
| [add-event-listener](add-event-listener/SKILL.md) | Changing Symfony event listeners or Doctrine lifecycle listeners. |
| [add-event-subscriber](add-event-subscriber/SKILL.md) | Changing Symfony event subscriptions, priorities, or subscriber behavior. |
| [add-rest-resource](add-rest-resource/SKILL.md) | Adding or extending a complete CRUD REST resource. |
| [add-security-voter](add-security-voter/SKILL.md) | Changing voters, subject-based permissions, or authorization call sites. |
| [implement-api-authentication](implement-api-authentication/SKILL.md) | Changing JWT/API-key authentication or broader access policy. |
| [update-application-configuration](update-application-configuration/SKILL.md) | Changing JSON settings, environment overrides, or Symfony service wiring. |
| [update-dependencies](update-dependencies/SKILL.md) | Updating Composer packages, Composer tools, or pinned GitHub Actions. |
| [update-docker-images](update-docker-images/SKILL.md) | Updating Docker image tags and multi-platform digest pins. |
| [update-github-actions](update-github-actions/SKILL.md) | Updating verified action SHA pins in workflows and local composite actions. |
| [update-symfony-recipes](update-symfony-recipes/SKILL.md) | Inspecting or updating installed Symfony Flex recipes with Composer. |
| [write-tests](write-tests/SKILL.md) | Adding or changing PHPUnit tests and choosing the appropriate test boundary. |

## Maintaining skills [ᐞ](#table-of-contents)

<a id="maintaining-skills"></a>

* Put each skill in `<skill-name>/SKILL.md` with YAML front matter containing
  its `name` and task-specific `description`.
* Use `# What is this?`, its anchor, a scope introduction, a link to shared
  guidance, and a table of contents. Include `Workflow` and `Validation`
  sections; add task-specific sections where needed.
* Give each section an explicit anchor and a table-of-contents return link.
  Keep the table of contents aligned with the actual headings.
* Link references to existing Markdown files using relative repository paths
  rather than formatting their paths only as inline code.
* End each skill with `Back to previous` linking to this index and
  `Back to main README.md` linking to the repository README.
* Put common preparation, contract clarification, test-selection rules,
  configuration linting, and documentation/handoff requirements in
  [shared guidance](shared-guidance.md). Keep concrete inspection targets,
  domain decisions, safety checks, and verification details in each skill.
* Preserve task-specific safety checks and behavior when harmonizing format.
  Update this index whenever skills are added, removed, or renamed.
* Check that front-matter names match directory names, every skill is listed
  exactly once, and relative links, anchors, and table-of-contents entries
  resolve. Markdown lint alone does not verify those relationships.
* Verify referenced classes, commands, flags, and example files against the
  current repository. Distinguish required policy from illustrative examples;
  do not turn one implementation detail into an unconditional rule.
* Run `make lint-markdown` after documentation changes.

---

[Back to previous](../../README.md#ai-assisted-development)

[Back to main README.md](../../README.md)
