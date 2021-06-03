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
use Symfony\Component\Serializer\Annotation\Groups;
use function array_merge;
use function array_unique;

/**
 * Class ApiKeyUser
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUser implements ApiKeyUserInterface
{
    /**
     * @Groups({
     *      "ApiKeyUser",
     *      "ApiKeyUser.apiKey",
     *  })
     */
    private string $username;

    /**
     * @Groups({
     *      "ApiKeyUser.apiKey",
     *  })
     */
    private ApiKey $apiKey;

    /**
     * @var array<int, string>
     *
     * @Groups({
     *      "ApiKeyUser",
     *      "ApiKeyUser.roles",
     *  })
     */
    private array $roles;

    /**
     * {@inheritdoc}
     */
    public function __construct(ApiKey $apiKey, array $roles)
    {
        $this->apiKey = $apiKey;
        $this->username = $this->apiKey->getToken();
        $this->roles = array_unique(array_merge($roles, [RolesService::ROLE_API]));
    }

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
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
        return '';
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
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * @todo Remove this when this `getUsername` is removed from main interface (Symfony 6.0)
     *
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }
}
