<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/src/UserActionsController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest\Traits\Actions\src;

use App\Annotation\RestApiDoc;
use App\Rest\Controller;
use App\Rest\Traits\Actions\User as Actions;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class UserActionsController
 *
 * @Route(
 *     path="/test_user_actions",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @RestApiDoc(disabled=true)
 *
 * @package App\Tests\E2E\Rest\Traits\Actions\src
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserActionsController extends Controller
{
    use Actions\CountAction;
    use Actions\CreateAction;
    use Actions\DeleteAction;
    use Actions\FindAction;
    use Actions\FindOneAction;
    use Actions\IdsAction;
    use Actions\UpdateAction;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
    }
}
