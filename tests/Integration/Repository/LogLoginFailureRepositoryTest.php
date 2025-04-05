<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogLoginFailure;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Repository\LogLoginFailureRepository;
use App\Resource\LogLoginFailureResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\TestCase\RepositoryTestCase;

/**
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method LogLoginFailureResource getResource()
 * @method LogLoginFailureRepository getRepository()
 */
final class LogLoginFailureRepositoryTest extends RepositoryTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName = LogLoginFailure::class;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName = LogLoginFailureRepository::class;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName = LogLoginFailureResource::class;

    /**
     * @var array<int, string>
     */
    protected array $associations = [
        'user',
    ];
}
