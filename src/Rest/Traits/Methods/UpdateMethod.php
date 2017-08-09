<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Methods/UpdateMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait UpdateMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method ResourceInterface getResource()
 * @method ResponseHandlerInterface getResponseHandler()
 */
trait UpdateMethod
{
    /**
     * Generic 'updateMethod' method for REST resources.
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param string               $id
     * @param array|null           $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function updateMethod(
        Request $request,
        FormFactoryInterface $formFactory,
        string $id,
        array $allowedHttpMethods = null
    ): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['PUT'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        /**
         * Lambda function to create form.
         *
         * @param Request              $request
         * @param FormFactoryInterface $formFactory
         * @param string               $id
         * @param string               $method
         *
         * @return FormInterface
         */
        $createForm = function (Request $request, FormFactoryInterface $formFactory, string $id, string $method): FormInterface
        {
            $formType = $this->getFormTypeClass($method);

            // Create form, load entity data for form and handle request
            $form = $formFactory->createNamed('', $formType, null, ['method' => $request->getMethod()]);
            $form->setData($this->getResource()->getDtoForEntity($id, $form->getConfig()->getDataClass()));

            $form->handleRequest($request);

            return $form;
        };

        try {
            $form = $createForm($request, $formFactory, $id, __METHOD__);

            if (!$form->isValid()) {
                $this->getResponseHandler()->handleFormError($form);
            }

            return $this
                ->getResponseHandler()
                ->createResponse($request, $this->getResource()->update($id, $form->getData()));
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
