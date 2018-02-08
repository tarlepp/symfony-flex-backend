<?php
declare(strict_types = 1);
/**
 * /src/DTO/ApiKey.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DTO;

use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\EntityInterface;
use App\Entity\UserGroup as UserGroupEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ApiKey
 *
 * @package App\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKey extends RestDto
{
    static protected $mappings = [
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
     * @var array
     */
    protected $userGroups = [];

    /**
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param null|string $id
     *
     * @return ApiKey
     */
    public function setId(string $id = null): ApiKey
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
    public function setToken(string $token): ApiKey
    {
        $this->setVisited('token');

        $this->token = $token;

        return $this;
    }

    /**
     * @return null|string
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
    public function setDescription(string $description): ApiKey
    {
        $this->setVisited('description');

        $this->description = $description;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param array $userGroups
     *
     * @return ApiKey
     */
    public function setUserGroups(array $userGroups): ApiKey
    {
        $this->setVisited('userGroups');

        $this->userGroups = $userGroups;

        return $this;
    }

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface $entity
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
        $iterator = function (UserGroupEntity $userGroup) {
            return $userGroup->getId();
        };

        /** @var ApiKeyEntity $entity */
        $this->id = $entity->getId();
        $this->token = $entity->getToken();
        $this->description = $entity->getDescription();
        $this->userGroups = $entity->getUserGroups()->map($iterator)->toArray();

        return $this;
    }

    /**
     * Method to update ApiKey entity user groups.
     *
     * @param ApiKeyEntity $entity
     * @param array        $value
     *
     * @return ApiKey
     */
    protected function updateUserGroups(ApiKeyEntity $entity, array $value): ApiKey
    {
        $entity->clearUserGroups();

        \array_map([$entity, 'addUserGroup'], $value);

        return $this;
    }
}
