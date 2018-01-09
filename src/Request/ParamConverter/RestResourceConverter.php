<?php
declare(strict_types = 1);
/**
 * /src/Request/ParamConverter/RestResourceConverter.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Request\ParamConverter;

use App\Rest\RestResource;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RestResourceConverter
 *
 * This is meant to be used within controller actions that uses @ParamConverter annotation. Example:
 *
 *  /**
 *   * @ParamConverter(
 *   *      "userEntity",
 *   *      class="App\Resource\UserResource",
 *   *  )
 *   *
 *   * @param User $userEntity
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
class RestResourceConverter implements ParamConverterInterface, ContainerAwareInterface
{
    // Traits
    use ContainerAwareTrait;

    /**
     * RestResourceConverter constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * Stores the object in the request.
     *
     * @param Request        $request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        /** @var RestResource $resource */
        $resource = $this->container->get($configuration->getClass());

        $name = $configuration->getName();

        $request->attributes->set($name, $resource->findOne($request->attributes->get($name), true));

        return true;
    }

    /**
     * Checks if the object is supported.
     *
     * @param ParamConverter $configuration
     *
     * @return bool
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function supports(ParamConverter $configuration): bool
    {
        $resourceName = $configuration->getClass();

        return $this->container->has($resourceName) && $this->container->get($resourceName) instanceof RestResource;
    }
}
