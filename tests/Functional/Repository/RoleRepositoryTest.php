<?php
declare(strict_types=1);
/**
 * /tests/Functional/Repository/RoleRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Repository;

use App\Repository\RoleRepository;
use App\Resource\RoleResource;
use App\Utils\Tests\PHPUnitUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RoleRepositoryTest
 *
 * @package Functional\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleRepositoryTest extends KernelTestCase
{
    /**
     * @var RoleRepository;
     */
    private $repository;

    /**
     * @throws \Exception
     */
    public static function tearDownAfterClass(): void
    {
        PHPUnitUtil::loadFixtures(static::$kernel);
    }

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(RoleResource::class)->getRepository();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testThatResetMethodDeletesAllRecords(): void
    {
        self::assertSame(5, $this->repository->countAdvanced());
        self::assertSame(5, $this->repository->reset());
        self::assertSame(0, $this->repository->countAdvanced());
    }
}
