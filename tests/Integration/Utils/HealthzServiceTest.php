<?php
declare(strict_types=1);
/**
 * /tests/Integration/Utils/HealthzServiceTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Utils;

use App\Repository\HealthzRepository;
use App\Utils\HealthzService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class HealthzServiceTest
 *
 * @package App\Tests\Integration\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HealthzServiceTest extends KernelTestCase
{
    public function testThatCheckMethodCallsExpectedRepositoryMethods(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|HealthzRepository $mockRepository */
        $mockRepository = $this->createMock(HealthzRepository::class);

        $mockRepository
            ->expects(static::once())
            ->method('cleanup');

        $mockRepository
            ->expects(static::once())
            ->method('create');

        $mockRepository
            ->expects(static::once())
            ->method('read')
            ->willReturn('');

        $healthzService = new HealthzService($mockRepository);
        $healthzService->check();
    }
}
