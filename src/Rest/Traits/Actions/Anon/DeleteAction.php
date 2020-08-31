<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Anon/DeleteAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Anon;

use App\Rest\Traits\Methods\DeleteMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait DeleteAction
 *
 * Trait to add 'deleteAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\DeleteMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Anon
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait DeleteAction
{
    use DeleteMethod;

    /**
     * @Route(
     *      "/{id}",
     *      requirements={
     *          "id" = "%app.uuid_v1_regex%",
     *      },
     *      methods={"DELETE"},
     *  )
     *
     * @throws Throwable
     */
    public function deleteAction(Request $request, string $id): Response
    {
        return $this->deleteMethod($request, $id);
    }
}
