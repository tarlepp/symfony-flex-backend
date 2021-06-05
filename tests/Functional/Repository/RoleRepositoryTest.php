<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/RoleRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Repository\RoleRepository;
use App\Utils\Tests\PhpUnitUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class RoleRepositoryTest
 *
 * @package Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleRepositoryTest extends KernelTestCase
{
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
    public function testThatResetMethodDeletesAllRecords(): void
    {
        /** @var RoleRepository $repository */
        $repository = static::getContainer()->get(RoleRepository::class);

        static::assertSame(5, $repository->countAdvanced());
        static::assertSame(5, $repository->reset());
        static::assertSame(0, $repository->countAdvanced());
    }
}
