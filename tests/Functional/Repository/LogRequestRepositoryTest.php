<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/LogRequestRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Functional\Repository;

use App\Repository\LogRequestRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class LogRequestRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogRequestRepositoryTest extends KernelTestCase
{
    /**
     * @var LogRequestRepository;
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$container->get(LogRequestRepository::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatCleanHistoryReturnsExpected(): void
    {
        static::assertSame(0, $this->repository->cleanHistory());
    }
}
