# What is this?

<a id="what-is-this"></a>

This guide explains when to use an event listener or subscriber and how to
register and test them in this repository. Symfony EventDispatcher events and
Doctrine ORM lifecycle events are separate systems.

## Table of Contents [ᐞ](#table-of-contents)

<a id="table-of-contents"></a>

* [What is this](#what-is-this)
  * [Table of Contents](#table-of-contents)
    * [Choosing a listener or subscriber](#choosing-a-listener-or-subscriber)
    * [Symfony event registration](#symfony-event-registration)
    * [Doctrine lifecycle events](#doctrine-lifecycle-events)
    * [Ordering and handler responsibilities](#ordering-and-handler-responsibilities)
    * [Testing and inspection](#testing-and-inspection)
    * [AI skills](#ai-skills)

## Choosing a listener or subscriber [ᐞ](#table-of-contents)

<a id="choosing-a-listener-or-subscriber"></a>

In Symfony, both are services called by an event dispatcher. A **listener**
is a callable registered for an event through configuration or attributes.
A **subscriber** implements `EventSubscriberInterface` and declares its event
names, methods, and priorities in `getSubscribedEvents()`. Neither is
inherently asynchronous or faster, and either can handle one or several events.

* **Existing handler:** preserve its listener/subscriber style unless conversion
  is required.
* **Cohesive Symfony concern that owns its subscription map:** use a subscriber,
  following this repository's existing Symfony handlers.
* **Related Symfony events sharing dependencies:** use a subscriber when
  grouping them keeps one clear responsibility.
* **Focused callable with bindings in configuration or attributes:** use a
  listener with explicit service registration.
* **Doctrine persistence lifecycle behavior:** use a Doctrine listener, not a
  Symfony subscriber.
* **Required step in one resource/service workflow:** prefer a direct service
  call or an existing resource lifecycle callback over adding an event.

Do not choose solely by event count. `AcceptLanguageSubscriber` handles just
one event and keeps its locale priority with the subscription. In contrast,
`LockedUserSubscriber` groups authentication success and failure for one
account-locking concern. A listener can also have several registrations:
`UserEntityEventListener` handles both Doctrine `prePersist` and `preUpdate`.

Keep unrelated responsibilities separate even if their events occur at the
same stage. Do not convert existing subscribers to listeners merely to follow
a general preference from another project.

## Symfony event registration [ᐞ](#table-of-contents)

<a id="symfony-event-registration"></a>

* Put subscribers in `src/EventSubscriber/` and implement
  `Symfony\Component\EventDispatcher\EventSubscriberInterface`. The `App\`
  service discovery and `autoconfigure: true` in `config/services.yaml`
  register them automatically.
* Declare subscriptions in `public static function getSubscribedEvents(): array`,
  with `#[Override]`, following nearby classes. Use the dispatched event class
  or string name, not a guessed equivalent. Bundle events may use named string
  constants; inspect their dispatch sites before changing existing mappings.
* Put listener classes in `src/EventListener/`. This directory is excluded
  from automatic service discovery. Add an explicit service definition,
  following `config/packages/event_listeners.yaml`, even when using
  `AsEventListener`.
* A Symfony listener can use a `kernel.event_listener` service tag with its
  event and method, or
  `Symfony\Component\EventDispatcher\Attribute\AsEventListener`. Use one
  registration mechanism per callback to avoid duplicate execution.
* Do not add listener tags or attributes to already subscribed callbacks.
  If the event belongs to a dedicated dispatcher, use that dispatcher's
  registration mechanism and inspect it separately.

Symfony subscribers also receive the repository's `app.stopwatch` tag and
participate in `StopwatchCompilerPass` decoration. Preserve this instrumentation
when updating services; do not assume every listener receives identical
decoration.

## Doctrine lifecycle events [ᐞ](#table-of-contents)

<a id="doctrine-lifecycle-events"></a>

`UserEntityEventListener` is registered in
`config/packages/event_listeners.yaml` with `doctrine.event_listener` tags for
`prePersist` and `preUpdate`. Despite its name, it is a global ORM lifecycle
listener that checks the object is a `User`, not an entity-specific listener
attached through ORM entity-listener mapping.

Use this existing registration pattern for comparable persistence hooks.
Symfony's `kernel.event_listener`, `AsEventListener`, and
`EventSubscriberInterface` do not register Doctrine lifecycle callbacks.
Do not introduce legacy Doctrine subscribers; check the installed
DoctrineBundle's supported APIs in `composer.json` and the implementation.
Entity-specific listeners are a different option and require their own ORM
mapping and service registration; do not silently mix the two approaches.

Check entity type, event argument types, connection scope, transaction timing,
and UnitOfWork/change-set rules. In particular, changes made during an update
must be reflected in the persisted change set. Never recursively call
`flush()` from a flush-triggered listener, and do not assume a lifecycle event
means the database transaction has committed.

`DoctrineExtensionSubscriber` is different: it implements Symfony's subscriber
interface and reacts to `RequestEvent` to configure Gedmo blameable behavior.
Its name does not make it a Doctrine event subscriber.

## Ordering and handler responsibilities [ᐞ](#table-of-contents)

<a id="ordering-and-handler-responsibilities"></a>

* Higher Symfony priorities run earlier; the default is `0`. Inspect nearby
  handlers before choosing or changing a priority. For example,
  `AcceptLanguageSubscriber` uses `100` so locale selection happens early
  enough for translation. Do not depend on registration order at equal
  priorities for required sequencing.
* Decide whether HTTP behavior applies to the main request, subrequests, or
  both. Use `isMainRequest()` where the requirement calls for it rather than
  applying a blanket filter to every handler.
* Keep handlers focused on adapting events and delegate business rules to
  resources or services. Preserve validation and authorization, and do not
  expose credentials or sensitive event payloads in responses or logs.
* Keep request-specific data out of shared service properties. Preserve
  response mutation, exception handling, and propagation behavior when
  updating handlers. Stop propagation only when the event contract and
  required behavior call for it.
* Events execute synchronously unless a separate asynchronous mechanism is
  explicitly configured. `kernel.terminate` is not a durable background job
  queue. Do not hide exceptions or add external side effects without defining
  their failure and transaction behavior.

## Testing and inspection [ᐞ](#table-of-contents)

<a id="testing-and-inspection"></a>

Follow [the testing guide](TESTING.md) and run commands inside the PHP
development container. Match the existing unit and integration test layout,
but do not rely only on calling a callback directly.

* Assert subscriber event maps, callback names, and priorities, as in
  `tests/Unit/EventSubscriber/AcceptLanguageSubscriberTest.php`.
* Test observable handler behavior, relevant no-op paths, invalid input, and
  failures. Include main/subrequests, ordering, propagation, and authorization
  outcomes when they matter.
* For Symfony registration changes, dispatch through the container's actual
  dispatcher and assert the handler's effect and intended invocation count.
  Use an HTTP test when the behavior depends on kernel or security sequencing.
* For Doctrine changes, persist/update and flush through the ORM, then reload
  the entity to verify the stored result. Direct callback tests do not prove
  that Doctrine registered the listener or persisted its changes.

For Symfony events, inspect registrations and priorities with:

```bash
bin/console debug:event-dispatcher kernel.request
bin/console lint:container
```

Replace `kernel.request` with the affected event name; use `--dispatcher` for a
dedicated dispatcher. Symfony's dispatcher inspection does not list Doctrine
lifecycle registrations.

## AI skills [ᐞ](#table-of-contents)

<a id="ai-skills"></a>

* [Create or update an event listener](../.github/skills/add-event-listener/SKILL.md)
* [Create or update an event subscriber](../.github/skills/add-event-subscriber/SKILL.md)

Both skills supplement the shared repository instructions and include
registration and caller-boundary testing requirements.

---

[Back to previous](README.md)

[Back to main README.md](../README.md)
