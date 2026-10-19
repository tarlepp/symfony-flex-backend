---
name: add-security-voter
description: Add or update Symfony security voters, subject-based authorization, and access-control tests using this repository's security identities and decision strategy.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding or changing a Symfony security voter, its supported
attributes or subjects, or the authorization checks that invoke it. For changes
to credentials, authentication flows, firewalls, or broader role policy, also
follow `.github/skills/implement-api-authentication/SKILL.md`.

Follow the [shared skill guidance](../shared-guidance.md), including
`.github/copilot-instructions.md` and `doc/AI_RULES.md`. Read `doc/SECURITY.md`
for the repository security policy.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Read `composer.json` for supported PHP and Symfony Security versions and
   `symfony.lock` for recipes. Inspect `config/packages/security.yaml`,
   `config/services.yaml`, `src/Security/Voter/IsUserHimselfVoter.php`, its tests,
   and every affected authorization call site before choosing a pattern.
   Trace how the subject is resolved and which authenticated identity reaches
   the voter; do not infer either from the route or entity class alone.
2. Establish the permission attribute, supported subject type, allowed and
   denied callers, ownership rules, and any role-based override. Include
   anonymous callers, other owners, JWT users, and API-key callers where
   relevant. Ask before making an unspecified policy decision or changing an
   access boundary. Extend an existing voter when it owns the same policy;
   keep unrelated permissions separate.
3. Place voters in `src/Security/Voter/` and extend Symfony's `Voter`, following
   local typing, generics, and `#[Override]` conventions. Match the installed
   method signatures, including the optional `Vote` argument where applicable.
   Implement `supports()` narrowly for the exact attributes and subject types.
   Unsupported requests must abstain, not grant or deny unrelated permissions;
   denial of a supported request belongs in `voteOnAttribute()`. Account for
   subjectless checks only when the intended permission requires them.
4. Read the caller from the supplied `TokenInterface`. Human users are
   represented by `SecurityUser`, not the Doctrine `User` entity; API keys use
   `ApiKeyUser`. For ownership checks, compare stable entity identifiers as
   `IsUserHimselfVoter` does, not object identity or display names. Do not treat
   an API-key identifier or credential as a human user's UUID. Deny supported
   requests for missing or inappropriate identities unless the policy
   explicitly permits them. Never log credentials or expose them in denial
   messages or tests.
5. Keep authorization decisions focused and free of mutations. Inject existing
   resources or services when domain information is needed rather than putting
   persistence or business operations in the voter. If the policy depends on
   roles, preserve configured inheritance using the existing security decision
   or role helpers; do not replace hierarchy-aware checks with a raw
   `getRoles()` membership test. When delegating another decision from a voter,
   use the supplied token with `AccessDecisionManagerInterface` and avoid
   voting recursively on the same attribute and subject.
6. Preserve the configured `unanimous` access-decision strategy and inspect
   overlapping voters: one supported denial can block an otherwise granted
   permission. Do not change the strategy or all-abstain behavior to make a
   new check pass. Keep intentional alternatives together at the correct
   decision boundary. For example, `UserRolesController` and
   `UserGroupsController` express self-or-root access with an `IsGranted`
   expression; adding a separate ownership requirement alongside a role check
   is not equivalent to that alternative.
7. Wire the permission at every relevant caller using the established
   `#[IsGranted]` or authorization-service pattern. Pass the actual subject;
   for attribute-based controllers, match the subject name to the resolved
   controller argument. Preserve class-level, method-level, trait-based, and
   firewall guards unless the requested policy explicitly changes them.
   Role-only checks do not enforce ownership, and merely creating a voter does
   not protect an endpoint. Preserve existing response status and error
   handling rather than swallowing access-denied exceptions.
8. Rely on existing `App\` service discovery and `autoconfigure: true` for
   standard voter registration. Do not add redundant `security.voter` tags or
   manually instantiate a voter at application call sites. If service
   configuration is exceptional, inspect its registration before changing it.
   Keep request-specific identities and subjects out of shared service state.
9. Follow `.github/skills/write-tests/SKILL.md`. Use
   `tests/Integration/Security/Voter/IsUserHimselfVoterTest.php` as a reference
   for exercising the public `vote()` boundary and asserting grant, deny, and
   abstain results, not protected methods. Cover unsupported attributes and
   subjects, anonymous or inappropriate identities, matching and different
   owners, and relevant role overrides. Add container-backed decision-manager
   coverage when registration, voter overlap, or role inheritance matters.
   For endpoint checks, follow
   `tests/E2E/Controller/v1/User/UserRolesControllerTest.php`: exercise the real
   request with allowed and denied callers and assert response status and
   protected output or absence of write side effects. Test JWT and API-key
   paths when shared policy affects both. Direct voter tests alone do not
   prove registration, correct subject binding, or endpoint protection.
10. Update relevant security documentation and OpenAPI access/error metadata
    when the observable authorization contract changes. Keep policy details
    close to existing documentation rather than creating parallel guidance.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run targeted voter, decision-manager, and affected endpoint tests inside the
  PHP development container, plus relevant checks from shared guidance.
* For service wiring changes, run `bin/console lint:container` and inspect
  `bin/console debug:container --tag=security.voter`. A registered service alone
  does not prove that the intended permission is enforced; verify the actual
  decision and caller boundary.
* Confirm that both permitted and forbidden cases behave as intended without
  weakening existing authentication, role requirements, or other voters.

---

[Back to previous](../shared-guidance.md)

[Back to main README.md](../../../README.md)
