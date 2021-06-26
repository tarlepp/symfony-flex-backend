<?php
declare(strict_types = 1);
/**
 * /src/Controller/UserGroup/UserGroupController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\UserGroup;

use App\DTO\UserGroup\UserGroupCreate;
use App\DTO\UserGroup\UserGroupPatch;
use App\DTO\UserGroup\UserGroupUpdate;
use App\Resource\UserGroupResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

/**
 * Class UserGroupController
 *
 * @OA\Tag(name="UserGroup Management")
 *
 * @package App\Controller\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method UserGroupResource getResource()
 */
#[Route(
    path: '/user_group',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
class UserGroupController extends Controller
{
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\FindOneAction;
    use Actions\Admin\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\DeleteAction;
    use Actions\Root\PatchAction;
    use Actions\Root\UpdateAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => UserGroupCreate::class,
        Controller::METHOD_UPDATE => UserGroupUpdate::class,
        Controller::METHOD_PATCH => UserGroupPatch::class,
    ];

    public function __construct(
        protected UserGroupResource $resource,
    ) {
    }
}
