<?php
declare(strict_types = 1);
/**
 * /src/Security/SecurityUser.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security;

use App\Entity\User;
use App\Security\Interfaces\SecurityUserInterface;

/**
 * Class SecurityUser
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUser implements SecurityUserInterface
{
    private string $username;
    private string $password;
    private string $language;
    private string $locale;
    private string $timezone;

    /**
     * @var array<int, string>
     */
    private array $roles;

    /**
     * SecurityUser constructor.
     *
     * @param array<int, string> $roles
     */
    public function __construct(User $user, array $roles = [])
    {
        $this->username = $user->getId();
        $this->password = $user->getPassword();
        $this->language = $user->getLanguage();
        $this->locale = $user->getLocale();
        $this->timezone = $user->getTimezone();
        $this->roles = $roles;
    }

    public function getUuid(): string
    {
        return $this->getUsername();
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int, string> The user roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function eraseCredentials(): void
    {
    }
}
