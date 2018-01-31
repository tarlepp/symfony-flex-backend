<?php
declare(strict_types=1);
/**
 * /tests/Integration/Security/UserProviderTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Security;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Security\UserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User as CoreUser;

/**
 * Class UserProviderTest
 *
 * @package App\Tests\Integration\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserProviderTest extends KernelTestCase
{
    /**
     * @var UserProvider
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityClass = User::class;

    /**
     * @var string
     */
    protected $repositoryClass = UserProvider::class;

    /**
     * @return EntityManagerInterface|Object
     */
    private static function getEntityManager(): EntityManagerInterface
    {
        /** @noinspection MissingService */
        return static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

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
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testThatRefreshUserThrowsAnExceptionWhenNotSupportedUserInterfaceIsUsed(): void
    {
        $user = new CoreUser('username', 'password');

        $this->repository->refreshUser($user);

        unset($user);
    }

    /**
     * @expectedException \Doctrine\ORM\NoResultException
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserIsNotFound(): void
    {
        $this->repository->refreshUser(new User());
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

    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        static::bootKernel();

        $this->repository = new $this->repositoryClass(
            static::getEntityManager(),
            new ClassMetadata($this->entityClass)
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->repository);

        gc_collect_cycles();
    }
}
