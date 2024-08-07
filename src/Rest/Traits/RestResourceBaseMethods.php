<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestResourceBaseMethods.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Exception\ValidatorException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;
use UnexpectedValueException;
use function assert;

/**
 * @package App\Rest\Traits
 */
trait RestResourceBaseMethods
{
    use RestResourceLifeCycles;

    /**
     * {@inheritdoc}
     *
     * @return array<int, EntityInterface>
     */
    public function find(
        ?array $criteria = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $search = null
    ): array {
        $criteria ??= [];
        $orderBy ??= [];
        $search ??= [];

        // Before callback method call
        $this->beforeFind($criteria, $orderBy, $limit, $offset, $search);

        // Fetch data
        $entities = $this->getRepository()->findByAdvanced($criteria, $orderBy, $limit, $offset, $search);

        // After callback method call
        $this->afterFind($criteria, $orderBy, $limit, $offset, $search, $entities);

        return $entities;
    }

    public function findOne(string $id, ?bool $throwExceptionIfNotFound = null): ?EntityInterface
    {
        $throwExceptionIfNotFound ??= false;

        // Before callback method call
        $this->beforeFindOne($id);

        /** @var EntityInterface|null $entity */
        $entity = $this->getRepository()->findAdvanced($id);

        $this->checkThatEntityExists($throwExceptionIfNotFound, $entity);

        // After callback method call
        $this->afterFindOne($id, $entity);

        return $entity;
    }

    public function findOneBy(
        array $criteria,
        ?array $orderBy = null,
        ?bool $throwExceptionIfNotFound = null
    ): ?EntityInterface {
        $orderBy ??= [];
        $throwExceptionIfNotFound ??= false;

        // Before callback method call
        $this->beforeFindOneBy($criteria, $orderBy);

        /** @var EntityInterface|null $entity */
        $entity = $this->getRepository()->findOneBy($criteria, $orderBy);

        $this->checkThatEntityExists($throwExceptionIfNotFound, $entity);

        // After callback method call
        $this->afterFindOneBy($criteria, $orderBy, $entity);

        return $entity;
    }

    public function count(?array $criteria = null, ?array $search = null): int
    {
        $criteria ??= [];
        $search ??= [];

        // Before callback method call
        $this->beforeCount($criteria, $search);

        $count = $this->getRepository()->countAdvanced($criteria, $search);

        // After callback method call
        $this->afterCount($criteria, $search, $count);

        return $count;
    }

    public function create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface
    {
        $flush ??= true;
        $skipValidation ??= false;

        // Create new entity
        $entity = $this->createEntity();

        // Before callback method call
        $this->beforeCreate($dto, $entity);

        // Validate DTO
        $this->validateDto($dto, $skipValidation);

        // Create or update entity
        $this->persistEntity($entity, $dto, $flush, $skipValidation);

        // After callback method call
        $this->afterCreate($dto, $entity);

        return $entity;
    }

    public function update(
        string $id,
        RestDtoInterface $dto,
        ?bool $flush = null,
        ?bool $skipValidation = null
    ): EntityInterface {
        $flush ??= true;
        $skipValidation ??= false;

        // Fetch entity
        $entity = $this->getEntity($id);

        /**
         * Determine used dto class and create new instance of that and load
         * entity to that. And after that patch that dto with given partial OR
         * whole dto class.
         */
        $restDto = $this->getDtoForEntity($id, $dto::class, $dto);

        // Before callback method call
        $this->beforeUpdate($id, $restDto, $entity);

        // Validate DTO
        $this->validateDto($restDto, $skipValidation);

        // Create or update entity
        $this->persistEntity($entity, $restDto, $flush, $skipValidation);

        // After callback method call
        $this->afterUpdate($id, $restDto, $entity);

        return $entity;
    }

    public function patch(
        string $id,
        RestDtoInterface $dto,
        ?bool $flush = null,
        ?bool $skipValidation = null
    ): EntityInterface {
        $flush ??= true;
        $skipValidation ??= false;

        // Fetch entity
        $entity = $this->getEntity($id);

        /**
         * Determine used dto class and create new instance of that and load
         * entity to that. And after that patch that dto with given partial OR
         * whole dto class.
         */
        $restDto = $this->getDtoForEntity($id, $dto::class, $dto, true);

        // Before callback method call
        $this->beforePatch($id, $restDto, $entity);

        // Validate DTO
        $this->validateDto($restDto, $skipValidation);

        // Create or update entity
        $this->persistEntity($entity, $restDto, $flush, $skipValidation);

        // After callback method call
        $this->afterPatch($id, $restDto, $entity);

        return $entity;
    }

    public function delete(string $id, ?bool $flush = null): EntityInterface
    {
        $flush ??= true;

        // Fetch entity
        $entity = $this->getEntity($id);

        // Before callback method call
        $this->beforeDelete($id, $entity);

        // And remove entity from repo
        $this->getRepository()->remove($entity, $flush);

        // After callback method call
        $this->afterDelete($id, $entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int, string>
     */
    public function getIds(?array $criteria = null, ?array $search = null): array
    {
        $criteria ??= [];
        $search ??= [];

        // Before callback method call
        $this->beforeIds($criteria, $search);

        // Fetch data
        $ids = $this->getRepository()->findIds($criteria, $search);

        // After callback method call
        $this->afterIds($ids, $criteria, $search);

        return $ids;
    }

    public function save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface
    {
        $flush ??= true;
        $skipValidation ??= false;

        // Before callback method call
        $this->beforeSave($entity);

        // Validate current entity
        $this->validateEntity($entity, $skipValidation);

        // Persist on database
        $this->getRepository()->save($entity, $flush);

        // After callback method call
        $this->afterSave($entity);

        return $entity;
    }

    /**
     * Helper method to set data to specified entity and store it to database.
     *
     * @throws Throwable
     */
    protected function persistEntity(
        EntityInterface $entity,
        RestDtoInterface $dto,
        bool $flush,
        bool $skipValidation
    ): void {
        // Update entity according to DTO current state
        $dto->update($entity);

        // And save current entity
        $this->save($entity, $flush, $skipValidation);
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function getEntity(string $id): EntityInterface
    {
        $entity = $this->getRepository()->find($id);

        if ($entity === null) {
            throw new NotFoundHttpException('Not found');
        }

        return $entity;
    }

    /**
     * Helper method to validate given DTO class.
     *
     * @throws Throwable
     */
    private function validateDto(RestDtoInterface $dto, bool $skipValidation): void
    {
        /** @var ConstraintViolationListInterface|null $errors */
        $errors = $skipValidation ? null : $this->getValidator()->validate($dto);

        // Oh noes, we have some errors
        if ($errors !== null && $errors->count() > 0) {
            throw new ValidatorException($dto::class, $errors);
        }
    }

    /**
     * Method to validate specified entity.
     *
     * @throws Throwable
     */
    private function validateEntity(EntityInterface $entity, bool $skipValidation): void
    {
        $errors = $skipValidation ? null : $this->getValidator()->validate($entity);

        // Oh noes, we have some errors
        if ($errors !== null && $errors->count() > 0) {
            throw new ValidatorException($entity::class, $errors);
        }
    }

    private function createEntity(): EntityInterface
    {
        /** @var class-string $entityClass */
        $entityClass = $this->getRepository()->getEntityName();

        $entity = new $entityClass();

        $exception = new UnexpectedValueException(
            sprintf('Given `%s` class does not implement `EntityInterface`', $entityClass),
        );

        return assert($entity instanceof EntityInterface) ? $entity : throw $exception;
    }

    /**
     * @throws NotFoundHttpException
     */
    private function checkThatEntityExists(bool $throwExceptionIfNotFound, ?EntityInterface $entity): void
    {
        // Entity not found
        if ($throwExceptionIfNotFound && $entity === null) {
            throw new NotFoundHttpException('Not found');
        }
    }
}
