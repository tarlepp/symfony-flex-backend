<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Logged/CreateAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Logged;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\CreateMethod;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use UnexpectedValueException;

/**
 * Trait CreateAction
 *
 * Trait to add 'createAction' for REST controllers for 'ROLE_LOGGED' users.
 *
 * @see \App\Rest\Traits\Methods\CreateMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Logged
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CreateAction
{
    // Traits
    use CreateMethod;

    /**
     * @Route(
     *     path="",
     *     methods={"POST"},
     *  )
     *
     * @Security("is_granted('ROLE_LOGGED')")
     *
     * @RestApiDoc()
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     *
     * @return Response
     *
     * @throws LogicException
     * @throws Throwable
     * @throws UnexpectedValueException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function createAction(Request $request, FormFactoryInterface $formFactory): Response
    {
        return $this->createMethod($request, $formFactory);
    }
}
