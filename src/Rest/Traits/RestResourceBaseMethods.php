<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Resource.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;
use App\Repository\BaseRepositoryInterface;
use App\Utils\JSON;
use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function get_class;

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
     * Getter method for entity repository.
     *
     * @return BaseRepositoryInterface
     */
    abstract public function getRepository(): BaseRepositoryInterface;

    /**
     * Getter for used validator.
     *
     * @return ValidatorInterface
     */
    abstract public function getValidator(): ValidatorInterface;

    /**
     * Getter method DTO class with loaded entity data.
     *
     * @param string                $id
     * @param string                $dtoClass
     * @param null|RestDtoInterface $dto
     *
     * @return RestDtoInterface
     *
     * @throws LogicException
     * @throws BadMethodCallException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    abstract public function getDtoForEntity(
        string $id,
        string $dtoClass,
        ?RestDtoInterface $dto = null
    ): RestDtoInterface;

    /**
     * Generic find method to return an array of items from database. Return value is an array of specified repository
     * entities.
     *
     * @param null|mixed[] $criteria
     * @param null|mixed[] $orderBy
     * @param null|integer $limit
     * @param null|integer $offset
     * @param null|mixed[] $search
     *
     * @return EntityInterface[]
     *
     * @throws InvalidArgumentException
     */
    public function find(
        ?array $criteria = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $search = null
    ): array {
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
    public function findOne(string $id, ?bool $throwExceptionIfNotFound = null): ?EntityInterface
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
     * @param mixed[]      $criteria
     * @param null|mixed[] $orderBy
     * @param null|boolean $throwExceptionIfNotFound
     *
     * @return null|EntityInterface
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findOneBy(
        array $criteria,
        ?array $orderBy = null,
        ?bool $throwExceptionIfNotFound = null
    ): ?EntityInterface {
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
     * @param null|mixed[] $criteria
     * @param null|mixed[] $search
     *
     * @return integer
     *
     * @throws InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function count(?array $criteria = null, ?array $search = null): int
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
     * @throws Exception
     * @throws ValidatorException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(RestDtoInterface $dto, ?bool $skipValidation = null): EntityInterface
    {
        $skipValidation = $skipValidation ?? false;

        // Validate DTO
        $this->validateDto($dto, $skipValidation);

        // Create new entity
        $entity = $this->createEntity();

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
     * @throws BadMethodCallException
     * @throws Exception
     * @throws ValidatorException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function update(string $id, RestDtoInterface $dto, ?bool $skipValidation = null): EntityInterface
    {
        $skipValidation = $skipValidation ?? false;

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
     * @param null|mixed[] $criteria
     * @param null|mixed[] $search
     *
     * @return string[]|array<mixed, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function getIds(?array $criteria = null, ?array $search = null): array
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
     * @throws Exception
     * @throws ValidatorException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function save(EntityInterface $entity, ?bool $skipValidation = null): EntityInterface
    {
        $skipValidation = $skipValidation ?? false;

        // Before callback method call
        $this->beforeSave($entity);

        // Validate current entity
        $this->validateEntity($entity, $skipValidation);

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
     * @throws Exception
     * @throws ValidatorException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
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
    protected function getEntity(string $id): EntityInterface
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
     * @throws Exception
     * @throws ValidatorException
     */
    private function validateDto(RestDtoInterface $dto, bool $skipValidation): void
    {
        /** @var ConstraintViolationListInterface|null $errors */
        $errors = !$skipValidation ? $this->getValidator()->validate($dto) : null;

        // Oh noes, we have some errors
        if ($errors !== null && $errors->count() > 0) {
            $this->createValidatorException($errors);
        }
    }

    /**
     * Method to validate specified entity.
     *
     * @param EntityInterface $entity
     * @param bool            $skipValidation
     *
     * @throws Exception
     * @throws ValidatorException
     */
    private function validateEntity(EntityInterface $entity, bool $skipValidation): void
    {
        /** @var ConstraintViolationListInterface|null $errors */
        $errors = !$skipValidation ? $this->getValidator()->validate($entity) : null;

        // Oh noes, we have some errors
        if ($errors !== null && $errors->count() > 0) {
            $this->createValidatorException($errors);
        }
    }

    /**
     * @return EntityInterface
     */
    private function createEntity(): EntityInterface
    {
        $entityClass = $this->getRepository()->getEntityName();

        /** @noinspection OneTimeUseVariablesInspection */
        /** @var EntityInterface $output */
        $output = new $entityClass();

        return $output;
    }

    /**
     * @param ConstraintViolationListInterface $errors
     *
     * @throws Exception
     * @throws ValidatorException
     */
    private function createValidatorException(ConstraintViolationListInterface $errors): void
    {
        $output = [];

        /** @var ConstraintViolationInterface $error */
        foreach ($errors as $error) {
            $output[] = [
                'message' => $error->getMessage(),
                'propertyPath' => $error->getPropertyPath(),
                'code' => $error->getCode(),
            ];
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new ValidatorException(JSON::encode($output));
    }
}
