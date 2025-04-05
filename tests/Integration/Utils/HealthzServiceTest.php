<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/HealthzServiceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Utils;

use App\Repository\HealthzRepository;
use App\Utils\HealthzService;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class HealthzServiceTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `HealthzService::check` method calls expected repository methods')]
    public function testThatCheckMethodCallsExpectedRepositoryMethods(): void
    {
        $repository = $this->getRepository();

        $repository
            ->expects($this->once())
            ->method('cleanup');

        $repository
            ->expects($this->once())
            ->method('create');

        $repository
            ->expects($this->once())
            ->method('read');

        new HealthzService($repository)
            ->check();
    }

    /**
     * @phpstan-return MockObject&HealthzRepository
     */
    private function getRepository(): MockObject
    {
        return $this->getMockBuilder(HealthzRepository::class)->disableOriginalConstructor()->getMock();
    }
}
