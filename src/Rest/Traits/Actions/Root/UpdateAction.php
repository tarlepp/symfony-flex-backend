<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Root/UpdateAction.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Actions\Root;

use App\Annotation\RestApiDoc;
use App\Rest\Traits\Methods\UpdateMethod;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Trait UpdateAction
 *
 * Trait to add 'updateAction' for REST controllers for 'ROLE_ROOT' users.
 *
 * @see \App\Rest\Traits\Methods\UpdateMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Root
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
     *          "id" = "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$"
     *      }
     *  )
     *
     * @Method({"PUT"})
     *
     * @Security("has_role('ROLE_ROOT')")
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
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function updateAction(Request $request, FormFactoryInterface $formFactory, string $id): Response
    {
        return $this->updateMethod($request, $formFactory, $id);
    }
}
