<?php
declare(strict_types = 1);
/**
 * /src/Controller/v1/Localization/TimezoneController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Controller\v1\Localization;

use App\Service\Localization;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @package App\Controller\v1\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsController]
#[OA\Tag(name: 'Localization')]
class TimeZoneController
{
    public function __construct(
        private readonly Localization $localization,
    ) {
    }

    /**
     * Endpoint action to get list of supported timezones. This is for use to
     * choose what timezone your frontend application can use within its date,
     * time, datetime, etc. formatting.
     */
    #[Route(
        path: '/v1/localization/timezone',
        methods: [Request::METHOD_GET],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of timezone objects.',
        content: new JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(
                        property: 'timezone',
                        description: 'Timezone group (Africa, America, Antarctica, Arctic, Asia, Atlantic, etc.).',
                        type: 'string',
                        example: 'Europe',
                    ),
                    new OA\Property(
                        property: 'identifier',
                        description: 'Timezone identifier that you can use with other libraries.',
                        type: 'string',
                        example: 'Europe/Helsinki',
                    ),
                    new OA\Property(
                        property: 'offset',
                        description: 'GMT offset of identifier.',
                        type: 'string',
                        example: 'GMT+2:00',
                    ),
                    new OA\Property(
                        property: 'value',
                        description: 'User friendly identifier value (underscores replaced with spaces).',
                        type: 'string',
                        example: 'Europe/Helsinki',
                    ),
                ],
                type: 'object'
            ),
            example: [
                'timezone' => 'Europe',
                'identifier' => 'Europe/Helsinki',
                'offset' => 'GMT+2:00',
                'value' => 'Europe/Helsinki',
            ],
        ),
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->localization->getTimezones());
    }
}
