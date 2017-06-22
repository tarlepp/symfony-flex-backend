<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Actions/Root/CreateAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\Root;

use App\Rest\Traits\Methods\CreateMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait CreateAction
 *
 * Trait to add 'createAction' for REST controllers for 'ROLE_ROOT' users.
 *
 * @see \App\Rest\Traits\Methods\CreateMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Root
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CreateAction
{
    use CreateMethod;

    /**
     * @Route("")
     *
     * @Method({"POST"})
     *
     * @Security("has_role('ROLE_ROOT')")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createAction(Request $request): Response
    {
        return $this->createMethod($request);
    }
}
