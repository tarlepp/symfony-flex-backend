<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Actions/User/CountAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\User;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\CountMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait CountAction
 *
 * Trait to add 'countAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\CountMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CountAction
{
    // Traits
    use CountMethod;

    /**
     * @Route("/count")
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
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function countAction(Request $request): Response
    {
        return $this->countMethod($request);
    }
}
