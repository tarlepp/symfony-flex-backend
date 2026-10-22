---
name: add-event-listener
description: Create or update Symfony event listeners or Doctrine lifecycle listeners, including service registration, event ordering, and tests following this repository's conventions.
---

# What is this?

<a id="what-is-this"></a>

Use this skill when creating or updating an event listener, its registration,
or its behavior. It covers Symfony EventDispatcher listeners and Doctrine
lifecycle listeners; these are separate event systems.

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

1. Read the dispatch site or framework event contract, and nearby handlers and
   tests. Identify the event system, exact
   event name, payload type, dispatch timing, and any dispatcher-specific
   registration. Do not infer these from a class name alone.
2. Read [the event-handling guide](../../../doc/EVENT_HANDLING.md) before
   choosing a listener over a subscriber. Extend an existing handler when the
   responsibility matches; preserve its registration style unless a conversion
   is part of the task. For a Symfony subscriber, follow
   [add-event-subscriber](../add-event-subscriber/SKILL.md).
3. Put listener classes in `src/EventListener/`. Keep handlers focused on event
   adaptation and delegate business logic to an existing resource or service.
   Use constructor injection, explicit parameter and return types, and the
   event argument type supported by the installed package. Do not mark a
   service readonly when lazy proxying may apply.
4. Wire the listener explicitly: `config/services.yaml` excludes
   `src/EventListener/` from automatic service discovery. Follow
   `config/packages/event_listeners.yaml` for service definitions and
   autowiring. For Symfony events, use `kernel.event_listener` with the correct
   event and method, or `AsEventListener` on an explicitly registered service.
   Do not register the same callback with both an attribute and a tag.
5. For Doctrine lifecycle events, follow `UserEntityEventListener` and its
   `doctrine.event_listener` tags instead of Symfony event registration. Match
   method names and event arguments to the ORM event. A global Doctrine
   listener receives events for multiple entity types, so guard the entity
   type. Check connection scope, UnitOfWork/change-set rules, and whether
   changes made at that lifecycle stage will actually be persisted. Do not
   call `flush()` recursively from a flush-triggered handler. Distinguish
   global lifecycle listeners from entity-specific Doctrine entity listeners;
   the latter require their own mapping and registration.
6. For updates, preserve event names, callback methods, priorities, dispatcher
   or connection scope, propagation, and exception behavior unless explicitly
   changing them. For ordering-sensitive behavior, inspect neighboring
   handlers and justify the priority; higher Symfony priorities run first.
   Decide main-request versus subrequest handling for HTTP events from the
   required behavior rather than adding a blanket guard.
7. Keep request-specific data out of shared service state. Preserve validation,
   authorization, and sensitive-data handling. Do not swallow exceptions,
   perform unrelated side effects, or treat synchronous events as background
   jobs.
8. Cover the handler's effect, relevant no-op paths, invalid inputs, and failures.
   Add registration
   coverage through the actual caller boundary: dispatch a Symfony event
   through the container's dispatcher, or persist/update and flush an entity
   through Doctrine as appropriate. Direct method calls alone do not prove
   that registration or lifecycle persistence works. Verify ordering,
   subrequests, and exactly-once invocation when relevant.
9. Update the event-handling guide or other relevant documentation if the
   change establishes a new registration convention or observable behavior.

## Validation [ᐞ](#table-of-contents)

<a id="validation"></a>

* For Symfony listeners, inspect
  `bin/console debug:event-dispatcher '<event-name>'` for the intended event,
  callback, and priority. Use `--dispatcher` when the event uses a dedicated
  dispatcher; this command does not verify Doctrine lifecycle registration.
* For Doctrine listeners, verify registration and persisted results through the
  ORM test rather than relying on Symfony dispatcher output.

---

[Back to previous](../README.md)

[Back to main README.md](../../../README.md)
