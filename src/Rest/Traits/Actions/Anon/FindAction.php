<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Anon/FindAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Anon;

use App\Rest\Traits\Methods\FindMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait FindAction
 *
 * Trait to add 'findAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\FindMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Anon
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait FindAction
{
    use FindMethod;

    /**
     * @Route(
     *     path="",
     *     methods={"GET"},
     *  )
     *
     * @throws Throwable
     */
    public function findAction(Request $request): Response
    {
        return $this->findMethod($request);
    }
}
