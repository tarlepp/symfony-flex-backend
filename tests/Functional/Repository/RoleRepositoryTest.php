<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/RoleRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Repository\RoleRepository;
use App\Tests\Utils\PhpUnitUtil;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class RoleRepositoryTest extends KernelTestCase
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
    public function testThatResetMethodDeletesAllRecords(): void
    {
        $repository = self::getContainer()->get(RoleRepository::class);

        self::assertSame(5, $repository->countAdvanced());
        self::assertSame(5, $repository->reset());
        self::assertSame(0, $repository->countAdvanced());
    }
}
