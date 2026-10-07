# Summary

* Describe the change
* Explain why it is needed

## Validation

* [ ] Ran full pre-commit static-analysis suite (`make phpcs`, `make ecs`,
      `make phplint`, `make php-parallel-lint`, `make psalm`, `make phpstan`,
      `make phploc`, `make phpinsights`, `make check-security`,
      `make lint-markdown`)
* [ ] Ran tests (`make run-tests`)
* [ ] For documentation changes, ran `make lint-markdown`
* [ ] Updated tests when behavior changed
* [ ] Added or updated database migration when entity changed
* [ ] For AI-assisted work, included a concise handoff summary (changed files +
      validation status)
* [ ] For AI-assisted work, no commit was created without explicit developer request

## Repository architecture checklist

* [ ] Kept PHP code aligned with strict types and PSR-12 conventions
* [ ] Reused existing shared patterns before introducing new ones
* [ ] Kept business logic in resource or service classes, not controllers
* [ ] Used DTOs for input and output instead of exposing entities directly
* [ ] Updated AutoMapper mapping when entity or DTO structure changed
* [ ] Avoided unrelated refactors
* [ ] Avoided new dependencies unless they were necessary
