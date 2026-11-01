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

Skills are additional references agents can consult when useful, not required
workflow gates. No matching skill, unavailable supporting references, or missing
skill-selection/loading records alone should block work. Repository instructions
and required checks still apply, including the
[shared guidance](shared-guidance.md),
[repository instructions](../copilot-instructions.md) and
[AI rules](../../doc/AI_RULES.md). For the canonical AI documentation inventory
and reading order, see the [README's AI-assisted development
section](../../README.md#ai-assisted-development). Use related skills together
when a task spans multiple concerns. Keep common execution, validation, and
handoff rules in the shared guidance rather than duplicating them in every
skill.

When consulting skills, start with the requested outcome and use additional
references only for layers the task actually changes:

* For a complete CRUD feature, start with
  [add-rest-resource](add-rest-resource/SKILL.md); use the entity, DTO, query,
  and testing skills for the affected parts. A DTO-only or query-only change
  does not require scaffolding a new resource.
* For a custom non-CRUD API operation, use
  [add-custom-api-endpoint](add-custom-api-endpoint/SKILL.md). Do not apply the
  generic CRUD workflow unless the endpoint actually needs those layers.
* For a project-specific Symfony validation rule, use
  [add-validation-constraint](add-validation-constraint/SKILL.md). When the
  constraint is used for API input, also follow the DTO mapping skill.
* For authentication flows or broad access policy, use
  [implement-api-authentication](implement-api-authentication/SKILL.md).
  For subject-based permissions, start with
  [add-security-voter](add-security-voter/SKILL.md).
* For the supported PHP runtime baseline, start with
  [update-php-runtime](update-php-runtime/SKILL.md). For other package versions,
  use [update-dependencies](update-dependencies/SKILL.md); for installed recipe
  scaffolding, use [update-symfony-recipes](update-symfony-recipes/SKILL.md);
  for settings and service wiring, use
  [update-application-configuration](update-application-configuration/SKILL.md).
  Docker image-only changes use
  [update-docker-images](update-docker-images/SKILL.md). GitHub Action pins use
  [update-github-actions](update-github-actions/SKILL.md). CI behavior and
  shared jobs use [update-github-workflows](update-github-workflows/SKILL.md).
* Use [write-tests](write-tests/SKILL.md) whenever tests change. For event
  handlers, use the [event-handling guide](../../doc/EVENT_HANDLING.md) to
  choose the listener or subscriber skill.
* For AI instructions, custom agent profiles, skill contracts, and their
  indexes, use [maintain-ai-guidance](maintain-ai-guidance/SKILL.md).
  Documentation checks do not prove live-client discovery or delegation.
* For shared output serialization, population groups, response formats,
  headers, or error envelopes, use
  [update-api-response-contract](update-api-response-contract/SKILL.md).
  Request DTO mapping alone does not establish the response contract.
* For request/login audit records, redaction, ignored paths, or their
  interaction with Monolog, use
  [update-request-logging](update-request-logging/SKILL.md). Database audit
  capture and diagnostic logs are separate sinks.
* For supported languages/locales, request locale selection, translation
  catalogs, or timezone behavior, use
  [update-localization](update-localization/SKILL.md). User preferences,
  translator fallback, and storage timezone are distinct contracts.
* For repository scripts, Make targets, checker behavior, container forwarding,
  or script regression tests, use
  [maintain-development-tooling](maintain-development-tooling/SKILL.md).
  Use the workflow and dependency skills for those separate contracts.
* For Docker build behavior, Compose services, entrypoints, runtime mounts,
  or Dev Container setup, use
  [update-docker-environment](update-docker-environment/SKILL.md).
  Image tag/digest-only changes remain in the image-update skill.

## Available skills [ᐞ](#table-of-contents)

<a id="available-skills"></a>

| Skill | Use when |
| --- | --- |
| [add-api-dto-mapping](add-api-dto-mapping/SKILL.md) | Changing DTOs, validation, request mapping, or argument resolution. |
| [add-console-command](add-console-command/SKILL.md) | Adding or changing Symfony console commands and their CLI contracts. |
| [add-custom-api-endpoint](add-custom-api-endpoint/SKILL.md) | Adding or changing a custom API operation that does not fit the generic CRUD controller lifecycle. |
| [add-doctrine-entity](add-doctrine-entity/SKILL.md) | Changing persisted entities, ORM mappings, or migrations. |
| [add-doctrine-query](add-doctrine-query/SKILL.md) | Changing repository queries, filtering, search, sorting, or pagination. |
| [add-event-listener](add-event-listener/SKILL.md) | Changing Symfony event listeners or Doctrine lifecycle listeners. |
| [add-event-subscriber](add-event-subscriber/SKILL.md) | Changing Symfony event subscriptions, priorities, or subscriber behavior. |
| [add-rest-resource](add-rest-resource/SKILL.md) | Adding or extending a complete CRUD REST resource. |
| [add-security-voter](add-security-voter/SKILL.md) | Changing voters, subject-based permissions, or authorization call sites. |
| [add-validation-constraint](add-validation-constraint/SKILL.md) | Adding or changing a project-specific Symfony Validator constraint and validator. |
| [implement-api-authentication](implement-api-authentication/SKILL.md) | Changing JWT/API-key authentication or broader access policy. |
| [maintain-ai-guidance](maintain-ai-guidance/SKILL.md) | Changing AI instructions, agent profiles, skill contracts, indexes, or approval and repair routing. |
| [maintain-development-tooling](maintain-development-tooling/SKILL.md) | Changing repository scripts, Make targets, checkers, container forwarding, or script regression tests. |
| [update-api-response-contract](update-api-response-contract/SKILL.md) | Changing shared response serialization, population groups, formats, headers, or error envelopes. |
| [update-application-configuration](update-application-configuration/SKILL.md) | Changing JSON settings, environment overrides, or Symfony service wiring. |
| [update-dependencies](update-dependencies/SKILL.md) | Updating Composer packages, Composer tools, or pinned GitHub Actions. |
| [update-docker-environment](update-docker-environment/SKILL.md) | Changing Docker build behavior, Compose services, entrypoints, runtime configuration, or Dev Container setup. |
| [update-docker-images](update-docker-images/SKILL.md) | Updating Docker image tags and multi-platform digest pins. |
| [update-github-actions](update-github-actions/SKILL.md) | Updating verified action SHA pins in workflows and local composite actions. |
| [update-github-workflows](update-github-workflows/SKILL.md) | Changing CI triggers, jobs, prerequisite gates, matrices, or shared bootstrap behavior. |
| [update-localization](update-localization/SKILL.md) | Changing supported languages/locales, locale selection, translations, localization preferences, or timezone behavior. |
| [update-php-runtime](update-php-runtime/SKILL.md) | Changing the supported PHP version or coordinated PHP runtime baseline. |
| [update-request-logging](update-request-logging/SKILL.md) | Changing request/login logging, redaction, stored metadata, ignored paths, or diagnostic sink interactions. |
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
* Before adding a skill or shared policy, follow the recurrence and
  non-overlap criteria in [AI rules](../../doc/AI_RULES.md#maintaining-task-specific-skills);
  prefer extending an existing skill or shared guidance.
* Check that front-matter names match directory names, every skill is listed
  exactly once, and local links and anchors resolve across repository Markdown
  files. Run `python3 scripts/check-skills.py` from the repository root inside
  the PHP development container (`make bash`, `make fish`, or an IDE Dev
  Container terminal), never directly on the host;
  Markdown lint alone does not verify those relationships.
* Verify referenced classes, commands, flags, and example files against the
  current repository. Distinguish required policy from illustrative examples;
  do not turn one implementation detail into an unconditional rule.
* Run `make lint-markdown` after documentation changes.

---

[Back to previous](../../README.md#ai-assisted-development)

[Back to main README.md](../../README.md)
