<?php
declare(strict_types = 1);
/**
 * /src/Controller/Localization/TimezoneController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\Localization;

use App\Service\Localization;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class TimezoneController
 *
 * @OA\Tag(name="Localization")
 *
 * @package App\Controller\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class TimeZoneController
{
    public function __construct(
        private Localization $localization,
    ) {
    }

    /**
     * Endpoint action to get list of supported timezones. This is for use to
     * choose what timezone your frontend application can use within its date,
     * time, datetime, etc. formatting.
     *
     * @OA\Response(
     *      response=200,
     *      description="List of timezone objects.",
     *      @OA\Schema(
     *          type="array",
     *          @OA\Items(
     *              type="object",
     *              @OA\Property(
     *                  property="timezone",
     *                  type="string",
     *                  example="Europe",
     *                  description="Africa,America,Antarctica,Arctic,Asia,Atlantic,Australia,Europe,Indian,Pacific,UTC.",
     *              ),
     *              @OA\Property(
     *                  property="identier",
     *                  type="string",
     *                  example="Europe/Helsinki",
     *                  description="Timezone identifier that you can use with other librariers.",
     *              ),
     *              @OA\Property(
     *                  property="offset",
     *                  type="string",
     *                  example="GMT+2:00",
     *                  description="GMT offset of identifier.",
     *              ),
     *              @OA\Property(
     *                  property="value",
     *                  type="string",
     *                  example="Europe/Helsinki",
     *                  description="User friendly value of identifier value eg. '_' characters are replaced by space.",
     *              ),
     *          ),
     *      ),
     *  )
     *
     * @throws Throwable
     */
    #[Route(
        path: '/localization/timezone',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->localization->getTimezones());
    }
}
