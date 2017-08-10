<?php
declare(strict_types=1);
/**
 * /src/Rest/RestResource.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use App\Entity\EntityInterface;
use App\Rest\DTO\RestDtoInterface;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class Resource
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestRestResource implements RestResourceInterface
{
    // Attach generic life cycle traits
    use Traits\RestResource;

    /**
     * @var Repository|EntityRepository
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
     * @return Repository
     */
    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * Setter method for repository.
     *
     * @param Repository $repository
     *
     * @return RestResourceInterface
     */
    public function setRepository(Repository $repository): RestResourceInterface
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
        if ((string)$this->dtoClass === '') {
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
        if ((string)$this->formTypeClass === '') {
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

    /**
     * Gets a reference to the entity identified by the given type and identifier without actually loading it,
     * if the entity is not yet loaded.
     *
     * @param string $id The entity identifier.
     *
     * @return Proxy|null
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getReference(string $id): ?Proxy
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
     * @param string $id
     * @param string $dtoClass
     *
     * @return RestDtoInterface
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getDtoForEntity(string $id, string $dtoClass): RestDtoInterface
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

        return $restDto;
    }

    /**
     * Generic find method to return an array of items from database. Return value is an array of specified repository
     * entities.
     *
     * @param null|array   $criteria
     * @param null|array   $orderBy
     * @param null|integer $limit
     * @param null|integer $offset
     * @param null|array   $search
     *
     * @return EntityInterface[]
     *
     * @throws \InvalidArgumentException
     */
    public function find(
        array $criteria = null,
        array $orderBy = null,
        int $limit = null,
        int $offset = null,
        array $search = null
    ): array
    {
        $criteria = $criteria ?? [];
        $orderBy = $orderBy ?? [];
        $limit = $limit ?? 0;
        $offset = $offset ?? 0;
        $search = $search ?? [];

        // Before callback method call
        $this->beforeFind($criteria, $orderBy, $limit, $offset, $search);

        // Fetch data
        $entities = $this->getRepository()->findByAdvanced($criteria, $orderBy, $limit, $offset, $search);

        // After callback method call
        $this->afterFind($criteria, $orderBy, $limit, $offset, $search, $entities);

        return $entities;
    }

    /**
     * Generic findOne method to return single item from database. Return value is single entity from specified
     * repository.
     *
     * @param string       $id
     * @param null|boolean $throwExceptionIfNotFound
     *
     * @return null|EntityInterface
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findOne(string $id, bool $throwExceptionIfNotFound = null): ?EntityInterface
    {
        $throwExceptionIfNotFound = $throwExceptionIfNotFound ?? false;

        // Before callback method call
        $this->beforeFindOne($id);

        /** @var null|EntityInterface $entity */
        $entity = $this->getRepository()->find($id);

        // Entity not found
        if ($throwExceptionIfNotFound && $entity === null) {
            throw new NotFoundHttpException('Not found');
        }

        // After callback method call
        $this->afterFindOne($id, $entity);

        return $entity;
    }

    /**
     * Generic findOneBy method to return single item from database by given criteria. Return value is single entity
     * from specified repository or null if entity was not found.
     *
     * @param array        $criteria
     * @param null|array   $orderBy
     * @param null|boolean $throwExceptionIfNotFound
     *
     * @return null|EntityInterface
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        bool $throwExceptionIfNotFound = null
    ): ?EntityInterface
    {
        $orderBy = $orderBy ?? [];
        $throwExceptionIfNotFound = $throwExceptionIfNotFound ?? false;

        // Before callback method call
        $this->beforeFindOneBy($criteria, $orderBy);

        /** @var null|EntityInterface $entity */
        $entity = $this->getRepository()->findOneBy($criteria, $orderBy);

        // Entity not found
        if ($throwExceptionIfNotFound && $entity === null) {
            throw new NotFoundHttpException('Not found');
        }

        // After callback method call
        $this->afterFindOneBy($criteria, $orderBy, $entity);

        return $entity;
    }

    /**
     * Generic count method to return entity count for specified criteria and search terms.
     *
     * @param null|array $criteria
     * @param null|array $search
     *
     * @return integer
     *
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function count(array $criteria = null, array $search = null): int
    {
        $criteria = $criteria ?? [];
        $search = $search ?? [];

        // Before callback method call
        $this->beforeCount($criteria, $search);

        $count = $this->getRepository()->countAdvanced($criteria, $search);

        // After callback method call
        $this->afterCount($criteria, $search, $count);

        return $count;
    }

    /**
     * Generic method to create new item (entity) to specified database repository. Return value is created entity for
     * specified repository.
     *
     * @param RestDtoInterface $dto
     * @param bool|null        $skipValidation
     *
     * @return EntityInterface
     *
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(RestDtoInterface $dto, bool $skipValidation = null): EntityInterface
    {
        $skipValidation = $skipValidation ?? false;

        // Validate DTO
        if (!$skipValidation) {
            $this->validateDto($dto);
        }

        // Determine entity name
        $entity = $this->getRepository()->getClassName();

        /**
         * Create new entity
         *
         * @var EntityInterface $entity
         */
        $entity = new $entity();

        // Before callback method call
        $this->beforeCreate($dto, $entity);

        // Create or update entity
        $this->persistEntity($entity, $dto);

        // After callback method call
        $this->afterCreate($dto, $entity);

        return $entity;
    }

    /**
     * Generic method to update specified entity with new data.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param bool|null        $skipValidation
     *
     * @return EntityInterface
     *
     * @throws \LogicException
     * @throws \BadMethodCallException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function update(string $id, RestDtoInterface $dto, bool $skipValidation = null): EntityInterface
    {
        $skipValidation = $skipValidation ?? false;

        // Fetch entity
        $entity = $this->getEntity($id);

        /**
         * Determine used dto class and create new instance of that and load entity to that. And after that patch
         * that dto with given partial OR whole dto class.
         *
         * @var RestDtoInterface $restDto
         */
        $dtoClass = \get_class($dto);

        $restDto = new $dtoClass();
        $restDto->load($entity);
        $restDto->patch($dto);

        // Validate DTO
        if (!$skipValidation) {
            $this->validateDto($restDto);
        }

        // Before callback method call
        $this->beforeUpdate($id, $restDto, $entity);

        // Create or update entity
        $this->persistEntity($entity, $restDto);

        // After callback method call
        $this->afterUpdate($id, $restDto, $entity);

        return $entity;
    }

    /**
     * Generic method to delete specified entity from database.
     *
     * @param string $id
     *
     * @return EntityInterface
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function delete(string $id): EntityInterface
    {
        // Fetch entity
        $entity = $this->getEntity($id);

        // Before callback method call
        $this->beforeDelete($id, $entity);

        // And remove entity from repo
        $this->getRepository()->remove($entity);

        // After callback method call
        $this->afterDelete($id, $entity);

        return $entity;
    }

    /**
     * Generic ids method to return an array of id values from database. Return value is an array of specified
     * repository entity id values.
     *
     * @param null|array $criteria
     * @param null|array $search
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getIds(array $criteria = null, array $search = null): array
    {
        $criteria = $criteria ?? [];
        $search = $search ?? [];

        // Before callback method call
        $this->beforeIds($criteria, $search);

        // Fetch data
        $ids = $this->getRepository()->findIds($criteria, $search);

        // After callback method call
        $this->afterIds($ids, $criteria, $search);

        return $ids;
    }

    /**
     * Generic method to save given entity to specified repository. Return value is created entity.
     *
     * @param EntityInterface $entity
     * @param null|boolean    $skipValidation
     *
     * @return EntityInterface
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     */
    public function save(EntityInterface $entity, bool $skipValidation = null): EntityInterface
    {
        $skipValidation = $skipValidation ?? false;

        // Before callback method call
        $this->beforeSave($entity);

        // Validate entity
        if (!$skipValidation) {
            $errors = $this->getValidator()->validate($entity);

            // Oh noes, we have some errors
            if (\count($errors) > 0) {
                throw new ValidatorException((string)$errors);
            }
        }

        // Persist on database
        $this->getRepository()->save($entity);

        // After callback method call
        $this->afterSave($entity);

        return $entity;
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
     * Helper method to validate given DTO class.
     *
     * @param RestDtoInterface $dto
     *
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     */
    private function validateDto(RestDtoInterface $dto): void
    {
        // Check possible errors of DTO
        $errors = $this->getValidator()->validate($dto);

        // Oh noes, we have some errors
        if (\count($errors) > 0) {
            throw new ValidatorException((string)$errors);
        }
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
}
