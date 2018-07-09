<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/User/FindAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\User;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\FindMethod;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait FindAction
 *
 * Trait to add 'findAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\FindMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait FindAction
{
    // Traits
    use FindMethod;

    /**
     * @Route(
     *      path="",
     *      methods={"GET"},
     *  )
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @RestApiDoc()
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws LogicException
     * @throws Throwable
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function findAction(Request $request): Response
    {
        return $this->findMethod($request);
    }
}
