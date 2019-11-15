<?php
declare(strict_types = 1);
/**
 * /src/ArgumentResolver/EntityValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\ArgumentResolver;

use App\Entity\EntityInterface;
use App\Resource\ResourceCollection;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;
use function is_string;
use function is_subclass_of;

/**
 * Class EntityValueResolver
 *
 * Example how to use this within your controller;
 *  /**
 *   * @Symfony\Component\Routing\Annotation\Route(path="some_path_to_your_route/{user}/{apikey}")
 *   *\/
 *  public function someMethod(\App\Entity\User $user, \App\Entity\ApiKey $apikey): Response
 *  {
 *      ...
 *  }
 *
 * And when you make your request like `GET /some_path_to_your_route/_user_uuid_/_apikey_uuid`
 * you will get those entities to your controller method which are resolved automatically via
 * those entity resource classes.
 *
 * Only thing that you need check is that parameter in your `path` definition matches with
 * method argument name.
 *
 * @package App\ArgumentResolver
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EntityValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var ResourceCollection
     */
    private $resourceCollection;

    /**
     * EntityValueResolver constructor.
     *
     * @param ResourceCollection $resourceCollection
     */
    public function __construct(ResourceCollection $resourceCollection)
    {
        $this->resourceCollection = $resourceCollection;
    }

    /**
     * Whether this resolver can resolve the value for the given ArgumentMetadata.
     *
     * @param Request          $request
     * @param ArgumentMetadata $argument
     *
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return is_string($request->get($argument->getName()))
            && is_subclass_of($argument->getType(), EntityInterface::class)
            && $this->resourceCollection->hasEntityResource($argument->getType());
    }

    /**
     * Returns the possible value(s).
     *
     * @param Request          $request
     * @param ArgumentMetadata $argument
     *
     * @return Generator
     *
     * @throws Throwable
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        yield $this->resourceCollection->getEntityResource($argument->getType())
            ->findOne($request->get($argument->getName()), !$argument->isNullable());
    }
}
