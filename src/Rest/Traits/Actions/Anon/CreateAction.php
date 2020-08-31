<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Anon/CreateAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Anon;

use App\DTO\RestDtoInterface;
use App\Rest\Traits\Methods\CreateMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait CreateAction
 *
 * Trait to add 'createAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\CreateMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Anon
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CreateAction
{
    use CreateMethod;

    /**
     * @Route(
     *     path="",
     *     methods={"POST"},
     *  )
     *
     * @throws Throwable
     */
    public function createAction(Request $request, RestDtoInterface $restDto): Response
    {
        return $this->createMethod($request, $restDto);
    }
}
