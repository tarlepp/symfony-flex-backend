<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/UserGroup/UserGroup.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\DTO\UserGroup;

use App\DTO\RestDto;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role as RoleEntity;
use App\Entity\UserGroup as Entity;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserGroup
 *
 * @package App\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class UserGroup extends RestDto
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 4, max: 255)]
    protected string $name = '';

    #[AppAssert\EntityReferenceExists(entityClass: RoleEntity::class)]
    protected ?RoleEntity $role = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->setVisited('name');

        $this->name = $name;

        return $this;
    }

    public function getRole(): ?RoleEntity
    {
        return $this->role;
    }

    public function setRole(RoleEntity $role): self
    {
        $this->setVisited('role');

        $this->role = $role;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param EntityInterface|Entity $entity
     */
    public function load(EntityInterface $entity): self
    {
        if ($entity instanceof Entity) {
            $this->name = $entity->getName();
            $this->role = $entity->getRole();
        }

        return $this;
    }
}
