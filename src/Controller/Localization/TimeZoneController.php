<?php
declare(strict_types = 1);
/**
 * /src/Controller/Localization/TimezoneController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller\Localization;

use App\Service\Localization;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class TimezoneController
 *
 * @Route(
 *     path="/localization/timezone",
 *     methods={"GET"}
 *  )
 *
 * @package App\Controller\Localization
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class TimeZoneController
{
    private Localization $localization;

    /**
     * TimezoneController constructor.
     *
     * @param Localization $localization
     */
    public function __construct(Localization $localization)
    {
        $this->localization = $localization;
    }

    /**
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->localization->getTimeZones());
    }
}
