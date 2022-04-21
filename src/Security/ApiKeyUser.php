<?php
declare(strict_types = 1);
/**
 * /src/Security/ApiKeyUser.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security;

use App\Entity\ApiKey;
use App\Enum\Role;
use App\Security\Interfaces\ApiKeyUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function array_merge;
use function array_unique;

/**
 * Class ApiKeyUser
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUser implements ApiKeyUserInterface, UserInterface
{
    private string $identifier;
    private string $apiKeyIdentifier;

    /**
     * @var array<int, string>
     */
    private array $roles;

    /**
     * {@inheritdoc}
     */
    public function __construct(ApiKey $apiKey, array $roles)
    {
        $this->identifier = $apiKey->getToken();
        $this->apiKeyIdentifier = $apiKey->getId();
        $this->roles = array_unique(array_merge($roles, [Role::ROLE_API->value]));
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getApiKeyIdentifier(): string
    {
        return $this->apiKeyIdentifier;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getPassword(): ?string
    {
        return null;
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
    public function eraseCredentials(): void
    {
    }
}
