<?php
declare(strict_types = 1);
/**
 * /src/Request/ParamConverter/RestResourceConverter.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Request\ParamConverter;

use App\Resource\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/** @noinspection AnnotationMissingUseInspection */
/** @noinspection PhpUndefinedClassInspection */
/**
 * Class RestResourceConverter
 *
 * This is meant to be used within controller actions that uses @ParamConverter annotation. Example:
 *  /**
 *   * @Route(
 *   *    "/{userEntity}",
 *   *    requirements={
 *   *        "userEntity" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
 *   *    }
 *   * )
 *   *
 *   * @ParamConverter(
 *   *      "userEntity",
 *   *      class="App\Resource\UserResource",
 *   *  )
 *   *
 *   * @param User $collection
 *   *\/
 *  public function testAction(User $userEntity)
 *  {
 *      ...
 *  }
 *
 * Purpose of this param converter is to use exactly same methods and workflow as in basic REST API requests.
 *
 * @package App\Request\ParamConverter
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestResourceConverter implements ParamConverterInterface
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * RestResourceConverter constructor.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request        $request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     *
     * @throws Throwable
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $name = $configuration->getName();
        $identifier = (string)$request->attributes->get($name, '');
        $resource = $this->collection->get($configuration->getClass());

        if ($identifier !== '') {
            $request->attributes->set($name, $resource->findOne($identifier, true));
        }

        return true;
    }

    /**
     * Checks if the object is supported.
     *
     * @param ParamConverter $configuration
     *
     * @return bool
     */
    public function supports(ParamConverter $configuration): bool
    {
        return $this->collection->has($configuration->getClass());
    }
}
