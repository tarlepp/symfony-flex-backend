<?php
declare(strict_types=1);
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
class ApiKeyUser implements UserInterface
{
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
        $this->username = $apiKey->getId();

        // Attach base 'ROLE_API' for API user
        $roles = [RolesService::ROLE_API];

        // Iterate API key user groups and attach those roles for API user
        $apiKey->getUserGroups()->map(function (UserGroup $userGroup) use (&$roles) {
            $roles[] = $userGroup->getRole()->getId();
        });

        $this->roles = \array_unique($rolesService->getInheritedRoles($roles));
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return array (Role|string)[] The user roles
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
     */
    public function getPassword(): void
    {
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     */
    public function getSalt(): void
    {
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
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
     */
    public function eraseCredentials(): void
    {
    }
}
