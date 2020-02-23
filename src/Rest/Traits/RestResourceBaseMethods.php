<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Resource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Utils\JSON;
use JsonException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use function get_class;
use function str_replace;

/**
 * Trait RestResourceBaseMethods
 *
 * @package App\Rest\Traits
 */
trait RestResourceBaseMethods
{
    // Attach generic life cycle traits
    use RestResourceLifeCycles;

    /**
     * {@inheritdoc}
     */
    abstract public function getRepository(): BaseRepositoryInterface;

    /**
     * {@inheritdoc}
     */
    abstract public function getValidator(): ValidatorInterface;

    /**
     * Getter method DTO class with loaded entity data.
     *
     * @param string           $id
     * @param string           $dtoClass
     * @param RestDtoInterface $dto
     * @param bool|null        $patch
     *
     * @return RestDtoInterface
     *
     * @throws Throwable
     */
    abstract public function getDtoForEntity(
        string $id,
        string $dtoClass,
        RestDtoInterface $dto,
        ?bool $patch = null
    ): RestDtoInterface;

    /**
     * Generic find method to return an array of items from database. Return value is an array of specified repository
     * entities.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $orderBy
     * @param int|null     $limit
     * @param int|null     $offset
     * @param mixed[]|null $search
     *
     * @return EntityInterface[]
     *
     * @throws Throwable
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

    /**
     * Generic findOne method to return single item from database. Return value is single entity from specified
     * repository.
     *
     * @param string    $id
     * @param bool|null $throwExceptionIfNotFound
     *
     * @return EntityInterface|null
     *
     * @throws Throwable
     */
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

    /**
     * Generic findOneBy method to return single item from database by given criteria. Return value is single entity
     * from specified repository or null if entity was not found.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     * @param bool|null    $throwExceptionIfNotFound
     *
     * @return EntityInterface|null
     *
     * @throws Throwable
     */
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

    /**
     * Generic count method to return entity count for specified criteria and search terms.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return int
     *
     * @throws Throwable
     */
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

    /**
     * Generic method to create new item (entity) to specified database repository. Return value is created entity for
     * specified repository.
     *
     * @param RestDtoInterface $dto
     * @param bool|null        $flush
     * @param bool|null        $skipValidation
     *
     * @return EntityInterface
     *
     * @throws Throwable
     */
    public function create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface
    {
        $flush ??= true;
        $skipValidation ??= false;

        // Validate DTO
        $this->validateDto($dto, $skipValidation);

        // Create new entity
        $entity = $this->createEntity();

        // Before callback method call
        $this->beforeCreate($dto, $entity);

        // Create or update entity
        $this->persistEntity($entity, $dto, $flush, $skipValidation);

        // After callback method call
        $this->afterCreate($dto, $entity);

        return $entity;
    }

    /**
     * Generic method to update specified entity with new data.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param bool|null        $flush
     * @param bool|null        $skipValidation
     *
     * @return EntityInterface
     *
     * @throws Throwable
     */
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
         * Determine used dto class and create new instance of that and load entity to that. And after that patch
         * that dto with given partial OR whole dto class.
         */
        $restDto = $this->getDtoForEntity($id, get_class($dto), $dto);

        // Validate DTO
        $this->validateDto($restDto, $skipValidation);

        // Before callback method call
        $this->beforeUpdate($id, $restDto, $entity);

        // Create or update entity
        $this->persistEntity($entity, $restDto, $flush, $skipValidation);

        // After callback method call
        $this->afterUpdate($id, $restDto, $entity);

        return $entity;
    }

    /**
     * Generic method to patch specified entity with new partial data.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param bool|null        $flush
     * @param bool|null        $skipValidation
     *
     * @return EntityInterface
     *
     * @throws Throwable
     */
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
         * Determine used dto class and create new instance of that and load entity to that. And after that patch
         * that dto with given partial OR whole dto class.
         */
        $restDto = $this->getDtoForEntity($id, get_class($dto), $dto, true);

        // Validate DTO
        $this->validateDto($restDto, $skipValidation);

        // Before callback method call
        $this->beforePatch($id, $restDto, $entity);

        // Create or update entity
        $this->persistEntity($entity, $restDto, $flush, $skipValidation);

        // After callback method call
        $this->afterPatch($id, $restDto, $entity);

        return $entity;
    }

    /**
     * Generic method to delete specified entity from database.
     *
     * @param string    $id
     * @param bool|null $flush
     *
     * @return EntityInterface
     *
     * @throws Throwable
     */
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
     * Generic ids method to return an array of id values from database. Return value is an array of specified
     * repository entity id values.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return string[]
     *
     * @throws Throwable
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

    /**
     * Generic method to save given entity to specified repository. Return value is created entity.
     *
     * @param EntityInterface $entity
     * @param bool|null       $flush
     * @param bool|null       $skipValidation
     *
     * @return EntityInterface
     *
     * @throws Throwable
     */
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
     * @param EntityInterface  $entity
     * @param RestDtoInterface $dto
     * @param bool             $flush
     * @param bool             $skipValidation
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
     * @param string $id
     *
     * @return EntityInterface
     *
     * @throws Throwable
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
     * @param RestDtoInterface $dto
     * @param bool             $skipValidation
     *
     * @throws Throwable
     */
    private function validateDto(RestDtoInterface $dto, bool $skipValidation): void
    {
        /** @var ConstraintViolationListInterface|null $errors */
        $errors = !$skipValidation ? $this->getValidator()->validate($dto) : null;

        // Oh noes, we have some errors
        if ($errors !== null && $errors->count() > 0) {
            $this->createValidatorException($errors, get_class($dto));
        }
    }

    /**
     * Method to validate specified entity.
     *
     * @param EntityInterface $entity
     * @param bool            $skipValidation
     *
     * @throws Throwable
     */
    private function validateEntity(EntityInterface $entity, bool $skipValidation): void
    {
        $errors = !$skipValidation ? $this->getValidator()->validate($entity) : null;

        // Oh noes, we have some errors
        if ($errors !== null && $errors->count() > 0) {
            $this->createValidatorException($errors, get_class($entity));
        }
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     *
     * @return EntityInterface
     */
    private function createEntity(): EntityInterface
    {
        /** @var class-string $entityClass */
        $entityClass = $this->getRepository()->getEntityName();

        return new $entityClass();
    }

    /**
     * @param ConstraintViolationListInterface $errors
     * @param string                           $target
     *
     * @throws JsonException
     */
    private function createValidatorException(ConstraintViolationListInterface $errors, string $target): void
    {
        $output = [];

        /** @var ConstraintViolationInterface $error */
        foreach ($errors as $error) {
            $output[] = [
                'message' => $error->getMessage(),
                'propertyPath' => $error->getPropertyPath(),
                'target' => str_replace('\\', '.', $target),
                'code' => $error->getCode(),
            ];
        }

        throw new ValidatorException(JSON::encode($output));
    }

    /**
     * @param bool                 $throwExceptionIfNotFound
     * @param EntityInterface|null $entity
     *
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
