<?php
declare(strict_types=1);
/**
 * /tests/Functional/Rest/Traits/Actions/src/AnonActionsController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Rest\Traits\Actions\src;

use App\Rest\Controller;
use App\Rest\Traits\Actions\Anon as Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class AnonActionsController
 *
 * @Route(path="/test_anon_actions")
 *
 * @package App\Tests\Functional\Rest\Traits\Actions\src
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AnonActionsController extends Controller
{
    use Actions\CountAction;
    use Actions\CreateAction;
    use Actions\DeleteAction;
    use Actions\FindAction;
    use Actions\FindOneAction;
    use Actions\IdsAction;
    use Actions\UpdateAction;
}
