<?php
declare(strict_types = 1);

/**
 * /src/Rest/DTO/User/UserCreate.php
 */

namespace App\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

class UserCreate extends User
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 8, max: 255)]
    protected string $password = '';
}
