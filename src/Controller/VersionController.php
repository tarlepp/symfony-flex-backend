<?php
declare(strict_types = 1);
/**
 * /src/Controller/VersionController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller;

use App\Service\Version;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class VersionController
 *
 * @package App\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class VersionController
{
    private Version $version;

    /**
     * VersionController constructor.
     */
    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    /**
     * Route for get API version.
     *
     * @Route(
     *     path="/version",
     *     methods={"GET"}
     *  )
     *
     * @OA\Response(
     *      response=200,
     *      description="success",
     *      @OA\Schema(
     *          type="object",
     *          example={"version": "1.2.3"},
     *          @OA\Property(property="version", type="string", description="Version number"),
     *      ),
     *  )
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['version' => $this->version->get()]);
    }
}
