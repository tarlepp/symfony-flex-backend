<?php
declare(strict_types = 1);
/**
 * /src/Resource/UserResource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\User as Entity;
use App\Entity\UserGroup;
use App\Repository\UserRepository as Repository;
use App\Rest\RestResource;
use App\Security\RolesService;
use Throwable;
use function array_filter;
use function array_values;
use function in_array;

/**
 * Class UserResource
 *
 * @package App\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity getReference(string $id)
 * @method Repository getRepository()
 * @method array<int, Entity> find(?array $criteria = null, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null)
 * @method Entity|null findOne(string $id, ?bool $throwExceptionIfNotFound = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?bool $throwExceptionIfNotFound = null)
 * @method Entity create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null)
 * @method Entity update(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null)
 * @method Entity patch(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null)
 * @method Entity delete(string $id, ?bool $flush = null)
 * @method Entity save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation = null)
 *
 * @codingStandardsIgnoreEnd
 */
class UserResource extends RestResource
{
    private RolesService $rolesService;

    /**
     * Class constructor.
     */
    public function __construct(Repository $repository, RolesService $rolesService)
    {
        $this->setRepository($repository);

        $this->rolesService = $rolesService;
    }

    /**
     * Method to fetch users for specified user group, note that this method will also check user role inheritance so
     * return value will contain all users that belong to specified user group via role inheritance.
     *
     * @return array<int, Entity>
     *
     * @throws Throwable
     */
    public function getUsersForGroup(UserGroup $userGroup): array
    {
        /**
         * Filter method to see if specified user belongs to certain user group.
         *
         * @param Entity $user
         *
         * @return bool
         */
        $filter = fn (Entity $user): bool => in_array(
            $userGroup->getRole()->getId(),
            $this->rolesService->getInheritedRoles($user->getRoles()),
            true
        );

        $users = $this->find();

        return array_values(array_filter($users, $filter));
    }
}
