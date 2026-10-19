---
name: add-event-subscriber
description: Create or update Symfony event subscribers, including event maps, priorities, service autoconfiguration, and behavior tests following this repository's conventions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when creating or updating a Symfony event subscriber, its
subscriptions, or its behavior.

Follow the [shared skill guidance](../shared-guidance.md), including
[repository instructions](../../copilot-instructions.md) and
[AI rules](../../../doc/AI_RULES.md).

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Workflow](#workflow)
    * [Validation](#validation)

## Workflow [ᐞ](#table-of-contents)

<a id="workflow"></a>

1. Read `composer.json`, `symfony.lock`, the event dispatch sites or framework
   contracts, and nearby subscribers and tests. Confirm exact event names,
   argument types, timing, and the dispatcher used. Read
   [the event-handling guide](../../../doc/EVENT_HANDLING.md) for the
   listener/subscriber decision.
2. Extend an existing subscriber when the responsibility matches. For a new
   Symfony subscriber, place it in `src/EventSubscriber/` and implement
   `Symfony\Component\EventDispatcher\EventSubscriberInterface`. Follow
   `AcceptLanguageSubscriber` for one event or `LockedUserSubscriber` for
   related authentication events. Keep unrelated concerns in separate
   services; one subscriber may legitimately handle just one event.
3. Implement `public static function getSubscribedEvents(): array` with
   `#[Override]`. Map each dispatched event name to a public callback, using
   `[method, priority]` or multiple callback entries only when needed. Match
   existing typing conventions. Prefer the event class where the dispatcher
   supports it, but preserve bundle string event names and compatibility
   mappings when required by actual dispatch sites; do not blindly register
   both class and string names.
4. Rely on the existing `App\` service discovery and `autoconfigure: true` in
   `config/services.yaml` for normal Symfony subscriber registration. Do not
   add redundant `kernel.event_listener` tags or `AsEventListener` attributes
   to subscriber callbacks. If a dedicated dispatcher is required, inspect
   its registration mechanism rather than assuming the default dispatcher
   receives the event.
5. Inject dependencies through the constructor, keep event adaptation thin,
   and delegate business behavior to resources or services. Keep all methods
   typed and request-specific state out of shared services. Account for
   `app.stopwatch` tagging and `StopwatchCompilerPass`; do not make the service
   readonly or bypass decoration just to simplify a test.
6. Preserve existing subscriptions, priorities, callback names, propagation,
   and exception behavior unless the task changes them. Higher Symfony
   priorities run first. Inspect surrounding handlers before changing order,
   especially locale, authentication, response, and exception processing.
   Decide main-request/subrequest handling explicitly when relevant. Preserve
   security checks and never silently swallow handler failures.
7. Do not confuse Symfony subscribers with Doctrine lifecycle subscriptions.
   `DoctrineExtensionSubscriber` reacts to a Symfony request event; its name
   does not make it a Doctrine subscriber. For ORM events, use
   [add-event-listener](../add-event-listener/SKILL.md) and the installed
   DoctrineBundle's supported listener registration instead of introducing
   legacy Doctrine subscribers.
8. Follow [write-tests](../write-tests/SKILL.md). Cover the exact subscription
   map, including priorities, as in
   `tests/Unit/EventSubscriber/AcceptLanguageSubscriberTest.php`, and observable
   handler behavior as in its integration tests. Also exercise the actual
   caller boundary for registration changes: dispatch through the container's
   appropriate dispatcher or make an HTTP request when kernel sequencing
   matters. Direct handler calls and subscription-map assertions alone cannot
   detect missing registration, wrong dispatcher, or duplicate execution.
   Cover relevant subrequests, no-op paths, invalid inputs, errors, ordering,
   and security outcomes.
9. Update relevant event or API documentation when the change affects
   observable behavior or establishes a new subscription convention.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* Run targeted subscriber and caller-boundary tests inside the PHP development
  container, plus the relevant checks from shared guidance.
* Inspect `bin/console debug:event-dispatcher '<event-name>'` to confirm each
  affected callback and priority. For a dedicated dispatcher, select it with
  `--dispatcher`.
* For service wiring changes, run `bin/console lint:container`. Confirm the
  handler is invoked only as often as intended through the real dispatcher,
  not merely that `getSubscribedEvents()` returns the expected array.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
