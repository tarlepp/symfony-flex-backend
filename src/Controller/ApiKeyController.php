<?php
declare(strict_types = 1);
/**
 * /src/Controller/ApiKeyController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller;

use App\Resource\ApiKeyResource;
use App\Rest\Controller;
use App\Rest\Traits\Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiKeyController
 *
 * @Route(
 *     path="/api_key",
 *  )
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @SWG\Tag(name="ApiKey Management")
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method ApiKeyResource getResource()
 */
class ApiKeyController extends Controller
{
    // Traits for REST actions
    use Actions\Root\CountAction;
    use Actions\Root\FindAction;
    use Actions\Root\FindOneAction;
    use Actions\Root\IdsAction;
    use Actions\Root\CreateAction;
    use Actions\Root\DeleteAction;
    use Actions\Root\PatchAction;
    use Actions\Root\UpdateAction;

    /**
     * ApiKeyController constructor.
     *
     * @param ApiKeyResource $resource
     */
    public function __construct(ApiKeyResource $resource)
    {
        $this->resource = $resource;
    }
}
