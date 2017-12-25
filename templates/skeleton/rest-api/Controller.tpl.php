<?= "<?php\n" ?>
declare(strict_types = 1);
/**
 * /src/Controller/<?= $controllerName ?>.php
 *
 * @author  <?= $author . "\n" ?>
 */
namespace App\Controller;

use App\Resource\<?= $resourceName ?>;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;

/**
 * @Route(path="<?= $routePath ?>")
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @SWG\Tag(name="<?= $swaggerTag ?>")
 *
 * @package App\Controller
 * @author  <?= $author . "\n" ?>
 *
 * @method <?= $resourceName ?> getResource()
 */
class <?= $controllerName ?> extends Controller
{
    // Traits for REST actions
    use Actions\User\CountAction;
    use Actions\User\FindAction;
    use Actions\User\FindOneAction;
    use Actions\User\IdsAction;
    use Actions\User\CreateAction;
    use Actions\User\DeleteAction;
    use Actions\User\PatchAction;
    use Actions\User\UpdateAction;

    /**
     * <?= $controllerName ?> constructor.
     *
     * @param <?= $resourceName ?> $resource
     * @param ResponseHandler $responseHandler
     */
    public function __construct(<?= $resourceName ?> $resource, ResponseHandler $responseHandler)
    {
        $this->init($resource, $responseHandler);
    }
}
