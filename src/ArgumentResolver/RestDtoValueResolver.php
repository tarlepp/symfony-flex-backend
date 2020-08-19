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
use AutoMapperPlus\AutoMapperInterface;
use AutoMapperPlus\Exception\UnregisteredMappingException;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use function count;
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
    private const CONTROLLER_KEY = '_controller';

    /**
     * @var array<int, string>
     */
    private array $supportedActions = [
        Controller::ACTION_CREATE,
        Controller::ACTION_UPDATE,
        Controller::ACTION_PATCH,
    ];

    /**
     * @var array<string, string>
     */
    private array $actionMethodMap = [
        Controller::ACTION_CREATE => Controller::METHOD_CREATE,
        Controller::ACTION_UPDATE => Controller::METHOD_UPDATE,
        Controller::ACTION_PATCH => Controller::METHOD_PATCH,
    ];

    private ControllerCollection $controllerCollection;
    private AutoMapperInterface $autoMapper;

    /**
     * RestDtoValueResolver constructor.
     */
    public function __construct(ControllerCollection $controllerCollection, AutoMapperInterface $autoMapper)
    {
        $this->controllerCollection = $controllerCollection;
        $this->autoMapper = $autoMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (count(explode('::', (string)$request->attributes->get(self::CONTROLLER_KEY))) !== 2) {
            return false;
        }

        [$controllerName, $actionName] = explode('::', (string)$request->attributes->get(self::CONTROLLER_KEY));

        return $argument->getType() === RestDtoInterface::class
            && in_array($actionName, $this->supportedActions, true)
            && $this->controllerCollection->has($controllerName);
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnregisteredMappingException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        [$controllerName, $actionName] = explode('::', (string)$request->attributes->get(self::CONTROLLER_KEY));

        $dtoClass = $this->controllerCollection
            ->get($controllerName)
            ->getDtoClass((string)$this->actionMethodMap[$actionName]);

        yield $this->autoMapper->map($request, $dtoClass);
    }
}
