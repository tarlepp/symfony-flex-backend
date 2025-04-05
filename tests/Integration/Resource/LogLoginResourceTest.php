<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/LogLoginResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogLogin;
use App\Repository\BaseRepository;
use App\Repository\LogLoginRepository;
use App\Resource\LogLoginResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
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
