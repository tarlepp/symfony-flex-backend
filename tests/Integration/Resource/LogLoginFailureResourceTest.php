<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/LogLoginFailureResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogLoginFailure;
use App\Entity\User;
use App\Repository\BaseRepository;
use App\Repository\LogLoginFailureRepository;
use App\Resource\LogLoginFailureResource;
use App\Rest\RestResource;
use App\Tests\Integration\TestCase\ResourceTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LogLoginFailureResourceTest extends ResourceTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass = LogLoginFailure::class;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass = LogLoginFailureRepository::class;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass = LogLoginFailureResource::class;

    /**
     * @throws Throwable
     */
    public function testThatResetMethodCallsExpectedRepositoryMethod(): void
    {
        $repository = $this->getMockBuilder($this->repositoryClass)->disableOriginalConstructor()->getMock();

        $user = new User()->setUsername('username');

        $repository
            ->expects($this->once())
            ->method('clear')
            ->with($user)
            ->willReturn(0);

        /**
         * @var LogLoginFailureRepository $repository
         */
        new LogLoginFailureResource($repository)->reset($user);
    }
}
