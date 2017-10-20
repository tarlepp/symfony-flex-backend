<?php
declare(strict_types = 1);
/**
 * /src/Rest/Describer/Responses.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use EXSyst\Component\Swagger\Operation;
use EXSyst\Component\Swagger\Response as SwaggerResponse;

/**
 * Class Responses
 *
 * @package App\Rest\Describer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Responses
{
    /**
     * @param Operation $operation
     */
    public function add401(Operation $operation): void
    {
        $data = [
            'description' => 'Invalid token',
            'examples' => [
                'Token not found' => '{code: 401, message: JWT Token not found}',
                'Expired token' => '{code: 401, message: Expired JWT Token}',
            ],
        ];

        $response = new SwaggerResponse($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set(401, $response);
    }

    /**
     * @param Operation $operation
     */
    public function add403(Operation $operation): void
    {
        $data = [
            'description' => 'Access denied',
            'examples' => [
                'Access denied' => '{message: Access denied., code: 0, status: 403}',
            ],
        ];

        $response = new SwaggerResponse($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set(403, $response);
    }

    /**
     * @param Operation $operation
     */
    public function add404(Operation $operation): void
    {
        $data = [
            'description' => 'Not found',
            'examples' => [
                'Not found' => '{message: Not found, code: 0, status: 404}',
            ],
        ];

        $response = new SwaggerResponse($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set(404, $response);
    }

    /**
     * @param Operation $operation
     * @param string    $description
     * @param int       $statusCode
     * @param string    $entityName
     */
    public function addOk(Operation $operation, string $description, int $statusCode, string $entityName): void
    {
        $data = [
            'description' => \sprintf($description, $entityName),
        ];

        $response = new SwaggerResponse($data);

        /** @noinspection PhpParamsInspection */
        $operation->getResponses()->set($statusCode, $response);
    }
}
