<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/HealthzRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Repository\HealthzRepository;
use App\Tests\Utils\PhpUnitUtil;
use DateTimeImmutable;
use DateTimeZone;
use Override;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class HealthzRepositoryTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[Override]
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        PhpUnitUtil::loadFixtures($kernel);

        $kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     */
    public function testThatReadValueMethodReturnsExpectedWithEmptyDatabase(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        PhpUnitUtil::loadFixtures($kernel);

        self::assertNull($this->getRepository()->read());
    }

    /**
     * @throws Throwable
     */
    #[Depends('testThatReadValueMethodReturnsExpectedWithEmptyDatabase')]
    public function testThatCreateValueReturnsExpected(): void
    {
        self::assertEqualsWithDelta(
            new DateTimeImmutable('now', new DateTimeZone('utc'))->getTimestamp(),
            $this->getRepository()->create()->getCreatedAt()->getTimestamp(),
            1,
        );
    }

    /**
     * @throws Throwable
     */
    #[Depends('testThatCreateValueReturnsExpected')]
    public function testThatReadValueReturnExpectedAfterCreate(): void
    {
        self::assertNotNull($this->getRepository()->read());
    }

    /**
     * @throws Throwable
     */
    #[Depends('testThatReadValueReturnExpectedAfterCreate')]
    public function testThatCleanupMethodClearsDatabaseReturnsExpected(): void
    {
        self::assertSame(0, $this->getRepository()->cleanup());
    }

    /**
     * @throws Throwable
     */
    private function getRepository(): HealthzRepository
    {
        return self::getContainer()->get(HealthzRepository::class);
    }
}
