<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/HealthzRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;
use App\Utils\Tests\PhpUnitUtil;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function assert;

/**
 * Class HealthzRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HealthzRepositoryTest extends KernelTestCase
{
    private ?HealthzRepository $repository = null;

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

        assert(static::$container->get(HealthzRepository::class) instanceof HealthzRepository);

        $this->repository = static::$container->get(HealthzRepository::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatReadValueMethodReturnsExpectedWithEmptyDatabase(): void
    {
        PhpUnitUtil::loadFixtures(static::$kernel);

        static::assertNull($this->getRepository()->read());
    }

    /**
     * @depends testThatReadValueMethodReturnsExpectedWithEmptyDatabase
     *
     * @throws Throwable
     */
    public function testThatCreateValueReturnsExpected(): void
    {
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(Healthz::class, $this->getRepository()->create());
    }

    /**
     * @depends testThatCreateValueReturnsExpected
     *
     * @throws Throwable
     */
    public function testThatReadValueReturnExpectedAfterCreate(): void
    {
        static::assertNotNull($this->getRepository()->read());
    }

    /**
     * @depends testThatReadValueReturnExpectedAfterCreate
     *
     * @throws Exception
     */
    public function testThatCleanupMethodClearsDatabaseReturnsExpected(): void
    {
        static::assertSame(0, $this->getRepository()->cleanup());
    }

    private function getRepository(): HealthzRepository
    {
        assert($this->repository instanceof HealthzRepository);

        return $this->repository;
    }
}
