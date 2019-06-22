<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Authenticated/UpdateAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Authenticated;

use App\Annotation\RestApiDoc;
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
 * Trait to add 'updateAction' for REST controllers for authenticated users.
 *
 * @see \App\Rest\Traits\Methods\UpdateMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Authenticated
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UpdateAction
{
    // Traits
    use UpdateMethod;

    /**
     * @Route(
     *      "/{id}",
     *      requirements={
     *          "id" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
     *      },
     *      methods={"PUT"},
     *  )
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     *
     * @RestApiDoc()
     *
     * @param Request          $request
     * @param RestDtoInterface $restDto
     * @param string           $id
     *
     * @return Response
     *
     * @throws Throwable
     */
    public function updateAction(Request $request, RestDtoInterface $restDto, string $id): Response
    {
        return $this->updateMethod($request, $restDto, $id);
    }
}
