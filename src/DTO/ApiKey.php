<?php
declare(strict_types = 1);
/**
 * /src/DTO/ApiKey.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO;

use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\EntityInterface;
use App\Entity\UserGroup as UserGroupEntity;
use Symfony\Component\Validator\Constraints as Assert;
use function array_map;

/**
 * Class ApiKey
 *
 * @package App\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method self patch(RestDtoInterface $dto): RestDtoInterface
 * @method self update(EntityInterface $entity): EntityInterface
 */
class ApiKey extends RestDto
{
    /**
     * @var mixed[]
     */
    protected static $mappings = [
        'userGroups' => 'updateUserGroups',
    ];

    /**
     * @var string|null
     */
    protected $id;

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
     * @var string[]|UserGroupEntity[]
     */
    protected $userGroups = [];

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return ApiKey
     */
    public function setId(?string $id = null): self
    {
        $this->setVisited('id');

        $this->id = $id;

        return $this;
    }

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
     * @return string[]|UserGroupEntity[]
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param string[]|UserGroupEntity[] $userGroups
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
     * @param EntityInterface|ApiKeyEntity $entity
     *
     * @return RestDtoInterface|ApiKey
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        /**
         * @param UserGroupEntity $userGroup
         *
         * @return string
         */
        $iterator = static function (UserGroupEntity $userGroup): string {
            return $userGroup->getId();
        };

        if ($entity instanceof ApiKeyEntity) {
            $this->id = $entity->getId();
            $this->token = $entity->getToken();
            $this->description = $entity->getDescription();
            $this->userGroups = $entity->getUserGroups()->map($iterator)->toArray();
        }

        return $this;
    }

    /**
     * Method to update ApiKey entity user groups.
     *
     * @param ApiKeyEntity      $entity
     * @param UserGroupEntity[] $value
     *
     * @return ApiKey
     */
    protected function updateUserGroups(ApiKeyEntity $entity, array $value): self
    {
        $entity->clearUserGroups();

        array_map([$entity, 'addUserGroup'], $value);

        return $this;
    }
}
