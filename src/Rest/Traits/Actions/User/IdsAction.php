<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/User/IdsAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\User;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\IdsMethod;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait IdsAction
 *
 * Trait to add 'idsAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\IdsMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait IdsAction
{
    // Traits
    use IdsMethod;

    /**
     * @Route("/ids")
     *
     * @Method({"GET"})
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
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function idsAction(Request $request): Response
    {
        return $this->idsMethod($request);
    }
}
