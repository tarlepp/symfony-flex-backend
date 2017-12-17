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
use App\Rest\RestResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
     * @var RestResourceInterface
     */
    protected $resource;

    /**
     * @var ResponseHandlerInterface
     */
    protected $responseHandler;

    /**
     * @return RestResourceInterface
     *
     * @throws \UnexpectedValueException
     */
    public function getResource(): RestResourceInterface
    {
        if (!$this->resource instanceof RestResourceInterface) {
            throw new \UnexpectedValueException('Resource service not set', 500);
        }

        return $this->resource;
    }

    /**
     * @return ResponseHandlerInterface
     *
     * @throws \UnexpectedValueException
     */
    public function getResponseHandler(): ResponseHandlerInterface
    {
        if (!$this->responseHandler instanceof ResponseHandlerInterface) {
            throw new \UnexpectedValueException('ResponseHandler service not set', 500);
        }

        return $this->responseHandler;
    }

    /**
     * Getter method for used DTO class for current controller.
     *
     * @param string|null $method
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    public function getDtoClass(string $method = null): string
    {
        $dtoClass = \array_key_exists($method, static::$dtoClasses)
            ? static::$dtoClasses[$method]
            : $this->getResource()->getDtoClass();

        if (!\in_array(RestDtoInterface::class, \class_implements($dtoClass), true)) {
            $message = \sprintf(
                'Given DTO class \'%s\' is not implementing \'%s\' interface.',
                $dtoClass,
                RestDtoInterface::class
            );

            throw new \UnexpectedValueException($message);
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
     * @throws \UnexpectedValueException
     */
    public function getFormTypeClass(string $method = null): string
    {
        $method = $method ?? '';

        if ($position = mb_strrpos($method, '::')) {
            $method = mb_substr($method, $position + 2);
        }

        return \array_key_exists($method, static::$formTypes)
            ? static::$formTypes[$method]
            : $this->getResource()->getFormTypeClass();
    }

    /**
     * Method to validate REST trait method.
     *
     * @param Request $request
     * @param array   $allowedHttpMethods
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function validateRestMethod(Request $request, array $allowedHttpMethods): void
    {
        // Make sure that we have everything we need to make this work
        if (!($this instanceof ControllerInterface)) {
            $message = \sprintf(
                'You cannot use \'%s\' controller class with REST traits if that does not implement \'%s\'',
                \get_class($this),
                ControllerInterface::class
            );

            throw new \LogicException($message);
        }

        if (!\in_array($request->getMethod(), $allowedHttpMethods, true)) {
            throw new MethodNotAllowedHttpException($allowedHttpMethods);
        }
    }

    /**
     * Method to handle possible REST method trait exception.
     *
     * @param \Exception  $exception
     * @param string|null $id
     *
     * @return HttpException
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handleRestMethodException(\Exception $exception, string $id = null): HttpException
    {
        $this->detachEntityFromManager($id);

        $code = $this->getExceptionCode($exception);

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
     * Method to process current criteria array.
     *
     * @SuppressWarnings("unused")
     *
     * @param array $criteria
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
     * @throws \UnexpectedValueException
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
        string $id = null
    ): FormInterface {
        $formType = $this->getFormTypeClass($method);

        // Create form, load possible entity data for form and handle request
        $form = $formFactory->createNamed('', $formType, null, ['method' => $request->getMethod()]);

        if ($id !== null) {
            $form->setData($this->getResource()->getDtoForEntity($id, $form->getConfig()->getDataClass()));
        }

        $form->handleRequest($request);

        if (!$form->isValid()) {
            $this->getResponseHandler()->handleFormError($form);
        }

        return $form;
    }

    /**
     * @param \Exception $exception
     *
     * @return int
     */
    private function getExceptionCode(\Exception $exception): int
    {
        return (int)$exception->getCode() !== 0 ? (int)$exception->getCode() : Response::HTTP_BAD_REQUEST;
    }

    /**
     * Method to detach entity from entity manager so possible changes to it won't be saved.
     *
     * @param string|null $id
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function detachEntityFromManager(string $id = null): void
    {
        if ($id === null) {
            return;
        }

        $resource = $this->getResource();
        $entityManager = $resource->getRepository()->getEntityManager();

        try {
            // Fetch entity
            $entity = $resource->findOne($id, false);

            // Detach entity from manager if it's been managed by it
            if ($entity !== null && $entityManager->getUnitOfWork()->getEntityState($entity) === UnitOfWork::STATE_MANAGED) {
                $entityManager->detach($entity);
            }
        } catch (\Exception $exception) {
            // Silently ignore this one
        }
    }
}
