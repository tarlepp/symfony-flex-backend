<?php
declare(strict_types=1);
/**
 * /tests/E2E/Rest/Traits/Actions/src/AdminActionsController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest\Traits\Actions\src;

use App\Annotation\RestApiDoc;
use App\Rest\Controller;
use App\Rest\Traits\Actions\Admin as Actions;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class AdminActionsController
 *
 * @Route(
 *     path="/test_admin_actions",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @RestApiDoc(disabled=true)
 *
 * @package App\Tests\E2E\Rest\Traits\Actions\src
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AdminActionsController extends Controller
{
    use Actions\CountAction;
    use Actions\CreateAction;
    use Actions\DeleteAction;
    use Actions\FindAction;
    use Actions\FindOneAction;
    use Actions\IdsAction;
    use Actions\UpdateAction;
}
