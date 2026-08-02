<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Resource/LogRequestResourceTest.php
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogRequest;
use App\Repository\BaseRepository;
use App\Repository\LogRequestRepository;
use App\Resource\LogRequestResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

final class LogRequestResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = LogRequest::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = LogRequestRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = LogRequestResource::class;
}
