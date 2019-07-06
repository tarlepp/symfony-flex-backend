<?php
declare(strict_types = 1);
/**
 * /src/Repository/UserRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Repository;

use App\Entity\User as Entity;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use function array_key_exists;

/**
 * Class UserRepository
 *
 * @package App\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null                     find(string $id, ?int $lockMode = null, ?int $lockVersion = null): ?Entity
 * @method array<int|string, mixed>|Entity findAdvanced(string $id, $hydrationMode = null)
 * @method Entity|null                     findOneBy(array $criteria, ?array $orderBy = null): ?Entity
 * @method array<int, Entity>              findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
 * @method array<int, Entity>              findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null): array
 * @method array<int, Entity>              findAll(): array
 *
 * @codingStandardsIgnoreEnd
 */
class UserRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected static $entityName = Entity::class;

    /**
     * Names of search columns.
     *
     * @var string[]
     */
    protected static $searchColumns = ['username', 'firstName', 'lastName', 'email'];

    /**
     * @var string
     */
    private $environment = 'dev';

    /**
     * @required
     *
     * @param string $environment
     *
     * @return UserRepository
     */
    public function setEnvironment(string $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * Method to check if specified username is available or not.
     *
     * @param string      $username
     * @param string|null $id
     *
     * @return bool
     *
     * @throws NonUniqueResultException
     */
    public function isUsernameAvailable(string $username, ?string $id = null): bool
    {
        return $this->isUnique('username', $username, $id);
    }

    /**
     * Method to check if specified email is available or not.
     *
     * @param string      $email Email to check
     * @param string|null $id    User id to ignore
     *
     * @return bool
     *
     * @throws NonUniqueResultException
     */
    public function isEmailAvailable(string $email, ?string $id = null): bool
    {
        return $this->isUnique('email', $email, $id);
    }

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
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @param string $username The username
     *
     * @return Entity|null
     *
     * @throws ORMException
     */
    public function loadUserByUsername($username): ?Entity
    {
        static $cache = [];

        if (!array_key_exists($username, $cache) || $this->environment === 'test') {
            // Build query
            $query = $this
                ->createQueryBuilder('u')
                ->select('u, g, r')
                ->leftJoin('u.userGroups', 'g')
                ->leftJoin('g.role', 'r')
                ->where('u.id = :username OR u.username = :username OR u.email = :email')
                ->setParameter('username', $username)
                ->setParameter('email', $username)
                ->getQuery();

            $cache[$username] = $query->getOneOrNullResult() ?? false;
        }

        return $cache[$username] instanceof Entity ? $cache[$username] : null;
    }

    /**
     * @param string      $column Column to check
     * @param string      $value  Value of specified column
     * @param string|null $id     User id to ignore
     *
     * @return bool
     *
     * @throws NonUniqueResultException
     */
    private function isUnique(string $column, string $value, ?string $id = null): bool
    {
        // Build query
        $query = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.' . $column . ' = :value')
            ->setParameter('value', $value);

        if ($id !== null) {
            $query
                ->andWhere('u.id <> :id')
                ->setParameter('id', $id);
        }

        return $query->getQuery()->getOneOrNullResult() === null;
    }
}
