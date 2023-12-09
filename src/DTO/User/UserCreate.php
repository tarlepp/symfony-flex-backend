<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/User/UserCreate.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserCreate
 *
 * @package App\DTO\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserCreate extends User
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 8, max: 255)]
    protected string $password = '';
}
