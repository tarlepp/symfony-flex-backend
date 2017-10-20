<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Anon/CountAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\Anon;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\CountMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait CountAction
 *
 * Trait to add 'countAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\CountMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Anon
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
