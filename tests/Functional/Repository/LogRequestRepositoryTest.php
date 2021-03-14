<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/LogRequestRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Repository\LogRequestRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function assert;

/**
 * Class LogRequestRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LogRequestRepositoryTest extends KernelTestCase
{
    private ?LogRequestRepository $repository = null;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        assert(static::$container->get(LogRequestRepository::class) instanceof LogRequestRepository);

        $this->repository = static::$container->get(LogRequestRepository::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatCleanHistoryReturnsExpected(): void
    {
        static::assertSame(0, $this->getRepository()->cleanHistory());
    }

    private function getRepository(): LogRequestRepository
    {
        assert($this->repository instanceof LogRequestRepository);

        return $this->repository;
    }
}
