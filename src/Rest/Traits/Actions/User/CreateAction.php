<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/User/CreateAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\User;

use App\DTO\RestDtoInterface;
use App\Rest\Traits\Methods\CreateMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait CreateAction
 *
 * Trait to add 'createAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\CreateMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CreateAction
{
    use CreateMethod;

    /**
     * @Route(
     *      path="",
     *      methods={"POST"},
     *  )
     *
     * @Security("is_granted('ROLE_USER')")
     *
     * @throws Throwable
     */
    public function createAction(Request $request, RestDtoInterface $restDto): Response
    {
        return $this->createMethod($request, $restDto);
    }
}
