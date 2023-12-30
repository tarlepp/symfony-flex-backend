<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/ApiKey/ApiKeyController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\ApiKey;

use App\DTO\ApiKey\ApiKeyCreate;
use App\DTO\ApiKey\ApiKeyPatch;
use App\DTO\ApiKey\ApiKeyUpdate;
use App\Resource\ApiKeyResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class ApiKeyController
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ApiKeyResource getResource()
 */
#[AsController]
#[Route(
    path: '/v1/api_key',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'ApiKey Management')]
class ApiKeyController extends Controller
{
    use Actions\Root\CountAction;
    use Actions\Root\FindAction;
    use Actions\Root\FindOneAction;
    use Actions\Root\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\DeleteAction;
    use Actions\Root\PatchAction;
    use Actions\Root\UpdateAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => ApiKeyCreate::class,
        Controller::METHOD_UPDATE => ApiKeyUpdate::class,
        Controller::METHOD_PATCH => ApiKeyPatch::class,
    ];

    public function __construct(
        ApiKeyResource $resource,
    ) {
        parent::__construct($resource);
    }
}
