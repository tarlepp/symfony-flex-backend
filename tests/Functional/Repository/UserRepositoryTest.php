<?php
declare(strict_types=1);
/**
 * /tests/Functional/Repository/UserRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserRepositoryTest extends KernelTestCase
{
    /**
     * @var UserRepository;
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(UserRepository::class);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessageRegExp /User "\w+" not found/
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionWithInvalidUsername(): void
    {
        $this->repository->loadUserByUsername('foobar');
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage User "test" not found
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserIsNotFound(): void
    {
        $user = new User();
        $user->setUsername('test');

        $this->repository->refreshUser($user);
    }

    public function testThatRefreshUserReturnsCorrectUser(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['username' => 'john']);

        static::assertSame($user->getId(), $this->repository->refreshUser($user)->getId());
    }
}
