<?php
declare(strict_types=1);
/**
 * /src/Rest/DTO/UserGroup.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DTO;

use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Entity\EntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserGroup
 *
 * @package App\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroup extends RestDto
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 4, max = 255)
     */
    private $name = '';

    /**
     * @var \App\Entity\Role
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private $role;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return UserGroup
     */
    public function setName(string $name): UserGroup
    {
        $this->setVisited('name');

        $this->name = $name;

        return $this;
    }

    /**
     * @return RoleEntity|string|null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param RoleEntity $role
     *
     * @return UserGroup
     */
    public function setRole(RoleEntity $role): UserGroup
    {
        $this->setVisited('role');

        $this->role = $role;

        return $this;
    }

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface|UserGroupEntity $entity
     *
     * @return RestDtoInterface|UserGroup
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        $this->name = $entity->getName();
        $this->role = $entity->getRole()->getId();

        return $this;
    }

    /**
     * Method to update specified entity with DTO data.
     *
     * @param EntityInterface|UserGroupEntity $entity
     *
     * @return EntityInterface|UserGroupEntity
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        $entity->setName($this->name);
        $entity->setRole($this->role);

        return $entity;
    }
}
