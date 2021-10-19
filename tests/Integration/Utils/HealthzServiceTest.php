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
use UnexpectedValueException;

/**
 * Class HealthzServiceTest
 *
 * @package App\Tests\Integration\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HealthzServiceTest extends KernelTestCase
{
    private MockObject | HealthzRepository | null $repository = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getMockBuilder(HealthzRepository::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `HealthzService::check` method calls expected repository methods
     */
    public function testThatCheckMethodCallsExpectedRepositoryMethods(): void
    {
        $this->getRepositoryMock()
            ->expects(self::once())
            ->method('cleanup');

        $this->getRepositoryMock()
            ->expects(self::once())
            ->method('create');

        $this->getRepositoryMock()
            ->expects(self::once())
            ->method('read');

        (new HealthzService($this->getRepository()))
            ->check();
    }

    private function getRepository(): HealthzRepository
    {
        return $this->repository instanceof HealthzRepository
            ? $this->repository
            : throw new UnexpectedValueException('Repository not set');
    }

    private function getRepositoryMock(): MockObject
    {
        return $this->repository instanceof MockObject
            ? $this->repository
            : throw new UnexpectedValueException('Repository not set');
    }
}
