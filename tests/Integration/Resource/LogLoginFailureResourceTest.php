<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/LogLoginFailureResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\LogLoginFailure;
use App\Entity\User;
use App\Repository\LogLoginFailureRepository;
use App\Resource\LogLoginFailureResource;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class LogLoginFailureResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginFailureResourceTest extends ResourceTestCase
{
    protected string $entityClass = LogLoginFailure::class;
    protected string $repositoryClass = LogLoginFailureRepository::class;
    protected string $resourceClass = LogLoginFailureResource::class;

    public function testThatResetMethodCallsExpectedRepositoryMethod(): void
    {
        /** @var MockObject|LogLoginFailureRepository $repository */
        $repository = $this->getMockBuilder($this->repositoryClass)->disableOriginalConstructor()->getMock();

        $user = (new User())->setUsername('username');

        $repository
            ->expects(static::once())
            ->method('clear')
            ->with($user)
            ->willReturn(0);

        (new LogLoginFailureResource($repository))->reset($user);
    }
}
