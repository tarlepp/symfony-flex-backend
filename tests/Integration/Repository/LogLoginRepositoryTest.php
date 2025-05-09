<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/LogLoginRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
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
