<?php
declare(strict_types=1);
/**
 * /src/Repository/RoleRepository.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository;

use App\Entity\Role as Entity;
use Doctrine\ORM\EntityRepository;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */

/**
 * Class RoleRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method Entity|null find($id, $lockMode = null, $lockVersion = null)
 */
class RoleRepository extends EntityRepository
{
    /**
     * Helper method to 'reset' repository entity table - in other words delete all records - so be carefully with
     * this...
     *
     * @return int
     */
    public function reset(): int
    {
        // Create query builder
        $queryBuilder = $this->createQueryBuilder('entity');

        // Define delete query
        $queryBuilder->delete();

        // Return deleted row count
        return $queryBuilder->getQuery()->execute();
    }
}
