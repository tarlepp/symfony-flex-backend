<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/UserRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\User as CoreUser;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserRepositoryTest extends RepositoryTestCase
{
    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityName = User::class;

    /**
     * @var string
     */
    protected $repositoryName = UserRepository::class;

    /**
     * @var array
     */
    protected $associations = [
        'userGroups',
    ];

    /**
     * @var array
     */
    protected $searchColumns = [
        'username',
        'firstname',
        'surname',
        'email',
    ];

    /**
     * @dataProvider dataProviderTestThatSupportsClassMethodReturnsExpected
     *
     * @param bool   $expected
     * @param string $class
     */
    public function testThatSupportsClassMethodReturnsExpected(bool $expected, string $class): void
    {
        if ($expected) {
            self::assertTrue($this->repository->supportsClass($class));
        } else {
            self::assertFalse($this->repository->supportsClass($class));
        }
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Instance of "Symfony\Component\Security\Core\User\User" is not supported.
     */
    public function testThatRefreshUserThrowsAnExceptionWhenNotSupportedUserInterfaceIsUsed(): void
    {
        $user = new CoreUser('username', 'password');

        $this->repository->refreshUser($user);
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

    /**
     * @return array
     */
    public function dataProviderTestThatSupportsClassMethodReturnsExpected(): array
    {
        return [
            [true, User::class],
            [false, UserGroup::class],
            [false, CoreUser::class],
        ];
    }
}
