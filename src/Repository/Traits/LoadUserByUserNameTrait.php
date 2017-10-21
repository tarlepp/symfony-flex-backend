<?php
declare(strict_types = 1);
/**
 * /src/Repository/Traits/LoadUserByUserNameTrait.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Repository\Traits;

use App\Entity\User as Entity;
use Doctrine\ORM\QueryBuilder;

/**
 * Trait LoadUserByUserNameTrait
 *
 * @package App\Repository\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method QueryBuilder createQueryBuilder(string $alias = null, string $indexBy = null): QueryBuilder
 */
trait LoadUserByUserNameTrait
{
    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not found.
     *
     * Method is override for performance reasons see link below.
     *
     * @link http://symfony2-document.readthedocs.org/en/latest/cookbook/security/entity_provider.html
     *       #managing-roles-in-the-database
     *
     * @param string $username The username
     *
     * @return null|Entity
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($username): ?Entity
    {
        // Build query
        $query = $this
            ->createQueryBuilder('u')
            ->select('u, g, r')
            ->leftJoin('u.userGroups', 'g')
            ->leftJoin('g.role', 'r')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery();

        return $query->getOneOrNullResult();
    }
}
