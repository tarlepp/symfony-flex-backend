<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Anon/IdsAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Anon;

use App\Rest\Traits\Methods\IdsMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait IdsAction
 *
 * Trait to add 'idsAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\IdsMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Anon
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait IdsAction
{
    use IdsMethod;

    /**
     * @Route(
     *     path="/ids",
     *     methods={"GET"},
     *  )
     *
     * @throws Throwable
     */
    public function idsAction(Request $request): Response
    {
        return $this->idsMethod($request);
    }
}
