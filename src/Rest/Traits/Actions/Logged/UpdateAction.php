<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Logged/UpdateAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Logged;

use App\DTO\RestDtoInterface;
use App\Rest\Traits\Methods\UpdateMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait UpdateAction
 *
 * Trait to add 'updateAction' for REST controllers for 'ROLE_LOGGED' users.
 *
 * @see \App\Rest\Traits\Methods\UpdateMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Logged
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UpdateAction
{
    use UpdateMethod;

    /**
     * @Route(
     *      "/{id}",
     *      requirements={
     *          "id" = "%app.uuid_v1_regex%",
     *      },
     *      methods={"PUT"},
     *  )
     *
     * @Security("is_granted('ROLE_LOGGED')")
     *
     * @throws Throwable
     */
    public function updateAction(Request $request, RestDtoInterface $restDto, string $id): Response
    {
        return $this->updateMethod($request, $restDto, $id);
    }
}
