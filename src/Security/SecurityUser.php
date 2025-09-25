<?php
declare(strict_types = 1);
/**
 * /src/Security/SecurityUser.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security;

use App\Entity\User;
use App\Enum\Language;
use App\Enum\Locale;
use Deprecated;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var non-empty-string
     */
    private readonly string $identifier;
    private readonly string $password;
    private readonly Language $language;
    private readonly Locale $locale;
    private readonly string $timezone;

    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        User $user,
        private readonly array $roles = [],
    ) {
        $this->identifier = $user->getId();
        $this->password = $user->getPassword();
        $this->language = $user->getLanguage();
        $this->locale = $user->getLocale();
        $this->timezone = $user->getTimezone();
    }

    public function getUuid(): string
    {
        return $this->getUserIdentifier();
    }

    #[Override]
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @codeCoverageIgnore
     */
    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @codeCoverageIgnore
     */
    #[Override]
    #[Deprecated]
    public function eraseCredentials(): void
    {
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
