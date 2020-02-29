<?php
declare(strict_types = 1);
/**
 * /src/Controller/Localization/LocaleController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller\Localization;

use App\Service\Localization;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LocaleController
 *
 * @Route(
 *     path="/localization/locale",
 *     methods={"GET"}
 *  )
 *
 * @SWG\Tag(name="Localization")
 *
 * @package App\Controller\Localization
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LocaleController
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
     * Endpoint action to get supported locales. This is for use to choose what
     * locale your frontend application can use within its number, time, date,
     * datetime, etc. formatting.
     *
     * @SWG\Response(
     *      response=200,
     *      description="List of locale strings.",
     *      @SWG\Schema(
     *          type="array",
     *          example={"en","fi"},
     *          @SWG\Items(type="string"),
     *      ),
     *  )
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->localization->getLocales());
    }
}
