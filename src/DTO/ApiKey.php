<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/ApiKey.php
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
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private $description;

    /**
     * @var string
     */
    private $token;

    /**
     * @var array
     */
    private $userGroups = [];

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
        $iterator = function (UserGroupEntity $userGroup) {
            return $userGroup->getId();
        };

        $this->id = $entity->getId();
        $this->token = $entity->getToken();
        $this->description = $entity->getDescription();
        $this->userGroups = $entity->getUserGroups()->map($iterator)->toArray();

        return $this;
    }

    /**
     * Method to update specified entity with DTO data.
     *
     * @param EntityInterface|ApiKeyEntity $entity
     *
     * @return EntityInterface|ApiKeyEntity
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        foreach ($this->getVisited() as $property) {
            if ($property === 'userGroups') {
                $entity->clearUserGroups();

                \array_map([$entity, 'addUserGroup'], $this->$property);
            } else {
                // Determine setter method
                $setter = 'set' . \ucfirst($property);

                // Update current dto property value
                $entity->$setter($this->$property);
            }
        }

        return $entity;
    }
}
