# Pull Request

## Summary

- Describe the change
- Explain why it is needed

## Validation

- [ ] Ran the smallest relevant checks for this change
- [ ] Updated tests when behavior changed
- [ ] Added or updated database migration when entity changed
- [ ] For AI-assisted work, included a concise handoff summary (changed files +
      validation status)
- [ ] For AI-assisted work, no commit was created without explicit developer request

## Repository architecture checklist

- [ ] Kept PHP code aligned with strict types and PSR-12 conventions
- [ ] Reused existing shared patterns before introducing new ones
- [ ] Kept business logic in resource or service classes, not controllers
- [ ] Used DTOs for input and output instead of exposing entities directly
- [ ] Updated AutoMapper mapping when entity or DTO structure changed
- [ ] Avoided unrelated refactors
- [ ] Avoided new dependencies unless they were necessary

## Notes for reviewers

- Call out any architectural exception, trade-off, or follow-up work here
