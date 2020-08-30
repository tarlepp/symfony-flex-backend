<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Utils/HealthzServiceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HealthzServiceTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatCheckMethodCallsExpectedRepositoryMethods(): void
    {
        /** @var MockObject|HealthzRepository $mockRepository */
        $mockRepository = $this->getMockBuilder(HealthzRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepository
            ->expects(static::once())
            ->method('cleanup');

        $mockRepository
            ->expects(static::once())
            ->method('create');

        $mockRepository
            ->expects(static::once())
            ->method('read');

        (new HealthzService($mockRepository))
            ->check();
    }
}
