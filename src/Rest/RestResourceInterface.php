<?php
declare(strict_types = 1);
/**
 * /src/Rest/RestResourceInterfaces.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use App\DTO\RestDtoInterface;
use App\Entity\EntityInterface;
use App\Repository\BaseRepositoryInterface;
use BadMethodCallException;
use Doctrine\Common\Proxy\Proxy;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnexpectedValueException;

/**
 * Interface ResourceInterface
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface RestResourceInterface
{
    /**
     * Getter method for entity repository.
     *
     * @return BaseRepositoryInterface
     */
    public function getRepository(): BaseRepositoryInterface;

    /**
     * Setter method for repository.
     *
     * @param BaseRepositoryInterface $repository
     *
     * @return RestResourceInterface
     */
    public function setRepository(BaseRepositoryInterface $repository): self;

    /**
     * Getter for used validator.
     *
     * @return ValidatorInterface
     */
    public function getValidator(): ValidatorInterface;

    /**
     * Setter for used validator.
     *
     * @param ValidatorInterface $validator
     *
     * @return RestResourceInterface
     */
    public function setValidator(ValidatorInterface $validator): self;

    /**
     * Getter method for used DTO class for this REST service.
     *
     * @return string
     *
     * @throws UnexpectedValueException
     */
    public function getDtoClass(): string;

    /**
     * Setter for used DTO class.
     *
     * @param string $dtoClass
     *
     * @return RestResourceInterface
     */
    public function setDtoClass(string $dtoClass): self;

    /**
     * Getter method for used default FormType class for this REST resource.
     *
     * @return string
     */
    public function getFormTypeClass(): string;

    /**
     * Setter method for used default FormType class for this REST resource.
     *
     * @param string $formTypeClass
     *
     * @return RestResourceInterface
     */
    public function setFormTypeClass(string $formTypeClass): self;

    /**
     * Getter method for current entity name.
     *
     * @return string
     */
    public function getEntityName(): string;

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
    public function getReference(string $id);

    /**
     * Getter method for all associations that current entity contains.
     *
     * @return string[]
     */
    public function getAssociations(): array;

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
    public function getDtoForEntity(string $id, string $dtoClass): RestDtoInterface;

    /**
     * Generic find method to return an array of items from database. Return value is an array of specified repository
     * entities.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param mixed[]|null $search
     *
     * @return EntityInterface[]
     */
    public function find(
        ?array $criteria = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $search = null
    ): array;

    /**
     * Generic findOne method to return single item from database. Return value is single entity from specified
     * repository.
     *
     * @param string       $id
     * @param boolean|null $throwExceptionIfNotFound
     *
     * @return EntityInterface|null
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOne(string $id, ?bool $throwExceptionIfNotFound = null): ?EntityInterface;

    /**
     * Generic findOneBy method to return single item from database by given criteria. Return value is single entity
     * from specified repository or null if entity was not found.
     *
     * @param mixed[]      $criteria
     * @param mixed[]|null $orderBy
     * @param boolean|null $throwExceptionIfNotFound
     *
     * @return EntityInterface|null
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findOneBy(
        array $criteria,
        ?array $orderBy = null,
        ?bool $throwExceptionIfNotFound = null
    ): ?EntityInterface;

    /**
     * Generic count method to return entity count for specified criteria and search terms.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return integer
     *
     * @throws InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function count(?array $criteria = null, ?array $search = null): int;

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
    public function create(RestDtoInterface $dto, ?bool $skipValidation = null): EntityInterface;

    /**
     * Generic method to update specified entity with new data.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param bool|null        $skipValidation
     *
     * @return EntityInterface
     *
     * @throws LogicException
     * @throws BadMethodCallException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function update(string $id, RestDtoInterface $dto, ?bool $skipValidation = null): EntityInterface;

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
    public function delete(string $id): EntityInterface;

    /**
     * Generic ids method to return an array of id values from database. Return value is an array of specified
     * repository entity id values.
     *
     * @param mixed[]|null $criteria
     * @param mixed[]|null $search
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function getIds(?array $criteria = null, ?array $search = null): array;

    /**
     * Generic method to save given entity to specified repository. Return value is created entity.
     *
     * @param EntityInterface $entity
     * @param boolean|null    $skipValidation
     *
     * @return EntityInterface
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Symfony\Component\Validator\Exception\ValidatorException
     */
    public function save(EntityInterface $entity, ?bool $skipValidation = null): EntityInterface;
}
