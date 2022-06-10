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

/**
 * Class HealthzRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HealthzRepositoryTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();

        PhpUnitUtil::loadFixtures(self::$kernel);

        self::$kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     */
    public function testThatReadValueMethodReturnsExpectedWithEmptyDatabase(): void
    {
        self::bootKernel();

        PhpUnitUtil::loadFixtures(self::$kernel);

        self::assertNull($this->getRepository()->read());
    }

    /**
     * @depends testThatReadValueMethodReturnsExpectedWithEmptyDatabase
     *
     * @throws Throwable
     */
    public function testThatCreateValueReturnsExpected(): void
    {
        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf(Healthz::class, $this->getRepository()->create());
    }

    /**
     * @depends testThatCreateValueReturnsExpected
     *
     * @throws Throwable
     */
    public function testThatReadValueReturnExpectedAfterCreate(): void
    {
        self::assertNotNull($this->getRepository()->read());
    }

    /**
     * @depends testThatReadValueReturnExpectedAfterCreate
     *
     * @throws Exception
     */
    public function testThatCleanupMethodClearsDatabaseReturnsExpected(): void
    {
        self::assertSame(0, $this->getRepository()->cleanup());
    }

    private function getRepository(): HealthzRepository
    {
        $repository = self::getContainer()->get(HealthzRepository::class);

        self::assertInstanceOf(HealthzRepository::class, $repository);

        return $repository;
    }
}
