<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/Actions/Anon/FindAction.php
 */

namespace App\Rest\Traits\Actions\Anon;

use App\Rest\Traits\Methods\FindMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Trait to add 'findAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\FindMethod for detailed documents.
 */
trait FindAction
{
    use FindMethod;

    /**
     * @throws Throwable
     */
    #[Route(
        path: '',
        methods: [Request::METHOD_GET],
    )]
    public function findAction(Request $request): Response
    {
        return $this->findMethod($request);
    }
}
