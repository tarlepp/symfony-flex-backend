<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /src/Controller/<?php echo $controllerName ?>.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Controller;

use App\Resource\<?php echo $resourceName ?>;
use App\Rest\Controller;
use App\Rest\ResponseHandler;
use App\Rest\Traits\Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;

/**
 * @Route(path="<?php echo $routePath ?>")
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @SWG\Tag(name="<?php echo $swaggerTag ?>")
 *
 * @package App\Controller
 * @author  <?php echo $author . "\n" ?>
 *
 * @method <?php echo $resourceName ?> getResource()
 */
class <?php echo $controllerName ?> extends Controller
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
     * <?php echo $controllerName ?> constructor.
     *
     * @param <?php echo $resourceName ?> $resource
     * @param ResponseHandler $responseHandler
     */
    public function __construct(<?php echo $resourceName ?> $resource, ResponseHandler $responseHandler)
    {
        $this->init($resource, $responseHandler);
    }
}
