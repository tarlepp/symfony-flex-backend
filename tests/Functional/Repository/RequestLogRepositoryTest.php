<?php
declare(strict_types=1);
/**
 * /tests/Functional/Repository/RequestLogRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Repository;

use App\Repository\RequestLogRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RequestLogRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestLogRepositoryTest extends KernelTestCase
{
    /**
     * @var RequestLogRepository;
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(RequestLogRepository::class);
    }

    public function testThatCleanHistoryReturnsExpected(): void
    {
        static::assertSame(0,  $this->repository->cleanHistory());
    }
}
