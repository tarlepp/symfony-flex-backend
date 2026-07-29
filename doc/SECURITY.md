# What is this?

<a id="what-is-this"></a>

This file defines the security policy for `tarlepp/symfony-flex-backend`,
including supported versions and how to report vulnerabilities responsibly.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Supported versions](#supported-versions)
    * [Reporting a vulnerability](#reporting-a-vulnerability)
    * [What to include in a report](#what-to-include-in-a-report)
    * [Disclosure process](#disclosure-process)
    * [Security maintenance in this repository](#security-maintenance-in-this-repository)
    * [Authentication and authorization](#authentication-and-authorization)
    * [Development best practices](#development-best-practices)

## Supported versions [ᐞ](#table-of-contents)

<a id="supported-versions"></a>

Only the latest state of the default branch is actively supported for security
fixes.

| Version | Supported |
| --- | --- |
| default branch (`main`) | Yes |
| older branches/releases | No |

## Reporting a vulnerability [ᐞ](#table-of-contents)

<a id="reporting-a-vulnerability"></a>

Please use private reporting channels only:

* Preferred: GitHub Security Advisories private reporting (`Report a
  vulnerability` in the Security tab).
* Alternative: email `tarmo.leppanen@pinja.com` with subject
  `SECURITY: symfony-flex-backend`.

Do not open public issues or pull requests for unpatched vulnerabilities.

## What to include in a report [ᐞ](#table-of-contents)

<a id="what-to-include-in-a-report"></a>

Include as much of the following as possible:

* Affected component(s), endpoint(s), and version/commit.
* Reproduction steps and required preconditions.
* Impact assessment (confidentiality, integrity, availability).
* Proof-of-concept payload or request examples.
* Suggested mitigation or patch idea (if available).

## Disclosure process [ᐞ](#table-of-contents)

<a id="disclosure-process"></a>

The maintainer follows coordinated disclosure:

* Acknowledge receipt within 3 business days.
* Share an initial triage or status update within 14 business days.
* Work toward a fix before public disclosure whenever possible.
* Publish release notes and CVE references (when applicable) after a fix is
  available.

## Security maintenance in this repository [ᐞ](#table-of-contents)

<a id="security-maintenance-in-this-repository"></a>

Security-related checks are part of the regular quality workflow.

Containerized security checks are enabled in repository scripts by default:

* Development container startup runs `composer audit --abandoned=report`
  (`docker-entrypoint-dev.sh`).
* Container image build includes Debian package security updates via
  `debsecan` (`Dockerfile`).
* Container image build also runs `composer audit` (`Dockerfile`).
* Docker image vulnerability scanning via Trivy (`debsecan` + `trivy` for
  container image analysis).

CI pipeline security checks:

* Secret detection via Gitleaks Action to prevent accidental credential commits.

Because these checks are defined in container build/startup scripts, CI jobs
that build or start these containers inherit the same baseline checks.

At minimum, dependency vulnerability checks are also available manually with:

```bash
make check-security
```

## Authentication and authorization [ᐞ](#table-of-contents)

<a id="authentication-and-authorization"></a>

This project supports multiple authentication methods:

* **JWT (JSON Web Tokens):** Stateless token-based authentication via Bearer tokens
  in the `Authorization` header. Configured with public/private key pair.
* **API Keys:** Application-level API key authentication for programmatic access.

Authorization uses role-based access control (RBAC):

* `ROLE_API` - API consumer role
* `ROLE_LOGGED` - Authenticated user role (implied by ROLE_API or ROLE_USER)
* `ROLE_USER` - Standard authenticated user role
* `ROLE_ADMIN` - Administrative role (inherits ROLE_USER)
* `ROLE_ROOT` - Root/superuser role (inherits ROLE_ADMIN)

JWT and API key credentials should be stored securely and never committed to
version control.

## Development best practices [ᐞ](#table-of-contents)

<a id="development-best-practices"></a>

**Secrets Management:**

* Never commit `.env.local`, JWT keys (`config/jwt/`), or credentials to
  version control.
* Store sensitive values in `secrets/` directory or environment variables only.
* Use `make generate-jwt-keys` to create JWT key pairs (output: not committed).

**Input Validation:**

* Use Symfony Validator constraints for all API input validation.
* Validate early in the request lifecycle (controller/DTO layer).
* Reject invalid input with appropriate error responses.

**Data Exposure Control:**

* Use DTOs (Data Transfer Objects) in `src/DTO/` to control what data is
  exposed in API responses.
* Never expose raw entities in API responses.
* Use the AutoMapper for safe entity-to-DTO mapping.

**Production Security:**

* Always use HTTPS/TLS in production.
* Ensure CORS configuration (`config/packages/nelmio_cors.yaml`) is appropriate
  for your use case.
* Review and configure role-based access control in `config/packages/security.yaml`.
* Regularly run `make check-security` to scan for known vulnerabilities.

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
