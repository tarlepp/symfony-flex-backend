<?php
declare(strict_types = 1);
/**
 * /src/Rest/RestResource.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;
use App\Repository\BaseRepositoryInterface;
use Doctrine\Common\Proxy\Proxy;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RestResource
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestResource implements RestResourceInterface
{
    use Traits\RestResourceBaseMethods;

    /**
     * @var BaseRepositoryInterface
     */
    private $repository;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var string
     */
    private $dtoClass;

    /**
     * @var string
     */
    private $formTypeClass;

    /**
     * Getter method for entity repository.
     *
     * @return BaseRepositoryInterface
     */
    public function getRepository(): BaseRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Setter method for repository.
     *
     * @param BaseRepositoryInterface $repository
     *
     * @return RestResourceInterface
     */
    public function setRepository(BaseRepositoryInterface $repository): RestResourceInterface
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Getter for used validator.
     *
     * @return ValidatorInterface
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    /**
     * Setter for used validator.
     *
     * @param ValidatorInterface $validator
     *
     * @return RestResourceInterface
     */
    public function setValidator(ValidatorInterface $validator): RestResourceInterface
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Getter method for used DTO class for this REST service.
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    public function getDtoClass(): string
    {
        if ($this->dtoClass === '') {
            $message = \sprintf(
                'DTO class not specified for \'%s\' resource',
                static::class
            );

            throw new \UnexpectedValueException($message);
        }

        return $this->dtoClass;
    }

    /**
     * Setter for used DTO class.
     *
     * @param string $dtoClass
     *
     * @return RestResourceInterface
     */
    public function setDtoClass(string $dtoClass): RestResourceInterface
    {
        $this->dtoClass = $dtoClass;

        return $this;
    }

    /**
     * Getter method for used default FormType class for this REST resource.
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    public function getFormTypeClass(): string
    {
        if ($this->formTypeClass === '') {
            $message = \sprintf(
                'FormType class not specified for \'%s\' resource',
                static::class
            );

            throw new \UnexpectedValueException($message);
        }

        return $this->formTypeClass;
    }

    /**
     * Setter method for used default FormType class for this REST resource.
     *
     * @param string $formTypeClass
     *
     * @return RestResourceInterface
     */
    public function setFormTypeClass(string $formTypeClass): RestResourceInterface
    {
        $this->formTypeClass = $formTypeClass;

        return $this;
    }

    /**
     * Getter method for current entity name.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->getRepository()->getEntityName();
    }

    /** @noinspection GenericObjectTypeUsageInspection */
    /**
     * Gets a reference to the entity identified by the given type and identifier without actually loading it,
     * if the entity is not yet loaded.
     *
     * @param string $id The entity identifier.
     *
     * @return Proxy|object|null
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getReference(string $id)
    {
        return $this->getRepository()->getReference($id);
    }

    /**
     * Getter method for all associations that current entity contains.
     *
     * @return array
     */
    public function getAssociations(): array
    {
        return \array_keys($this->getRepository()->getAssociations());
    }

    /**
     * Getter method DTO class with loaded entity data.
     *
     * @param string                $id
     * @param string                $dtoClass
     * @param null|RestDtoInterface $dto
     *
     * @return RestDtoInterface
     *
     * @throws \LogicException
     * @throws \BadMethodCallException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getDtoForEntity(string $id, string $dtoClass, RestDtoInterface $dto = null): RestDtoInterface
    {
        // Fetch entity
        $entity = $this->getEntity($id);

        /**
         * Create new instance of DTO and load entity to that.
         *
         * @var RestDtoInterface $restDto
         */
        $restDto = new $dtoClass();
        $restDto->load($entity);

        if ($dto !== null) {
            $restDto->patch($dto);
        }

        return $restDto;
    }

    /**
     * Helper method to set data to specified entity and store it to database.
     *
     * @param EntityInterface  $entity
     * @param RestDtoInterface $dto
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     */
    protected function persistEntity(EntityInterface $entity, RestDtoInterface $dto): void
    {
        // Update entity according to DTO current state
        $dto->update($entity);

        // And save current entity
        $this->save($entity);
    }

    /**
     * @param string $id
     *
     * @return EntityInterface
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function getEntity(string $id): EntityInterface
    {
        /** @var EntityInterface $entity */
        $entity = $this->getRepository()->find($id);

        // Entity not found
        if ($entity === null) {
            throw new NotFoundHttpException('Not found');
        }

        return $entity;
    }

    /**
     * Helper method to validate given DTO class.
     *
     * @param RestDtoInterface $dto
     * @param bool             $skipValidation
     *
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     */
    private function validateDto(RestDtoInterface $dto, bool $skipValidation): void
    {
        // Check possible errors of DTO
        $errors = !$skipValidation ? $this->getValidator()->validate($dto) : [];

        // Oh noes, we have some errors
        if (\count($errors) > 0) {
            throw new ValidatorException((string)$errors);
        }
    }

    /**
     * Method to validate specified entity.
     *
     * @param EntityInterface $entity
     * @param bool            $skipValidation
     *
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     */
    private function validateEntity(EntityInterface $entity, bool $skipValidation): void
    {
        $errors = !$skipValidation ? $this->getValidator()->validate($entity) : [];

        // Oh noes, we have some errors
        if (\count($errors) > 0) {
            throw new ValidatorException((string)$errors);
        }
    }
}
