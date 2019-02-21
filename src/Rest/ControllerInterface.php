<?php
declare(strict_types = 1);
/**
 * /src/Rest/ControllerInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use LogicException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnexpectedValueException;

/**
 * Interface ControllerInterface
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ControllerInterface
{
    /**
     * @required
     *
     * @param ResponseHandler $responseHandler
     *
     * @return ControllerInterface|Controller|self
     */
    public function setResponseHandler(ResponseHandler $responseHandler);

    /**
     * @return RestResourceInterface
     *
     * @throws UnexpectedValueException
     */
    public function getResource(): RestResourceInterface;

    /**
     * @return ResponseHandlerInterface
     *
     * @throws UnexpectedValueException
     */
    public function getResponseHandler(): ResponseHandlerInterface;

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getDtoClass(?string $method = null): string;

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getFormTypeClass(?string $method = null): string;

    /**
     * Method to validate REST trait method.
     *
     * @param Request  $request
     * @param string[] $allowedHttpMethods
     *
     * @throws LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function validateRestMethod(Request $request, array $allowedHttpMethods): void;

    /**
     * Method to handle possible REST method trait exception.
     *
     * @param Throwable $exception
     *
     * @return Throwable
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handleRestMethodException(Throwable $exception): Throwable;

    /**
     * Method to process current criteria array.
     *
     * @param mixed[] $criteria
     */
    public function processCriteria(array &$criteria): void;

    /**
     * Method to process POST, PUT and PATCH action form within REST traits.
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param string               $method
     * @param string|null          $id
     *
     * @return FormInterface
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function processForm(
        Request $request,
        FormFactoryInterface $formFactory,
        string $method,
        ?string $id = null
    ): FormInterface;
}
