<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Methods/CreateMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\ControllerInterface;
use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Trait CreateMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CreateMethod
{
    /**
     * Generic 'createMethod' method for REST resources.
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param array|null           $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function createMethod(
        Request $request,
        FormFactoryInterface $formFactory,
        array $allowedHttpMethods = null
    ): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['POST'];

        // Make sure that we have everything we need to make this work
        if (!($this instanceof ControllerInterface)) {
            $message = \sprintf(
                'You cannot use \'%s\' within controller class that does not implement \'%s\'',
                self::class,
                ControllerInterface::class
            );

            throw new \LogicException($message);
        }

        if (!\in_array($request->getMethod(), $allowedHttpMethods, true)) {
            throw new MethodNotAllowedHttpException($allowedHttpMethods);
        }

        /**
         * Lambda function to create form.
         *
         * @param Request              $request
         * @param FormFactoryInterface $formFactory
         * @param string               $method
         *
         * @return FormInterface
         */
        $createForm = function (Request $request, FormFactoryInterface $formFactory, string $method): FormInterface
        {
            $formType = $this->getFormTypeClass($method);

            // Create form and handle request
            $form = $formFactory->createNamed('', $formType, null, ['method' => $request->getMethod()]);
            $form->handleRequest($request);

            return $form;
        };

        try {
            $form = $createForm($request, $formFactory, __METHOD__);

            if (!$form->isValid()) {
                // TODO handle form errors

                throw new HttpException(Response::HTTP_BAD_REQUEST, 'form has errors');
            }

            return $this
                ->getResponseHandler()
                ->createResponse($request, $this->getResource()->create($form->getData()), Response::HTTP_CREATED);
        } catch (\Exception $error) {
            if ($error instanceof HttpException) {
                throw $error;
            }

            $code = $error->getCode() !== 0 ? $error->getCode() : Response::HTTP_BAD_REQUEST;

            throw new HttpException($code, $error->getMessage(), $error, [], $code);
        }
    }

    /**
     * @return ResourceInterface
     *
     * @throws \UnexpectedValueException
     */
    abstract public function getResource(): ResourceInterface;

    /**
     * @return ResponseHandlerInterface
     *
     * @throws \UnexpectedValueException
     */
    abstract public function getResponseHandler(): ResponseHandlerInterface;

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    abstract public function getDtoClass(string $method = null): string;

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    abstract public function getFormTypeClass(string $method = null): string;
}
