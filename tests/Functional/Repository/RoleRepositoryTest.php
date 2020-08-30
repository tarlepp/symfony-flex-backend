<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/RoleRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleRepositoryTest extends KernelTestCase
{
    /**
     * @var RoleRepository;
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

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$container->get(RoleRepository::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatResetMethodDeletesAllRecords(): void
    {
        static::assertSame(5, $this->repository->countAdvanced());
        static::assertSame(5, $this->repository->reset());
        static::assertSame(0, $this->repository->countAdvanced());
    }
}
