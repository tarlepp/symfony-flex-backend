<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Resource/LogLoginResourceTest.php
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogLogin;
use App\Repository\BaseRepository;
use App\Repository\LogLoginRepository;
use App\Resource\LogLoginResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

final class LogLoginResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = LogLogin::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = LogLoginRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = LogLoginResource::class;
}
