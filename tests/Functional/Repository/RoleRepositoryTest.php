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
use function assert;

/**
 * Class RoleRepositoryTest
 *
 * @package Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleRepositoryTest extends KernelTestCase
{
    private ?RoleRepository $repository = null;

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

        assert(static::$container->get(RoleRepository::class) instanceof RoleRepository);

        $this->repository = static::$container->get(RoleRepository::class);
    }

    /**
     * @throws Throwable
     */
    public function testThatResetMethodDeletesAllRecords(): void
    {
        static::assertSame(5, $this->getRepository()->countAdvanced());
        static::assertSame(5, $this->getRepository()->reset());
        static::assertSame(0, $this->getRepository()->countAdvanced());
    }

    private function getRepository(): RoleRepository
    {
        assert($this->repository instanceof RoleRepository);

        return $this->repository;
    }
}
