<?php
declare(strict_types = 1);
/**
 * /src/ValueResolver/RestDtoValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\ValueResolver;

use App\DTO\RestDtoInterface;
use App\Rest\Controller;
use App\Rest\ControllerCollection;
use AutoMapperPlus\AutoMapperInterface;
use Generator;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;
use function count;
use function explode;
use function in_array;

/**
 * @package App\ValueResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestDtoValueResolver implements ValueResolverInterface
{
    private const string CONTROLLER_KEY = '_controller';

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

    private ?string $controllerName = null;
    private ?string $actionName = null;

    /**
     * @param ControllerCollection<Controller> $controllerCollection
     */
    public function __construct(
        private readonly ControllerCollection $controllerCollection,
        private readonly AutoMapperInterface $autoMapper,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $bits = explode('::', (string)$request->attributes->get(self::CONTROLLER_KEY, ''));

        if (count($bits) !== 2) {
            return false;
        }

        [$controllerName, $actionName] = $bits;

        $output = $argument->getType() === RestDtoInterface::class
            && in_array($actionName, $this->supportedActions, true)
            && $this->controllerCollection->has($controllerName);

        if ($output === true) {
            $this->controllerName = $controllerName;
            $this->actionName = $actionName;
        }

        return $output;
    }

    /**
     * @throws Throwable
     *
     * @return Generator<RestDtoInterface>
     */
    #[Override]
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if (!$this->supports($request, $argument) || $this->controllerName === null || $this->actionName === null) {
            return [];
        }

        $actionName = $this->actionName;

        $dtoClass = $this->controllerCollection
            ->get($this->controllerName)
            ->getDtoClass($this->actionMethodMap[$actionName] ?? null);

        yield $this->autoMapper->map($request, $dtoClass);
    }
}
