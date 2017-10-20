<?php
declare(strict_types = 1);
/**
 * /src/Security/ApiKeyUser.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Security;

use App\Entity\ApiKey;
use App\Entity\UserGroup;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ApiKeyUser
 *
 * @package App\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUser implements ApiKeyUserInterface, UserInterface
{
    /**
     * @var ApiKey
     */
    private $apiKey;

    /**
     * @var string
     */
    private $username;

    /**
     * @var array
     */
    private $roles;

    /**
     * ApiKeyUser constructor.
     *
     * @param ApiKey       $apiKey
     * @param RolesService $rolesService
     */
    public function __construct(ApiKey $apiKey, RolesService $rolesService)
    {
        $this->apiKey = $apiKey;

        $this->username = $this->apiKey->getId();

        // Attach base 'ROLE_API' for API user
        $roles = [RolesService::ROLE_API];

        // Iterate API key user groups and attach those roles for API user
        $this->apiKey->getUserGroups()->map(function (UserGroup $userGroup) use (&$roles) {
            $roles[] = $userGroup->getRole()->getId();
        });

        $this->roles = \array_unique($rolesService->getInheritedRoles($roles));
    }

    /**
     * Getter method for ApiKey entity
     *
     * @return ApiKey
     */
    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    /**
     * Returns the roles granted to the api user.
     *
     * @return string[] The user roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @codeCoverageIgnore
     */
    public function getPassword(): void
    {
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @codeCoverageIgnore
     */
    public function getSalt(): void
    {
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     *
     * @codeCoverageIgnore
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     *
     * @codeCoverageIgnore
     */
    public function eraseCredentials(): void
    {
    }
}
