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

    public const ACTION_COUNT = 'countAction';
    public const ACTION_CREATE = 'createAction';
    public const ACTION_DELETE = 'deleteAction';
    public const ACTION_FIND = 'findAction';
    public const ACTION_FIND_ONE = 'findOneAction';
    public const ACTION_IDS = 'idsAction';
    public const ACTION_PATCH = 'patchAction';
    public const ACTION_UPDATE = 'updateAction';

    public const METHOD_COUNT = 'countMethod';
    public const METHOD_CREATE = 'createMethod';
    public const METHOD_DELETE = 'deleteMethod';
    public const METHOD_FIND = 'findMethod';
    public const METHOD_FIND_ONE = 'findOneMethod';
    public const METHOD_IDS = 'idsMethod';
    public const METHOD_PATCH = 'patchMethod';
    public const METHOD_UPDATE = 'updateMethod';

    protected ?ResponseHandlerInterface $responseHandler = null;

    public function __construct(
        protected readonly RestResourceInterface $resource
    ) {
    }

    public function getResource(): RestResourceInterface
    {
        return $this->resource ?? throw new UnexpectedValueException('Resource service not set', 500);
    }

    public function getResponseHandler(): ResponseHandlerInterface
    {
        return $this->responseHandler ?? throw new UnexpectedValueException('ResponseHandler service not set', 500);
    }

    #[Required]
    public function setResponseHandler(ResponseHandler $responseHandler): static
    {
        $this->responseHandler = $responseHandler;

        return $this;
    }
}
