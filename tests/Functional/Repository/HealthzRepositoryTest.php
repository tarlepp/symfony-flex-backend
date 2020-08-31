<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/HealthzRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Functional\Repository;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;
use App\Utils\Tests\PhpUnitUtil;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HealthzRepositoryTest extends KernelTestCase
{
    /**
     * @var HealthzRepository;
     */
    private $repository;

    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        static::bootKernel();

        PhpUnitUtil::loadFixtures(static::$kernel);

        static::$kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$container->get(HealthzRepository::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatReadValueMethodReturnsExpectedWithEmptyDatabase(): void
    {
        PhpUnitUtil::loadFixtures(static::$kernel);

        static::assertNull($this->repository->read());
    }

    /**
     * @depends testThatReadValueMethodReturnsExpectedWithEmptyDatabase
     *
     * @throws Throwable
     */
    public function testThatCreateValueReturnsExpected(): void
    {
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(Healthz::class, $this->repository->create());
    }

    /**
     * @depends testThatCreateValueReturnsExpected
     *
     * @throws Throwable
     */
    public function testThatReadValueReturnExpectedAfterCreate(): void
    {
        static::assertNotNull($this->repository->read());
    }

    /**
     * @depends testThatReadValueReturnExpectedAfterCreate
     *
     * @throws Exception
     */
    public function testThatCleanupMethodClearsDatabaseReturnsExpected(): void
    {
        static::assertSame(0, $this->repository->cleanup());
    }
}
