<?php
declare(strict_types = 1);
/**
 * /src/DTO/ApiKey/ApiKey.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\ApiKey;

use App\DTO\RestDto;
use App\DTO\RestDtoInterface;
use App\Entity\ApiKey as Entity;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Interfaces\UserGroupAwareInterface;
use App\Entity\UserGroup as UserGroupEntity;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use function array_map;

/**
 * Class ApiKey
 *
 * @package App\DTO
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class ApiKey extends RestDto
{
    /**
     * @var array<string, string>
     */
    protected static array $mappings = [
        'userGroups' => 'updateUserGroups',
    ];

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    protected string $description = '';

    protected string $token = '';

    /**
     * @var UserGroupEntity[]|array<int, UserGroupEntity>
     *
     * @AppAssert\EntityReferenceExists(entityClass=UserGroupEntity::class)
     */
    protected array $userGroups = [];

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->setVisited('token');

        $this->token = $token;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->setVisited('description');

        $this->description = $description;

        return $this;
    }

    /**
     * @return array<int, UserGroupEntity>
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param array<int, UserGroupEntity> $userGroups
     */
    public function setUserGroups(array $userGroups): self
    {
        $this->setVisited('userGroups');

        $this->userGroups = $userGroups;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param EntityInterface|Entity $entity
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        if ($entity instanceof Entity) {
            $this->id = $entity->getId();
            $this->token = $entity->getToken();
            $this->description = $entity->getDescription();

            /** @var array<int, UserGroupEntity> $groups */
            $groups = $entity->getUserGroups()->toArray();

            $this->userGroups = $groups;
        }

        return $this;
    }

    /**
     * Method to update ApiKey entity user groups.
     *
     * @param array<int, UserGroupEntity> $value
     */
    protected function updateUserGroups(UserGroupAwareInterface $entity, array $value): void
    {
        $entity->clearUserGroups();

        array_map([$entity, 'addUserGroup'], $value);
    }
}
