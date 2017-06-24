<?php
declare(strict_types=1);
/**
 * /src/Controller/UserGroupController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Resource\UserGroupResource;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class UserGroupController
 *
 * @Route(path="/user_group")
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @package App\Controller
 */
class UserGroupController extends Controller
{
    use Actions\Admin\CountAction;
    use Actions\Admin\FindAction;
    use Actions\Admin\FindOneAction;
    use Actions\Admin\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\DeleteAction;
    use Actions\Root\UpdateAction;

    /**
     * UserGroupController constructor.
     *
     * @param UserGroupResource $userGroupResource
     * @param ResponseHandler   $responseHandler
     */
    public function __construct(UserGroupResource $userGroupResource, ResponseHandler $responseHandler)
    {
        $this->init($userGroupResource, $responseHandler);
    }
}
