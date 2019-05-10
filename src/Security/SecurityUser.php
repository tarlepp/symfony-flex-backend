<?php
declare(strict_types = 1);
/**
 * /src/Security/SecurityUser.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
     * @inheritDoc
     */
    public function getPassword(): string
    {
        /** @noinspection UnnecessaryCastingInspection */
        return (string)($this->password ?? '');
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): void
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return (string)$this->username;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
        unset($this->password);
    }
}
