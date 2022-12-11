<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/src/LoggedActionsController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions\src;

use App\DTO\User\UserCreate;
use App\DTO\User\UserPatch;
use App\DTO\User\UserUpdate;
use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions\Logged as Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

/**
 * Class LoggedActionsController
 *
 * @package App\Tests\E2E\Rest\Traits\Actions\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[Route(
    path: '/test_logged_actions',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
class LoggedActionsController extends Controller
{
    use Actions\CountAction;
    use Actions\CreateAction;
    use Actions\DeleteAction;
    use Actions\FindAction;
    use Actions\FindOneAction;
    use Actions\IdsAction;
    use Actions\UpdateAction;
    use Actions\PatchAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => UserCreate::class,
        Controller::METHOD_UPDATE => UserUpdate::class,
        Controller::METHOD_PATCH => UserPatch::class,
    ];

    public function __construct(
        UserResource $resource,
    ) {
        parent::__construct($resource);
    }
}
