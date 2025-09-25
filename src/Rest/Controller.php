<?php
declare(strict_types = 1);
/**
 * /src/Rest/Controller.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest;

use App\Rest\Interfaces\ControllerInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Actions\RestActionBase;
use App\Rest\Traits\RestMethodHelper;
use Override;
use Symfony\Contracts\Service\Attribute\Required;
use UnexpectedValueException;

/**
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @property ?RestResourceInterface $resource
 */
abstract class Controller implements ControllerInterface
{
    use RestActionBase;
    use RestMethodHelper;

    public const string ACTION_COUNT = 'countAction';
    public const string ACTION_CREATE = 'createAction';
    public const string ACTION_DELETE = 'deleteAction';
    public const string ACTION_FIND = 'findAction';
    public const string ACTION_FIND_ONE = 'findOneAction';
    public const string ACTION_IDS = 'idsAction';
    public const string ACTION_PATCH = 'patchAction';
    public const string ACTION_UPDATE = 'updateAction';

    public const string METHOD_COUNT = 'countMethod';
    public const string METHOD_CREATE = 'createMethod';
    public const string METHOD_DELETE = 'deleteMethod';
    public const string METHOD_FIND = 'findMethod';
    public const string METHOD_FIND_ONE = 'findOneMethod';
    public const string METHOD_IDS = 'idsMethod';
    public const string METHOD_PATCH = 'patchMethod';
    public const string METHOD_UPDATE = 'updateMethod';

    protected ?ResponseHandlerInterface $responseHandler = null;

    public function __construct(
        protected readonly RestResourceInterface $resource
    ) {
    }

    /**
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    #[Override]
    public function getResource(): RestResourceInterface
    {
        return $this->resource;
    }

    #[Override]
    public function getResponseHandler(): ResponseHandlerInterface
    {
        return $this->responseHandler ?? throw new UnexpectedValueException('ResponseHandler service not set', 500);
    }

    #[Required]
    #[Override]
    public function setResponseHandler(ResponseHandler $responseHandler): static
    {
        $this->responseHandler = $responseHandler;

        return $this;
    }
}
