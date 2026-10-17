---
name: implement-api-authentication
description: Implement or update API authentication and authorization using this repository's JWT, API-key, role, and voter patterns.
---

# Implement API authentication and authorization

Use this skill when changing how API callers authenticate, which credentials
are accepted, or which authenticated callers may access an endpoint or resource.

Follow the shared skill guidance in `.github/skills/shared-guidance.md`.

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
6. Follow `.github/skills/write-api-tests/SKILL.md` for testing. Cover valid
   and invalid or missing credentials, status codes, permitted and denied
   roles, and anonymous access where relevant. Exercise both JWT and API-key
   authentication when shared firewall or policy behavior changes.
7. Update OpenAPI security metadata or relevant documentation when the
   documented authentication requirements or endpoint access contract changes.
   If the work changes persisted security data, follow
   `.github/skills/add-doctrine-entity/SKILL.md` for the entity and migration.
