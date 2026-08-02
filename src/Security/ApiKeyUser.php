<?php
declare(strict_types = 1);

/**
 * /src/Security/ApiKeyUser.php
 */

namespace App\Security;

use App\Entity\ApiKey;
use App\Enum\Role;
use App\Security\Interfaces\ApiKeyUserInterface;
use Override;
use Symfony\Component\Security\Core\User\UserInterface;
use function array_unique;

class ApiKeyUser implements ApiKeyUserInterface, UserInterface
{
    /**
     * @var non-empty-string
     */
    private readonly string $identifier;
    private readonly string $apiKeyIdentifier;

    /**
     * @var array<int, string>
     */
    private readonly array $roles;

    /**
     * {@inheritDoc}
     */
    public function __construct(ApiKey $apiKey, array $roles)
    {
        $this->identifier = $apiKey->getToken();
        $this->apiKeyIdentifier = $apiKey->getId();
        $this->roles = array_unique([...$roles, Role::API->value]);
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getApiKeyIdentifier(): string
    {
        return $this->apiKeyIdentifier;
    }

    #[Override]
    public function getRoles(): array
    {
        return $this->roles;
    }
}
