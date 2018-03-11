<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/Tags.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Rest\Doc\RouteModel;
use EXSyst\Component\Swagger\Operation;
use Swagger\Annotations as SWG;
use function array_filter;
use function array_values;
use function count;

/**
 * Class Tags
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Tags
{
    /**
     * Method to process operation 'tags'
     *
     * @param Operation  $operation
     * @param RouteModel $routeModel
     */
    public function process(Operation $operation, RouteModel $routeModel): void
    {
        // Initialize main data array
        $data = [
            'tags' => [],
        ];

        $this->processTags($routeModel, $data);

        // Merge data to operation
        $operation->merge($data);
    }

    /**
     * @param RouteModel $routeModel
     * @param mixed[]    &$data
     */
    private function processTags(RouteModel $routeModel, array &$data): void
    {
        $filter = function ($annotation): bool {
            return $annotation instanceof SWG\Tag;
        };

        $annotations = array_values(array_filter($routeModel->getControllerAnnotations(), $filter));

        // If controller has 'SWG\Tag' annotation we will use that as a tag
        if (count($annotations) === 1) {
            /** @var SWG\Tag $annotation */
            $annotation = $annotations[0];

            $tagName = $annotation->name;

            $data['tags'][] = $tagName;
        }
    }
}
