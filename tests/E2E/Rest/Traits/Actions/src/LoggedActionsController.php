<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/src/LoggedActionsController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions\src;

use App\DTO\User\UserCreate;
use App\DTO\User\UserPatch;
use App\DTO\User\UserUpdate;
use App\Rest\Controller;
use App\Rest\Traits\Actions\Logged as Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LoggedActionsController
 *
 * @Route(
 *     path="/test_logged_actions",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")

 *
 * @package App\Tests\E2E\Rest\Traits\Actions\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggedActionsController extends Controller
{
    use Actions\CountAction;
    use Actions\CreateAction;
    use Actions\DeleteAction;
    use Actions\FindAction;
    use Actions\FindOneAction;
    use Actions\IdsAction;
    use Actions\UpdateAction;

    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => UserCreate::class,
        Controller::METHOD_UPDATE => UserUpdate::class,
        Controller::METHOD_PATCH => UserPatch::class,
    ];
}
