<?php
declare(strict_types = 1);
/**
 * /src/Controller/Localization/LanguageController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Controller\Localization;

use App\Service\Localization;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LanguageController
 *
 * @Route(
 *     path="/localization/language",
 *     methods={"GET"}
 *  )
 *
 * @OA\Tag(name="Localization")
 *
 * @package App\Controller\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LanguageController
{
    private Localization $localization;

    /**
     * LanguageController constructor.
     */
    public function __construct(Localization $localization)
    {
        $this->localization = $localization;
    }

    /**
     * Endpoint action to get supported languages. This is for use to choose
     * what language your frontend application can use within its translations.
     *
     * @OA\Response(
     *      response=200,
     *      description="List of language strings.",
     *      @OA\Schema(
     *          type="array",
     *          example={"en","fi"},
     *          @OA\Items(type="string"),
     *      ),
     *  )
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->localization->getLanguages());
    }
}
