<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Anon/CountAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Anon;

use App\Rest\Traits\Methods\CountMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait CountAction
 *
 * Trait to add 'countAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\CountMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Anon
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CountAction
{
    use CountMethod;

    /**
     * @Route(
     *     path="/count",
     *     methods={"GET"},
     *  )
     *
     * @throws Throwable
     */
    public function countAction(Request $request): Response
    {
        return $this->countMethod($request);
    }
}
