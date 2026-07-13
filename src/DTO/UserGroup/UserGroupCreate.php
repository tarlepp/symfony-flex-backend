<?php
declare(strict_types = 1);

/**
 * /src/Rest/DTO/UserGroup/UserGroupCreate.php
 */

namespace App\DTO\UserGroup;

use App\Entity\Role;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class UserGroupCreate extends UserGroup
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[AppAssert\EntityReferenceExists(entityClass: Role::class)]
    protected ?Role $role = null;
}
