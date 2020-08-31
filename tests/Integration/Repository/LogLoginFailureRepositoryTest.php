<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\LogLoginFailure;
use App\Repository\LogLoginFailureRepository;
use App\Resource\LogLoginFailureResource;

/**
 * Class LogLoginFailureRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginFailureRepositoryTest extends RepositoryTestCase
{
    protected string $entityName = LogLoginFailure::class;
    protected string $repositoryName = LogLoginFailureRepository::class;
    protected string $resourceName = LogLoginFailureResource::class;
    protected array $associations = [
        'user',
    ];
}
