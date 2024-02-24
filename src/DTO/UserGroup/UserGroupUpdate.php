<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/UserGroup/UserGroupUpdate.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\DTO\UserGroup;

use App\Entity\Role;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\DTO\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupUpdate extends UserGroup
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[AppAssert\EntityReferenceExists(entityClass: Role::class)]
    protected ?Role $role = null;
}
