<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Authenticated/CountAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions\Authenticated;

use App\Rest\Traits\Methods\CountMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Trait to add 'countAction' for REST controllers for authenticated users.
 *
 * @see \App\Rest\Traits\Methods\CountMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Authenticated
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait CountAction
{
    use CountMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/count',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function countAction(Request $request): Response
    {
        return $this->countMethod($request);
    }
}
