<?php
declare(strict_types = 1);
/**
 * /src/Repository/Traits/RepositoryWrappersTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository\Traits;

use App\Rest\UuidHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Throwable;
use UnexpectedValueException;

/**
 * Class RepositoryWrappersTrait
 *
 * @package App\Repository\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method string getEntityName(): string
 */
trait RepositoryWrappersTrait
{
    protected ManagerRegistry $managerRegistry;

    /**
     * Gets a reference to the entity identified by the given type and identifier without actually loading it,
     * if the entity is not yet loaded.
     *
     * @param string $id
     *
     * @return object|null
     *
     * @throws ORMException
     */
    public function getReference(string $id)
    {
        try {
            $referenceId = UuidHelper::fromString($id);
        } catch (InvalidUuidStringException $exception) {
            (static fn (Throwable $exception): string => (string)$exception)($exception);

            $referenceId = $id;
        }

        return $this->getEntityManager()->getReference($this->getEntityName(), $referenceId);
    }

    /**
     * Gets all association mappings of the class.
     *
     * @return array<int, string>
     */
    public function getAssociations(): array
    {
        return $this->getClassMetaData()->getAssociationMappings();
    }

    /**
     * Returns the ORM metadata descriptor for a class.
     *
     * @return ClassMetadataInfo
     */
    public function getClassMetaData(): ClassMetadataInfo
    {
        return $this->getEntityManager()->getClassMetadata($this->getEntityName());
    }

    /**
     * Getter method for EntityManager for current entity.
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        $manager = $this->managerRegistry->getManagerForClass($this->getEntityName());

        if (!($manager instanceof EntityManager)) {
            throw new UnexpectedValueException(
                'Cannot get entity manager for entity \'' . $this->getEntityName() . '\''
            );
        }

        if ($manager->isOpen() === false) {
            $this->managerRegistry->resetManager();

            $manager = $this->getEntityManager();
        }

        return $manager;
    }

    /**
     * Method to create new query builder for current entity.
     *
     * @param string|null $alias
     * @param string|null $indexBy
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(?string $alias = null, ?string $indexBy = null): QueryBuilder
    {
        $alias ??= 'entity';

        // Create new query builder
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select($alias)
            ->from($this->getEntityName(), $alias, $indexBy);
    }
}
