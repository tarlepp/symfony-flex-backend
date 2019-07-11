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
use App\Entity\EntityInterface;
use App\Entity\UserGroup as UserGroupEntity;
use App\Entity\UserGroupAwareInterface;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use function array_map;

/**
 * Class ApiKey
 *
 * @package App\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method self|RestDtoInterface  get(string $id): RestDtoInterface
 * @method self|RestDtoInterface  patch(RestDtoInterface $dto): RestDtoInterface
 * @method Entity|EntityInterface update(EntityInterface $entity): EntityInterface
 */
class ApiKey extends RestDto
{
    /**
     * @var array<string, string>
     */
    protected static $mappings = [
        'userGroups' => 'updateUserGroups',
    ];

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    protected $description;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var array<int, UserGroupEntity>
     *
     * @AppAssert\EntityReferenceExists()
     */
    protected $userGroups = [];

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return ApiKey
     */
    public function setToken(string $token): self
    {
        $this->setVisited('token');

        $this->token = $token;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ApiKey
     */
    public function setDescription(string $description): self
    {
        $this->setVisited('description');

        $this->description = $description;

        return $this;
    }

    /**
     * @return UserGroupEntity[]
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param array<int, UserGroupEntity> $userGroups
     *
     * @return ApiKey
     */
    public function setUserGroups(array $userGroups): self
    {
        $this->setVisited('userGroups');

        $this->userGroups = $userGroups;

        return $this;
    }

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface|Entity $entity
     *
     * @return RestDtoInterface|ApiKey
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        if ($entity instanceof Entity) {
            $this->id = $entity->getId();
            $this->token = $entity->getToken();
            $this->description = $entity->getDescription();

            /** @var array<int, UserGroupEntity> $userGroups */
            $userGroups = $entity->getUserGroups()->toArray();

            $this->userGroups = $userGroups;
        }

        return $this;
    }

    /**
     * Method to update ApiKey entity user groups.
     *
     * @param UserGroupAwareInterface $entity
     * @param UserGroupEntity[]       $value
     */
    protected function updateUserGroups(UserGroupAwareInterface $entity, array $value): void
    {
        $entity->clearUserGroups();

        array_map([$entity, 'addUserGroup'], $value);
    }
}
