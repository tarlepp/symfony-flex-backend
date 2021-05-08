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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class HealthzServiceTest
 *
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HealthzServiceTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `HealthzService::check` method calls expected repository methods
     */
    public function testThatCheckMethodCallsExpectedRepositoryMethods(): void
    {
        $healthzRepositoryMock = $this->getHealthzRepositoryMock();

        $healthzRepositoryMock
            ->expects(static::once())
            ->method('cleanup');

        $healthzRepositoryMock
            ->expects(static::once())
            ->method('create');

        $healthzRepositoryMock
            ->expects(static::once())
            ->method('read');

        (new HealthzService($healthzRepositoryMock))
            ->check();
    }

    /**
     * @return MockObject&HealthzRepository
     */
    private function getHealthzRepositoryMock(): MockObject | HealthzRepository
    {
        return $this->getMockBuilder(HealthzRepository::class)->disableOriginalConstructor()->getMock();
    }
}
