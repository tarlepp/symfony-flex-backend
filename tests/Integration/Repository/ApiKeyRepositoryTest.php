<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Resource\ApiKeyResource;

/**
 * Class ApiKeyRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyRepositoryTest extends RepositoryTestCase
{
    protected string $entityName = ApiKey::class;
    protected string $repositoryName = ApiKeyRepository::class;
    protected string $resourceName = ApiKeyResource::class;
    protected array $associations = [
        'userGroups',
        'logsRequest',
        'createdBy',
        'updatedBy',
    ];
}
