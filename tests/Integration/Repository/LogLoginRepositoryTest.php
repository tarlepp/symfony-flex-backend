<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Integration/LogLoginRepositoryTest.php
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogLogin;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\LogLoginRepository;
use App\Resource\LogLoginResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @method LogLoginResource getResource()
 * @method LogLoginRepository getRepository()
 */
final class LogLoginRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = LogLogin::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = LogLoginRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = LogLoginResource::class;

    /**
     * @var array<int, string>
     */
    protected array $associations = [
        'user',
    ];
}
