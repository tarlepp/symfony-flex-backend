---
name: implement-api-authentication
description: Implement or update API authentication and authorization using this repository's JWT, API-key, role, and voter patterns.
---

# Implement API authentication and authorization

Use this skill when changing how API callers authenticate, which credentials
are accepted, or which authenticated callers may access an endpoint or resource.

Before applying this skill, read `.github/copilot-instructions.md` and
`doc/AI_RULES.md`. Follow those general repository rules alongside this
task-specific workflow; they take precedence if guidance conflicts.

## Workflow

1. Check `composer.json` and `symfony.lock` for the installed Symfony Security
   and authentication bundles. Inspect `config/packages/security.yaml`, the
   relevant routes and controllers, and nearby security classes and tests
   before changing behavior. Treat the current implementation as the source of
   truth rather than assuming framework or bundle defaults.
2. Clarify the intended caller, credentials, public/protected endpoints, and
   required roles when they are not specified. Ask before making non-trivial
   changes to authentication methods, role policy, token lifecycle, or access
   boundaries.
3. Preserve firewall ordering, stateless API behavior, and existing login or
   token-issuance flows unless the request explicitly requires changing them.
   Review access-control ordering carefully: Symfony applies only the first
   matching access-control rule. Never make a protected route public or weaken
   an existing guard to make a test pass.
4. Reuse the existing security building blocks where they fit:
   `ApiKeyAuthenticator`, `ApiKeyUserProvider`, `SecurityUserFactory`,
   `RolesService`, `App\Enum\Role`, the configured role hierarchy, and existing
   voters. Use the project's established route/controller authorization
   patterns, including `#[IsGranted]` where appropriate. Keep authorization
   decisions scoped to the right endpoint or voter rather than embedding them
   in unrelated controller logic.
5. Handle credentials as secrets. Do not expose tokens, passwords, private JWT
   keys, or sensitive authentication details in API responses, DTOs, logs,
   fixtures, or documentation. Follow the existing credential creation,
   rotation, and revocation workflows; do not invent a new token storage or
   lifecycle design without explicit requirements.
6. Add caller-level tests for the behavior changed. Cover valid and invalid or
   missing credentials, authentication status codes, permitted and denied
   roles, and anonymous access where relevant. Exercise both JWT and API-key
   authentication if the change affects their shared firewall or policy. Use
   `App\Tests\E2E\TestCase\WebTestCase` helpers such as `getTestClient()` and
   `getApiKeyClient()` rather than creating alternate authentication helpers.
   Add focused integration tests for providers, authenticators, roles, or
   voters when their internal behavior changes. Follow
   `.github/skills/write-api-tests/SKILL.md`.
7. Update OpenAPI security metadata or relevant documentation when the
   documented authentication requirements or endpoint access contract changes.
   If the work changes persisted security data, follow
   `.github/skills/add-doctrine-entity/SKILL.md` for the entity and migration.

## Validation

From the host machine, run `make bash` (or `make fish`) in the repository root
to open a shell in the PHP Docker container. If the containers are stopped,
start them first with `make start`, then open the container shell from a
second host terminal. An IDE Dev Container terminal is already inside the
container.

Run project commands from the repository root inside the running PHP
development container, not on the host machine. Run the smallest relevant
`phpunit` test classes first, including affected E2E tests; run `make run-tests`
when broader regression coverage is warranted. Run `make ecs`, `make phplint`,
`make phpstan`, and `make psalm` for PHP changes. Run `make lint-markdown` when
this skill or other Markdown files change. Follow the full pre-commit validation
suite in `.github/copilot-instructions.md` before commit.

Before handoff, identify the authentication or authorization behavior changed,
the tests run or blocked, and any migration that was generated or applied to a
non-production database. Do not commit unless explicitly requested.
