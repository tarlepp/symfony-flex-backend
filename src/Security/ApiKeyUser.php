<?php
declare(strict_types = 1);
/**
 * /src/Security/ApiKeyUser.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Security;

use App\Entity\ApiKey;
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
    private ApiKey $apiKey;

    /**
     * @var array<int, string>
     */
    private array $roles;

    /**
     * {@inheritdoc}
     */
    public function __construct(ApiKey $apiKey, array $roles)
    {
        $this->apiKey = $apiKey;
        $this->identifier = $this->apiKey->getToken();
        $this->roles = array_unique(array_merge($roles, [RolesService::ROLE_API]));
    }

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
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

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @todo Remove this method when Symfony 6.0.0 is released
     *
     * @codeCoverageIgnore
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }
}
