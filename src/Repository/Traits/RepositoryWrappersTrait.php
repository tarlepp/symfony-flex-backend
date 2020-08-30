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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Throwable;
use UnexpectedValueException;

/**
 * Class RepositoryWrappersTrait
 *
 * @package App\Repository\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RepositoryWrappersTrait
{
    protected ManagerRegistry $managerRegistry;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    public function getAssociations(): array
    {
        return $this->getClassMetaData()->getAssociationMappings();
    }

    public function getClassMetaData(): ClassMetadataInfo
    {
        return $this->getEntityManager()->getClassMetadata($this->getEntityName());
    }

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
