<?php
declare(strict_types = 1);

/**
 * /src/Rest/DTO/User/UserPatch.php
 */

namespace App\DTO\User;

use App\DTO\Traits\PatchUserGroups;

class UserPatch extends User
{
    use PatchUserGroups;
}
