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
     * @var MockObject|HealthzRepository
     */
    private $repository;

    /**
     * @throws Throwable
     *
     * @testdox Test that `HealthzService::check` method calls expected repository methods
     */
    public function testThatCheckMethodCallsExpectedRepositoryMethods(): void
    {
        $this->repository
            ->expects(static::once())
            ->method('cleanup');

        $this->repository
            ->expects(static::once())
            ->method('create');

        $this->repository
            ->expects(static::once())
            ->method('read');

        (new HealthzService($this->repository))
            ->check();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getMockBuilder(HealthzRepository::class)->disableOriginalConstructor()->getMock();
    }
}
