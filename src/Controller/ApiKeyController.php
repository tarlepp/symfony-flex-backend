<?php
declare(strict_types = 1);
/**
 * /src/Controller/ApiKeyController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller;

use App\DTO\ApiKey\ApiKeyCreate;
use App\DTO\ApiKey\ApiKeyPatch;
use App\DTO\ApiKey\ApiKeyUpdate;
use App\Resource\ApiKeyResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiKeyController
 *
 * @OA\Tag(name="ApiKey Management")
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ApiKeyResource getResource()
 */
#[Route(
    path: '/api_key',
)]
#[Security('is_granted("IS_AUTHENTICATED_FULLY")')]
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
        protected ApiKeyResource $resource,
    ) {
    }
}
