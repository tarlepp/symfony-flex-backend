---
name: add-doctrine-query
description: Add or update Doctrine repository queries, filtering, search, sorting, and pagination using this repository's shared query helpers and database tests.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when adding or changing repository queries, query criteria,
searchable fields, joins, sorting, pagination, counts, or ID lookups. For entity
mapping or index changes, also follow
`.github/skills/add-doctrine-entity/SKILL.md`. For a full endpoint change, also
follow `.github/skills/add-rest-resource/SKILL.md`.

Follow the [shared skill guidance](../shared-guidance.md), including
`.github/copilot-instructions.md` and `doc/AI_RULES.md`.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Read `composer.json` for supported PHP, Doctrine ORM, and DBAL versions,
   `symfony.lock` for recipes, and `config/packages/doctrine.yaml` for mappings
   and database types. Inspect the target entity, repository, resource, callers,
   and nearby tests. Trace request criteria through `RequestHandler`, controller
   processing, and resource normalization when API query parameters are
   affected. Do not assume that raw HTTP input and repository arguments have
   the same shape.
2. Establish the expected result type, matching rules, relationship semantics,
   ordering, pagination, and missing-result behavior. Distinguish no filter
   from an empty list, `null`, zero, or an empty string. Ask when these choices
   change an unspecified API contract or access boundary. Preserve existing
   authorization and data-visibility restrictions; user-provided criteria must
   not replace mandatory restrictions.
3. Keep data access in repositories under `src/Repository/`, following
   `BaseRepository<TEntity>`, `$entityName`, and local dependency-injection
   conventions. Keep business orchestration in resources or services. First
   check `RepositoryMethodsTrait`, `RepositoryWrappersTrait`,
   `RepositoryHelper`, and `SearchTerm` for reusable behavior rather than
   duplicating a generic query or introducing a parallel repository pattern.
4. Choose the narrowest existing method that meets the need. Standard
   `findBy()` and `findOneBy()` delegate to Doctrine's entity repository;
   advanced methods use this project's criteria/search helpers and queued
   query customizations. These paths are not interchangeable. For a custom
   query, use the existing `createQueryBuilder()` wrapper, whose default alias
   is `entity`, and declare entity, scalar, list, or array-shape return types
   accurately. Preserve single-result cardinality errors instead of masking
   them with an arbitrary first row or an empty result.
5. Bind untrusted values with query parameters and the appropriate DBAL type.
   Parameters do not protect field names, aliases, operators, or sort
   directions: resolve dynamic identifiers through validated, intended choices
   rather than interpolating arbitrary request strings into DQL or SQL.
   Preserve the existing criteria grammar and error handling. Reuse
   `UuidHelper` and nearby UUID-binding patterns for binary UUID fields, and
   check configured enum and date/time types. For lists, verify the installed
   DBAL's supported array binding and UUID conversion rather than assuming a
   scalar type converts every element.
6. Keep filtering and search consistent with `RepositoryHelper` and the
   repository's `$searchColumns`. Verify AND/OR grouping, null comparisons,
   empty-list behavior, and relationship aliases. Preserve existing search
   tokenization, LIKE wildcard behavior, and database collation semantics
   unless the requested contract changes them. Do not silently ignore an
   invalid new filter, widen the matching set, or catch query failures and
   return success-shaped empty results.
7. Choose joins deliberately: an inner join excludes roots without a match;
   a left join may preserve them until a WHERE predicate excludes them.
   Select joined associations only when fetching them is needed. Check for
   duplicate root rows, lazy-loading/N+1 queries, and changed result shapes.
   When using `addLeftJoin()`, `addInnerJoin()`, or `addCallback()`, inspect
   `BaseRepository::processQueryBuilder()`: queued customizations are consumed
   during processing, not automatically applied by every repository method.
   Ensure required restrictions reach each related list, count, and ID query;
   do not assume one queued callback persists across those calls.
8. Preserve sorting and pagination contracts. `RepositoryHelper::processOrderBy()`
   handles string directions and `SortDirection`; resource normalization may
   affect what reaches it. `findByAdvanced()` currently returns an empty array
   for a non-positive limit, uses no paginator when the limit is absent, and
   uses `OffsetPaginator` with a `Window` when a limit is supplied. Do not
   replace this with row-level limits on collection fetch joins, which can
   truncate pages or associations. Use deterministic ordering for new paged
   queries, with a stable tie-breaker where needed, without changing existing
   ordering implicitly.
9. Keep count and ID results aligned with the matching root entities.
   `countAdvanced()` counts distinct root IDs and `findIds()` selects distinct
   IDs; joins must not inflate totals. For custom aggregates or grouped queries,
   verify the intended count explicitly instead of copying the list query
   blindly. Review generated SQL and query plans when a performance claim
   matters. Add an index only when justified, through entity mapping and a
   reviewed migration using the entity skill; never change a live schema with
   ad hoc SQL or `doctrine:schema:update`.
10. Follow `.github/skills/write-tests/SKILL.md`. Use real-database Functional
    tests such as `tests/Functional/Repository/UserRepositoryTest.php` to prove
    query results; mock QueryBuilder expectations alone cannot verify SQL
    semantics. Inspect existing repository test helpers before adding setup.
    Cover matching and excluded entities, no matches, relevant null/empty
    inputs, relation duplicates or missing relations, search combinations,
    ordering ties, and pagination boundaries. Assert exact IDs and order, not
    only row counts, and verify count/ID consistency where affected. For shared
    helper changes, extend `tests/Integration/Rest/RepositoryHelperTest.php`
    or `tests/Unit/Rest/SearchTermTest.php` as appropriate. For API query
    changes, also exercise the real HTTP boundary and its authorization,
    validation, response shape, and paging behavior.
11. Update existing query-parameter documentation or API metadata when accepted
    filters, search, ordering, pagination, or results change. A query-only
    change does not require a migration unless persisted mapping changes.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run targeted repository, shared-helper, and affected caller-boundary tests
  inside the PHP development container, plus relevant checks from shared
  guidance. Use the existing test database and fixture lifecycle.
* For index or mapping changes, follow the entity skill's migration and schema
  validation checks. Do not run destructive queries against production or
  alter persistent non-test data without explicit approval.
* For performance work, verify the requested improvement using representative
  data and measured query counts or execution plans; successful execution or
  fewer lines of query code is not evidence of faster behavior.

---

[Back to previous](../shared-guidance.md)

[Back to main README.md](../../../README.md)
