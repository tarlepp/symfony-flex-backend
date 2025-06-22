<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/LogRequestResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogRequest;
use App\Repository\BaseRepository;
use App\Repository\LogRequestRepository;
use App\Resource\LogRequestResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
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
