# What is this?

<a id="what-is-this"></a>

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Summary](#summary)
    * [Validation](#validation)
    * [Repository architecture checklist](#repository-architecture-checklist)
    * [Notes for reviewers](#notes-for-reviewers)

## Summary [ᐞ](#table-of-contents)

<a id="summary"></a>

* Describe the change
* Explain why it is needed

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* [ ] Ran full pre-commit static-analysis suite (`make phpcs`, `make ecs`,
      `make phplint`, `make php-parallel-lint`, `make psalm`, `make phpstan`,
      `make phploc`, `make phpinsights`, `make check-security`,
      `make lint-markdown`)
* [ ] Ran tests (`make run-tests` or `make run-tests-fastest`)
* [ ] For documentation changes, ran `make lint-markdown`
* [ ] Updated tests when behavior changed
* [ ] Added or updated database migration when entity changed
* [ ] For AI-assisted work, included a concise handoff summary (changed files +
      validation status)
* [ ] For AI-assisted work, no commit was created without explicit developer request

## Repository architecture checklist [ᐞ](#table-of-contents)

<a id="repository-architecture-checklist"></a>

* [ ] Kept PHP code aligned with strict types and PSR-12 conventions
* [ ] Reused existing shared patterns before introducing new ones
* [ ] Kept business logic in resource or service classes, not controllers
* [ ] Used DTOs for input and output instead of exposing entities directly
* [ ] Updated AutoMapper mapping when entity or DTO structure changed
* [ ] Avoided unrelated refactors
* [ ] Avoided new dependencies unless they were necessary

## Notes for reviewers [ᐞ](#table-of-contents)

<a id="notes-for-reviewers"></a>

* Call out any architectural exception, trade-off, or follow-up work here

---

[Back to previous](../README.md)
