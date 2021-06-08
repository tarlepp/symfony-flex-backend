<?php
declare(strict_types = 1);
/**
 * /src/Repository/UserRepository.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Repository;

use App\Entity\User as Entity;
use App\Rest\UuidHelper;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use function array_key_exists;

/**
 * Class UserRepository
 *
 * @package App\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method Entity|null findAdvanced(string $id, string | int | null $hydrationMode = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Entity[] findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @method Entity[] findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null)
 * @method Entity[] findAll()
 *
 * @codingStandardsIgnoreEnd
 */
class UserRepository extends BaseRepository
{
    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['username', 'firstName', 'lastName', 'email'];

    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private string $environment,
    ) {
    }

    /**
     * Method to check if specified username is available or not.
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
     * @param string $email Email to check
     * @param string|null $id User id to ignore
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
     * @see http://symfony2-document.readthedocs.org/en/latest/cookbook/security/entity_provider.html
     *      #managing-roles-in-the-database
     *
     * @param string $username The username
     * @param bool $uuid Is username parameter UUID or not
     *
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $username, bool $uuid): ?Entity
    {
        /** @var array<string, Entity|null> $cache */
        static $cache = [];

        if (!array_key_exists($username, $cache) || $this->environment === 'test') {
            // Build query
            $queryBuilder = $this
                ->createQueryBuilder('u')
                ->select('u, g, r')
                ->leftJoin('u.userGroups', 'g')
                ->leftJoin('g.role', 'r');

            if ($uuid) {
                $queryBuilder
                    ->where('u.id = :uuid')
                    ->setParameter('uuid', $username, UuidBinaryOrderedTimeType::NAME);
            } else {
                $queryBuilder
                    ->where('u.username = :username OR u.email = :email')
                    ->setParameter('username', $username)
                    ->setParameter('email', $username);
            }

            $query = $queryBuilder->getQuery();

            // phpcs:disable
            /** @var Entity|null $result */
            $result = $query->getOneOrNullResult();

            $cache[$username] = $result;
            // phpcs:enable
        }

        return $cache[$username] instanceof Entity ? $cache[$username] : null;
    }

    /**
     * @param string $column Column to check
     * @param string $value Value of specified column
     * @param string|null $id User id to ignore
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
                ->setParameter('id', $id, UuidHelper::getType($id));
        }

        return $query->getQuery()->getOneOrNullResult() === null;
    }
}
