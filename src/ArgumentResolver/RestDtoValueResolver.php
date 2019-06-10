<?php
declare(strict_types = 1);
/**
 * /src/ArgumentResolver/RestDtoValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\ArgumentResolver;

use App\DTO\RestDtoInterface;
use App\Rest\Controller;
use App\Rest\ControllerCollection;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use function explode;
use function in_array;

/**
 * Class RestDtoValueResolver
 *
 * @package App\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestDtoValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var array|string[]
     */
    private $supportedActions = [
        Controller::ACTION_CREATE,
        Controller::ACTION_UPDATE,
        Controller::ACTION_PATCH,
    ];

    /**
     * @var array|array<string, string>
     */
    private $actionMethodMap = [
        Controller::ACTION_CREATE => Controller::METHOD_CREATE,
        Controller::ACTION_UPDATE => Controller::METHOD_UPDATE,
        Controller::ACTION_PATCH => Controller::METHOD_PATCH,
    ];

    /**
     * @var ControllerCollection
     */
    private $controllerCollection;

    /**
     * RestDtoValueResolver constructor.
     *
     * @param ControllerCollection $controllerCollection
     */
    public function __construct(ControllerCollection $controllerCollection)
    {
        $this->controllerCollection = $controllerCollection;
    }

    /**
     * Whether this resolver can resolve the value for the given ArgumentMetadata.
     *
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        [$controllerName, $actionName] = explode('::', $request->attributes->get('_controller'));

        return $argument->getType() === RestDtoInterface::class
            && in_array($actionName, $this->supportedActions, true)
            && $this->controllerCollection->has($controllerName);
    }

    /**
     * Returns the possible value(s).
     *
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return Generator
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        [$controllerName, $actionName] = explode('::', $request->attributes->get('_controller'));

        $dtoClass = $this->controllerCollection->get($controllerName)->getDtoClass($this->actionMethodMap[$actionName]);

        /** @var RestDtoInterface $restDto */
        $restDto = new $dtoClass();

        yield $restDto->createFromRequest($request);
    }
}
