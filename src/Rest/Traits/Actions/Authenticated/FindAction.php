<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Authenticated/FindAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\Authenticated;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\FindMethod;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait FindAction
 *
 * Trait to add 'findAction' for REST controllers for authenticated users.
 *
 * @see \App\Rest\Traits\Methods\FindMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Authenticated
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait FindAction
{
    // Traits
    use FindMethod;

    /**
     * @Route("")
     *
     * @Method({"GET"})
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
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
