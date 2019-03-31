<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/MethodValidator.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Rest\ControllerInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\RestResourceInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\UnitOfWork;
use Error;
use Exception;
use LogicException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use TypeError;
use UnexpectedValueException;
use function array_key_exists;
use function class_implements;
use function in_array;
use function mb_strrpos;
use function mb_substr;
use function sprintf;

/**
 * Trait MethodValidator
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestMethodHelper
{
    /**
     * Method + DTO class names (key + value)
     *
     * @var string[]
     */
    protected static $dtoClasses = [];

    /**
     * Method + Form type class names (key + value)
     *
     * @var string[]
     */
    protected static $formTypes = [];

    /**
     * @return RestResourceInterface
     */
    abstract public function getResource(): RestResourceInterface;

    /**
     * @return ResponseHandlerInterface
     */
    abstract public function getResponseHandler(): ResponseHandlerInterface;

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getDtoClass(?string $method = null): string
    {
        $dtoClass = $method !== null && array_key_exists($method, static::$dtoClasses)
            ? static::$dtoClasses[$method]
            : $this->getResource()->getDtoClass();

        if (!in_array(RestDtoInterface::class, class_implements($dtoClass), true)) {
            $message = sprintf(
                'Given DTO class \'%s\' is not implementing \'%s\' interface.',
                $dtoClass,
                RestDtoInterface::class
            );

            throw new UnexpectedValueException($message);
        }

        return $dtoClass;
    }

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getFormTypeClass(?string $method = null): string
    {
        $method = $method ?? '';
        $position = mb_strrpos($method, '::');

        if ($position !== false) {
            $method = mb_substr($method, $position + 2);
        }

        return array_key_exists($method, static::$formTypes)
            ? static::$formTypes[$method]
            : $this->getResource()->getFormTypeClass();
    }

    /**
     * Method to validate REST trait method.
     *
     * @param Request  $request
     * @param string[] $allowedHttpMethods
     *
     * @throws LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function validateRestMethod(Request $request, array $allowedHttpMethods): void
    {
        // Make sure that we have everything we need to make this work
        if (!($this instanceof ControllerInterface)) {
            $message = sprintf(
                'You cannot use \'%s\' controller class with REST traits if that does not implement \'%s\'',
                static::class,
                ControllerInterface::class
            );

            throw new LogicException($message);
        }

        if (!in_array($request->getMethod(), $allowedHttpMethods, true)) {
            throw new MethodNotAllowedHttpException($allowedHttpMethods);
        }
    }

    /**
     * Method to handle possible REST method trait exception.
     *
     * @param Throwable   $exception
     * @param string|null $id
     *
     * @return Throwable
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function handleRestMethodException(Throwable $exception, ?string $id = null): Throwable
    {
        if ($id !== null) {
            $this->detachEntityFromManager($id);
        }

        return $this->determineOutputAndStatusCodeForRestMethodException($exception);
    }

    /**
     * Method to process current criteria array.
     *
     * @SuppressWarnings("unused")
     *
     * @param mixed[] $criteria
     */
    public function processCriteria(/** @scrutinizer ignore-unused */ array &$criteria): void
    {
    }

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
     * @throws UnexpectedValueException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
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
    ): FormInterface {
        $formType = $this->getFormTypeClass($method);

        // Create form, load possible entity data for form and handle request
        $form = $formFactory->createNamed('', $formType, null, ['method' => $request->getMethod()]);

        $this->setDtoToForm($form, $id);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            throw new BadRequestHttpException('Bad request');
        }

        if (!$form->isValid()) {
            $this->getResponseHandler()->handleFormError($form);
        }

        return $form;
    }

    /**
     * @param Throwable $exception
     *
     * @return int
     */
    private function getExceptionCode(Throwable $exception): int
    {
        return (int)$exception->getCode() !== 0 ? (int)$exception->getCode() : Response::HTTP_BAD_REQUEST;
    }

    /**
     * Method to detach entity from entity manager so possible changes to it won't be saved.
     *
     * @param string $id
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function detachEntityFromManager(string $id): void
    {
        $currentResource = $this->getResource();

        /** @var EntityManager $entityManager */
        $entityManager = $currentResource->getRepository()->getEntityManager();

        // Fetch entity
        $entity = $currentResource->getRepository()->find($id);

        // Detach entity from manager if it's been managed by it
        if ($entity !== null
            /** @scrutinizer ignore-call */
            && $entityManager->getUnitOfWork()->getEntityState($entity) === UnitOfWork::STATE_MANAGED
        ) {
            $entityManager->detach($entity);
        }
    }

    /**
     * @param Throwable|Exception|TypeError|Error $exception
     *
     * @return Throwable
     */
    private function determineOutputAndStatusCodeForRestMethodException($exception): Throwable
    {
        $code = $this->getExceptionCode($exception);

        // Ensure that we have proper exception, otherwise REST resource isn't configured properly
        if (!($exception instanceof Exception)) {
            $exception = new Exception($exception->getMessage(), $code);
        }

        /** @var Exception $exception */
        $output = new HttpException($code, $exception->getMessage(), $exception, [], $code);

        if ($exception instanceof HttpException) {
            $output = $exception;
        } elseif ($exception instanceof NoResultException) {
            $code = Response::HTTP_NOT_FOUND;

            $output = new HttpException($code, 'Not found', $exception, [], $code);
        } elseif ($exception instanceof NonUniqueResultException) {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;

            $output = new HttpException($code, $exception->getMessage(), $exception, [], $code);
        }

        return $output;
    }

    /**
     * @param FormInterface $form
     * @param string|null   $id
     *
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     */
    private function setDtoToForm(FormInterface $form, ?string $id): void
    {
        $dtoClass = $form->getConfig()->getDataClass();

        if ($id !== null && $dtoClass !== null) {
            $form->setData($this->getResource()->getDtoForEntity($id, $dtoClass));
        }
    }
}
