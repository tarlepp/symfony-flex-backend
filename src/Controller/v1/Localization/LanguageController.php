<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/Localization/LanguageController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\Localization;

use App\Service\Localization;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LanguageController
 *
 * @OA\Tag(name="Localization")
 *
 * @package App\Controller\v1\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LanguageController
{
    public function __construct(
        private Localization $localization,
    ) {
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
    #[Route(
        path: '/v1/localization/language',
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->localization->getLanguages());
    }
}
