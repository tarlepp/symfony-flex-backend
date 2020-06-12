# What is this?

This document contains information about main concepts and features how this
application is built and how you can use it.

This template application has quite lot features that aren't so clear for
new developers - so this document is trying to solve that issue.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [REST API wtf?](#rest-api-wtf)
    * [Explanation](#explanation)
    * [Base workflow](#base-workflow)
    * [REST traits](#rest-traits)
    * [How to make new REST API?](#how-to-make-new-rest-api)
    * [Alternatives](#alternatives)
  * [Authentication and authorization](#authentication-and-authorization)
    * [Authentication](#authentication)
      * [Normal users](#normal-users)
      * [ApiKey "users"](#apikey-users)
  * [Controllers](#controllers)
  * [Resources](#resources)
    * [Lifecycle callbacks](#lifecycle-callbacks)
  * [Repositories](#repositories)
    * [Parameter handling](#parameter-handling)
    * [Query builder callbacks and joins](#query-builder-callbacks-and-joins)
  * [Argument value resolvers](#argument-value-resolvers)
    * [Entity value resolver](#entity-value-resolver)
    * [Logged in user value resolver](#logged-in-user-value-resolver)
    * [REST DTO value resolver](#rest-dto-value-resolver)
  * [Common helpers](#common-helpers)
    * [LoggerAwareTrait](#loggerawaretrait)
    * [StopwatchAwareTrait](#stopwatchawaretrait)

## REST API wtf?

This application provides a REST API that you can easily use - and now you
might be asking question - why, isn't there a ton of those already?

### Explanation

When I started with this "project" I first tried `FOSRestBundle` and after that
`API Platform` and neither of those wasn't "good" enough for my needs for REST
API - note that specially `API Platform` has evolved since that a lot.

How I have make this application to work, differs quite lot from both of those.
The main points to make this from scratch are following things;

* Easy to use _"generic"_ API for entities
* Using DTOs instead of form types
* Time based UUID as `id` instead of normal `autoinc id`
* Normalized development environment and IDE (PhpStorm) settings
* Easy use of Xdebug
* Docker support for development and production setups
* Separate user entity from security context
* Authentication
  * User with JWT for normal applications
  * ApiKey authentication for server-to-server communication
* Code and type coverage
* Provide common tools for development process that are easy to use
* Role based REST actions - this will cover like 99% use cases in real world
* DTO usage
* Automatic API documentation _(work in progress...)_
* Total control how your database queries are executed (avoiding `n+1` problem,
  etc.)
* And the main thing - to learn how to use Symfony

### Base workflow

Within this application the base workflow is following, when we're talking
about "big" sections at time;

`Controller/Command <--> Resource <--> Repository`

So the main idea is to use _generic_ `resource` service(s) to control your
application workflow. This resource is just one more layer within your
application where you can control your request / response handling as you like.

### REST traits

Application relies traits within those basic CRUD actions. You can easily
attach any of following generic trait to your application controller;

* use Actions\_{role}_\CountAction
* use Actions\_{role}_\FindAction
* use Actions\_{role}_\FindOneAction
* use Actions\_{role}_\IdsAction
* use Actions\_{role}_\CreateAction
* use Actions\_{role}_\DeleteAction
* use Actions\_{role}_\PatchAction
* use Actions\_{role}_\UpdateAction

Where you can use that _{role}_ to specify which role user needs to have
to access that action.

### How to make new REST API?

To create new REST API from scratch you need to add following parts to your
application;

* Entity
* DTO(s)
* Repository
* Resource
* Controller
* +necessary test classes for all those above

And that those all has bind together, you should see clear example of this
structure if you look how `ApiKey` endpoint has been built to this application.

## Authentication and authorization

### Authentication

By default this application is providing a "normal" user and "apikey"
authentication implementations. Another quite _big_ difference to traditional
Symfony  applications is that this application does not bind `Entities` to
`Symfony\Component\Security\Core\User\UserInterface` - this application uses
separated DTO's for that.

#### Normal users

These users are authenticated via `Json Web Token (JWT)`, each JWT is created
using private/public keys and those keys are always re-generated when
environment is started. This will ensure that eg. each application update
users needs to make new login - this can be changed if needed but imho this is
most _safest_ way to handle token invalidation on application updates.

Also note that if/when you want to use something else as user provider than
your application database, you just need to change `SecurityUserFactory`
implementation and/or create your own one.

#### ApiKey "users"

This authentication is used within `server-to-server` communication between
"trusted" parties. These "ApiKey" users are authenticated via special HTTP
request header;

```text
Authorization: ApiKey _APIKEY_TOKEN_HERE_
```

## Controllers

If your application is providing just a basic REST API for CRUD actions, then
like 99% your controllers would look like something below;

```php
<?php

namespace App\Controller;

use App\DTO\FooBarDtoCreate;
use App\DTO\FooBarDtoUpdate;
use App\DTO\FooBarDtoPatch;
use App\Rest\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/foo-bar")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @method FooBarResource getResource()
 */
class FooBarController extends Controller
{
    use Actions\Root\CountAction;
    use Actions\Root\FindAction;
    use Actions\Root\FindOneAction;
    use Actions\Root\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\DeleteAction;
    use Actions\Root\PatchAction;
    use Actions\Root\UpdateAction;

    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => FooBarDtoCreate::class,
        Controller::METHOD_UPDATE => FooBarDtoUpdate::class,
        Controller::METHOD_PATCH => FooBarDtoPatch::class,
    ];

    public function __construct(FooBarResource $resource)
    {
        $this->resource = $resource;
    }
}
```

Controller with this structure is giving you 100% CRUD operations that your
application will provide within that simple controller.

### Alternatives

You should know that there is also another solutions for REST API, that you
really should look/try also.

* [API Platform](https://api-platform.com/)
* [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle)

Another note is that you might benefit a lot within your skill set if you
build REST API from scratch - within that process you will learn basically
the main aspects of Symfony based applications (security, controllers, dto,
request/response handling, etc.).

## Resources

Resource services are layer between your controller/command and repository.
Within this layer you can control how to `mutate` that repository data for
your needs etc. Also this resource layer is providing following base methods
that you can easily use within your application;

* getRepository(): BaseRepositoryInterface;
* getValidator(): ValidatorInterface;
* getDtoForEntity(string $id, string $dtoClass, RestDtoInterface $dto, ?bool
  $patch = null): RestDtoInterface;
* find(?array $criteria = null, ?array $orderBy = null, ?int $limit = null,
  ?int $offset = null, ?array $search = null): array
* findOne(string $id, ?bool $throwExceptionIfNotFound = null): ?EntityInterface
* findOneBy(array $criteria, ?array $orderBy = null, ?bool
  $throwExceptionIfNotFound = null): ?EntityInterface
* count(?array $criteria = null, ?array $search = null): int
* create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation =
  null): EntityInterface
* update(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool
  $skipValidation = null): EntityInterface
* patch(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool
  $skipValidation = null): EntityInterface
* delete(string $id, ?bool $flush = null): EntityInterface
* getIds(?array $criteria = null, ?array $search = null): array
* save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation
  = null): EntityInterface

Methods itself should be quite self-explanatory, but you can read detailed
information about those from
[this interface](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/Rest/RestResourceInterface.php).

These resource services are the main backbone of your application.

### Lifecycle callbacks

Using resource services give you ability to use `lifecycle callbacks` that
give you extra layer to control how your requests are handled within your
application. These lifecycle callbacks are basically just middleware that are
attached to following resource methods;

* find
* findOne
* findOneBy
* count
* ids
* create
* update
* patch
* delete
* save

Each of these you can use `before` and `after` methods within your resource
service to control your request and response as you like. For more information
about these see [this](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/Rest/Traits/RestResourceLifeCycles.php).

## Repositories

Repositories within this application are much like the same as Symfony
itself provides by default, but there is couple of extra features within those;

### Parameter handling

This application has builtin generic parameter handling to help you with
generic REST queries. Handling of following generic parameters has been
attached to _all_ Advanced `find/findBy/count/ids` methods (some of these
are replacements for what doctrine itself is providing)

* Handling for `where` parameter, within this you can easily create quite
  complex custom queries - see
  [this](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/Rest/RequestHandler.php#L63)
  for examples.
* Handling for `order` parameter, which you can use to order results
  easily - see
  [this](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/Rest/RequestHandler.php#L104)
  for examples.
* Handling for `limit/offset` parameters, which you can use for paginator
  features within your application - see
  [this](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/Rest/RequestHandler.php#L128)
  and
  [this](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/Rest/RequestHandler.php#L145)
  for examples.
* Handling for `search` parameter, which you can use to make generic search
  for your data out of the box - see
  [this](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/Rest/RequestHandler.php#L169)
  for examples.
* Handling for `populate` parameter, which you can use to change the context
  of serialization groups.

Note that within base REST CRUD actions these parameters have been passed
through from `resource` service to repository level.

### Query builder callbacks and joins

Usually common use case for generic REST API is to add custom callbacks to
specified query that is going to be executed and this application provides you
necessary tools to do that and within these custom callbacks you usually need
some joins attached to query itself.

These are usually attached to via [lifecycle callbacks](#lifecycle-callbacks)
where you do eg. something like this;

```php
$callback = static function (QueryBuilder $queryBuilder): void {
    $queryBuilder
        ->addSelect([
            'foo',
            'bar',
            'foobar',
        ]);
};

// Attach callback and necessary joins
$this->getRepository()
    ->addCallback($callback)
    ->addInnerJoin(['entity.foo', 'foo'])
    ->addInnerJoin(['entity.bar', 'bar'])
    ->addLeftJoin(['bar.foobar', 'foobar']);
```

So eg. in this use case code above is executed via `beforeFind` and
`beforeFindOne` lifecycle callbacks. And what is happening there is that on
those queries we're _automatically_ attaching three (3) joins to query and
adding selects to three (3) another entities so that there isn't Doctrine
lazy loading with those.

This will save some serious time on request / response processing + it'll
just make one query instead of `1+n` queries.

## Argument value resolvers

Application has following builtin argument resolvers that you can use to build
your own application.

[Symfony documentation](https://symfony.com/doc/current/controller/argument_value_resolver.html)

### Entity value resolver

This is basically replacement for [SensioFrameworkExtraBundle](https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle)
`@ParamConverter` annotation, so that you can easily inject _any_ entity to
your controller action method which will use specified `resource` method to
resolve that entity value.

And because this is using specified `resource` service you can easily use
those [lifecycle callbacks](#lifecycle-callbacks) as you like.

[source](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/ArgumentResolver/EntityValueResolver.php)

### Logged in user value resolver

Because this application has separated user `entity` totally from actual
firewall user - you can use this resolver to inject actual logged in user
entity to your controller.

[source](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/ArgumentResolver/LoggedInUserValueResolver.php)

### REST DTO value resolver

This resolver is used within following generic REST CRUD routes;

* POST /some-endpoint, creating a new entity
* PUT /some-endpoint/some-id, updating existing entity
* PATCH /some-endpoint/some-id, patching existing entity

The purpose of this resolver is to convert user `request` to corresponding DTO
object so that resource service can process those requests correctly.

This resolver is using [AutoMapperPlusBundle](https://github.com/mark-gerarts/automapper-plus-bundle)
to make actual mapping from request to corresponding DTO object.

[source](https://github.com/tarlepp/symfony-flex-backend/blob/master/src/ArgumentResolver/RestDtoValueResolver.php)

## Common helpers

This application has builtin common helper traits that you can easily to use
everywhere in your application.

_**Note** that you should use these only to help you to debug your application
quickly - these helpers are not mentioned to be use in your production code!!!
eg. if you need `LoggerInterface` in your service, just use DI to inject that
to your service._

### LoggerAwareTrait

Within this trait you can easily inject _your_ `logger` to any service that
you've in your application.

```php
<?php

class SomeService
{
    use \App\Helpers\LoggerAwareTrait;

    public function fooBar(): void
    {
        $this->logger->info('some log message');
    }
}
```

After that you can just use `$this->logger` in your service to log what you
need.

### StopwatchAwareTrait

This is used to easy up your application debug process.

```php
<?php

class SomeService
{
    use \App\Helpers\StopwatchAwareTrait;

    public function fooBar(): void
    {
        $this->stopwatch->start('some log');
        // Some code that you need to debug
        $this->stopwatch->stop('some log');
    }
}
```

With this example you can see how much your custom code will take time to
process and you can see this easily from your profiler.

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
