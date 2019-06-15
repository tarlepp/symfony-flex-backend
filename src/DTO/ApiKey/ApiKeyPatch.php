<?php
declare(strict_types = 1);
/**
 * /src/DTO/ApiKey/ApiKeyPatch.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\ApiKey;

use App\Entity\ApiKey as Entity;
use App\Entity\UserGroup as UserGroupEntity;

/**
 * Class ApiKeyPatch
 *
 * @package App\DTO\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyPatch extends ApiKey
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Method to update ApiKey entity user groups.
     *
     * @param Entity            $entity
     * @param UserGroupEntity[] $value
     *
     * @return ApiKey
     */
    protected function updateUserGroups(Entity $entity, array $value): ApiKey
    {
        array_map([$entity, 'addUserGroup'], $value);

        return $this;
    }
}
