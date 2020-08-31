<?php
declare(strict_types = 1);
/**
 * /src/Rest/Interfaces/RestResourceInterfaces.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Interfaces;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use UnexpectedValueException;

/**
 * Interface ResourceInterface
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface RestResourceInterface
{
    /**
     * Getter method for serializer context.
     *
     * @return array<int|string, array<int, array<int, string>|string>|bool|string>
     */
    public function getSerializerContext(): array;

    /**
     * Getter method for entity repository.
     */
    public function getRepository(): BaseRepositoryInterface;

    /**
     * Setter method for repository.
     */
    public function setRepository(BaseRepositoryInterface $repository): self;

    /**
     * Getter for used validator.
     */
    public function getValidator(): ValidatorInterface;

    /**
     * Setter for used validator.
     *
     * @see https://symfony.com/doc/current/service_container/autowiring.html#autowiring-other-methods-e-g-setters
     *
     * @required
     */
    public function setValidator(ValidatorInterface $validator): self;

    /**
     * Getter method for used DTO class for this REST service.
     *
     * @throws UnexpectedValueException
     */
    public function getDtoClass(): string;

    /**
     * Setter for used DTO class.
     */
    public function setDtoClass(string $dtoClass): self;

    /**
     * Getter method for current entity name.
     */
    public function getEntityName(): string;

    /**
     * Gets a reference to the entity identified by the given type and
     * identifier without actually loading it, if the entity is not yet
     * loaded.
     *
     * @return object|null
     *
     * @throws ORMException
     */
    public function getReference(string $id);

    /**
     * Getter method for all associations that current entity contains.
     *
     * @return array<int, string>
     */
    public function getAssociations(): array;

    /**
     * Getter method DTO class with loaded entity data.
     *
     * @throws Throwable
     */
    public function getDtoForEntity(
        string $id,
        string $dtoClass,
        RestDtoInterface $dto,
        ?bool $patch = null
    ): RestDtoInterface;

    /**
     * Generic find method to return an array of items from database. Return
     * value is an array of specified repository entities.
     *
     * @param array<int|string, string|array>|null $criteria
     * @param array<string, string>|null $orderBy
     * @param array<string, string>|null $search
     *
     * @return array<int, EntityInterface>
     *
     * @throws Throwable
     */
    public function find(
        ?array $criteria = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $search = null
    ): array;

    /**
     * Generic findOne method to return single item from database. Return value
     * is single entity from specified repository.
     *
     * @throws Throwable
     */
    public function findOne(string $id, ?bool $throwExceptionIfNotFound = null): ?EntityInterface;

    /**
     * Generic findOneBy method to return single item from database by given
     * criteria. Return value is single entity from specified repository or
     * null if entity was not found.
     *
     * @param array<int|string, string|array> $criteria
     * @param array<int, string>|null $orderBy
     *
     * @throws Throwable
     */
    public function findOneBy(
        array $criteria,
        ?array $orderBy = null,
        ?bool $throwExceptionIfNotFound = null
    ): ?EntityInterface;

    /**
     * Generic count method to return entity count for specified criteria and
     * search terms.
     *
     * @param array<int|string, string|array>|null $criteria
     * @param array<string, string>|null $search
     *
     * @throws Throwable
     */
    public function count(?array $criteria = null, ?array $search = null): int;

    /**
     * Generic method to create new item (entity) to specified database
     * repository. Return value is created entity for specified repository.
     *
     * @throws Throwable
     */
    public function create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface;

    /**
     * Generic method to update specified entity with new data.
     *
     * @throws Throwable
     */
    public function update(
        string $id,
        RestDtoInterface $dto,
        ?bool $flush = null,
        ?bool $skipValidation = null
    ): EntityInterface;

    /**
     * Generic method to patch specified entity with new data.
     *
     * throws Throwable
     */
    public function patch(
        string $id,
        RestDtoInterface $dto,
        ?bool $flush = null,
        ?bool $skipValidation = null
    ): EntityInterface;

    /**
     * Generic method to delete specified entity from database.
     *
     * @throws Throwable
     */
    public function delete(string $id, ?bool $flush = null): EntityInterface;

    /**
     * Generic ids method to return an array of id values from database. Return
     * value is an array of specified repository entity id values.
     *
     * @param array<int|string, string|array>|null $criteria
     * @param array<string, string>|null $search
     *
     * @return array<int, string>
     */
    public function getIds(?array $criteria = null, ?array $search = null): array;

    /**
     * Generic method to save given entity to specified repository. Return
     * value is created entity.
     *
     * @throws Throwable
     */
    public function save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation = null): EntityInterface;
}
