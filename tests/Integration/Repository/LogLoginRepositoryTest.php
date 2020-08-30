<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/LogLoginRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\LogLogin;
use App\Repository\LogLoginRepository;
use App\Resource\LogLoginResource;

/**
 * Class LogLoginRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginRepositoryTest extends RepositoryTestCase
{
    protected string $entityName = LogLogin::class;
    protected string $repositoryName = LogLoginRepository::class;
    protected string $resourceName = LogLoginResource::class;
    protected array $associations = [
        'user',
    ];
}
