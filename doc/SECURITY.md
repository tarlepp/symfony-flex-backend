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

Because these checks are defined in container build/startup scripts, CI jobs
that build or start these containers inherit the same baseline checks.

At minimum, dependency vulnerability checks are also available manually with:

```bash
make check-security
```

---

[Back to previous](README.md) - [Back to main README.md](../README.md)
