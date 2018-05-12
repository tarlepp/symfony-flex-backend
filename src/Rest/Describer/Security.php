<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/Security.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Rest\Doc\RouteModel;
use EXSyst\Component\Swagger\Operation;
use EXSyst\Component\Swagger\Parameter;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SecurityAnnotation;
use function array_filter;
use function array_values;
use function count;

/**
 * Class Security
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Security
{
    /**
     * @var Responses
     */
    private $responses;

    /**
     * ApiDocDescriberRestSecurity constructor.
     *
     * @param Responses $responses
     */
    public function __construct(Responses $responses)
    {
        $this->responses = $responses;
    }

    /**
     * Method to process rest action '@Security' annotation. If this annotation is present we need to following things:
     *  1) Add 'Authorization' header parameter
     *  2) Add 401 response
     *  2) Add 403 response
     *
     * @param Operation  $operation
     * @param RouteModel $routeModel
     *
     * @throws InvalidArgumentException
     */
    public function process(Operation $operation, RouteModel $routeModel): void
    {
        $filter = static function ($annotation): bool {
            return $annotation instanceof SecurityAnnotation;
        };

        if (count(array_values(array_filter($routeModel->getMethodAnnotations(), $filter))) === 1) {
            $parameter = [
                'type' => 'string',
                'name' => 'Authorization',
                'in' => 'header',
                'required' => true,
                'description' => 'Authorization header',
                'default' => 'Bearer _your_jwt_here_',
            ];

            // Add Authorization header parameter
            $operation->getParameters()->add(new Parameter($parameter));

            // Attach 401 and 403 responses
            $this->responses->add401($operation);
            $this->responses->add403($operation);
        }
    }
}
