<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Anon/PatchAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\Anon;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\PatchMethod;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;

/**
 * Trait PatchAction
 *
 * Trait to add 'patchAction' for REST controllers for anonymous users.
 *
 * @see \App\Rest\Traits\Methods\PatchMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Root
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait PatchAction
{
    // Traits
    use PatchMethod;

    /**
     * @Route(
     *      "/{id}",
     *      requirements={
     *          "id" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$"
     *      }
     *  )
     *
     * @Method({"PATCH"})
     *
     * @RestApiDoc()
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param string               $id
     *
     * @return Response
     *
     * @throws LogicException
     * @throws UnexpectedValueException
     * @throws Throwable
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function patchAction(Request $request, FormFactoryInterface $formFactory, string $id): Response
    {
        return $this->patchMethod($request, $formFactory, $id);
    }
}
