<?php
declare(strict_types = 1);
/**
 * /src/Security/SecurityUser.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class SecurityUser
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUser implements UserInterface
{
    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var string[]
     */
    private $roles = [];

    /**
     * SecurityUser constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->username = $user->getId();
        $this->password = $user->getPassword();
    }

    /**
     * @inheritDoc
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param string[] $roles
     *
     * @return SecurityUser
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string
     */
    public function getPassword(): string
    {
        /** @noinspection UnnecessaryCastingInspection */
        return (string)($this->password ?? '');
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     */
    public function getSalt(): void
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return (string)$this->username;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
        unset($this->password);
    }
}
