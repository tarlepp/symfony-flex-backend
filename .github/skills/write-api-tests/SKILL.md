---
name: write-api-tests
description: Add or update PHPUnit tests for REST API behavior using this repository's E2E, integration, fixtures, and authentication helpers.
---

# Write API tests

Use this skill when adding or changing tests for an API endpoint, DTO, resource,
repository, or request mapping. Prefer tests that exercise the behavior through
the same boundary used by callers.

## Choose the test layer

* Use `tests/E2E/Controller/` for HTTP request/response behavior, including
  status codes, JSON shape, validation errors, authentication, and
  authorization. Extend `App\Tests\E2E\TestCase\WebTestCase` and use its
  `getTestClient()` or `getApiKeyClient()` helpers. Follow nearby tests for
  credentials, fixture expectations, request headers, and JSON assertions.
* Use `tests/Integration/Controller/` for wiring checks such as whether a
  controller is registered and uses the intended resource. Extend the
  appropriate existing integration test case, such as
  `RestIntegrationControllerTestCase`.
* Use `tests/Integration/DTO/` for DTO validation, mapping, and update/patch
  behavior. Reuse `DtoTestCase`, `RestRequestMapperTestCase`, or
  `RestRequestMapperConfigurationTestCase` when the tested class fits.
* Use `tests/Integration/Resource/` and
  `tests/Integration/Repository/` for behavior that depends on persistence or
  the Doctrine-backed resource/repository.
* Use `tests/Unit/` only for behavior that can be isolated from the Symfony
  container, database, and HTTP stack. Keep integration and E2E assertions out
  of unit tests.

## Workflow

1. Inspect the closest test for the same endpoint, DTO, resource, or repository,
   along with its test base class and relevant fixtures. Confirm whether test
   data is reset or shared before relying on fixed counts or IDs.
2. Write tests for observable behavior and meaningful edge cases. Cover
   successful and invalid requests, expected response status and JSON, and
   authorization boundaries when the change affects protected routes or roles.
   Avoid asserting incidental implementation details.
3. For writes, assert the resulting state or response rather than only that the
   request did not throw. Keep test data isolated and follow established
   fixture/data-provider patterns. Do not modify shared fixtures just to make
   one assertion pass if a local setup is more appropriate.
4. Use the project's existing test helpers and JSON utilities. Do not create
   alternate auth helpers or hand-roll request serialization when the test
   suite already provides a suitable helper.
5. When behavior changes, update or replace assertions that encode the old
   contract. Do not weaken or delete existing security or regression coverage
   without explicit justification.

## Running tests

Run commands from the repository root inside the running PHP development
container. The project does not use `bin/phpunit`; invoke targeted tests with
the installed runner, for example:

```bash
./vendor/bin/phpunit tests/E2E/Controller/v1/User/UserControllerTest.php
./vendor/bin/phpunit tests/Integration/Controller/v1/User/UserControllerTest.php
```

Run the smallest relevant test class or directory first. Run `make run-tests`
when broader regression coverage is warranted. Use `doc/TESTING.md` and the
Makefile for current runner details; do not add a new test framework or runner.

Before handoff, identify the tests added or changed and state which targeted
tests ran or were blocked, including the reason. Do not commit unless explicitly
requested.
