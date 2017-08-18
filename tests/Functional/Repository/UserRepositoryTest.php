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
use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Security\Core\User\User as CoreUser;

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

    public static function tearDownAfterClass(): void
    {
        $application = new Application(static::$kernel);

        $command = new LoadDataFixturesDoctrineCommand();

        $application->add($command);

        $input = new ArrayInput([
            'command'           => 'doctrine:fixtures:load',
            '--no-interaction'  => true,
            '--fixtures'        => 'src/DataFixtures/',
        ]);

        $input->setInteractive(false);

        $command->run($input, new ConsoleOutput(ConsoleOutput::VERBOSITY_QUIET));
    }

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(UserRepository::class);
    }

    /**
     * @expectedException \Doctrine\ORM\NoResultException
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionWithInvalidUsername(): void
    {
        $this->repository->loadUserByUsername('foobar');
    }

    /**
     * @expectedException \Doctrine\ORM\NoResultException
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
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Instance of "Symfony\Component\Security\Core\User\User" is not supported.
     */
    public function testThatRefreshUserThrowsAnExceptionIfUserClassIsNotSupported(): void
    {
        $user = new CoreUser('test', 'password');

        $this->repository->refreshUser($user);
    }

    public function testThatCountAdvancedReturnsExpected(): void
    {
        static::assertSame(5, $this->repository->countAdvanced());
    }

    public function testThatFindByAdvancedReturnsExpected(): void
    {
        $users = $this->repository->findByAdvanced(['username' => 'john']);

        static::assertCount(1, $users);
    }

    public function testThatFindIdsReturnsExpected(): void
    {
        static::assertCount(4, $this->repository->findIds([], ['or' => 'john-']));
    }

    /**
     * @depends testThatIsUsernameAvailableMethodReturnsExpected
     */
    public function testThatResetMethodDeletesAllRecords(): void
    {
        $this->repository->reset();

        self::assertSame(0, $this->repository->countAdvanced());
    }

    public function testThatIsUsernameAvailableMethodReturnsExpected(): void
    {
        $iterator = function (User $user, bool $expected) {
            return [
                $expected,
                $user->getUsername(),
                $expected ? $user->getId() : null,
            ];
        };

        $users = $this->repository->findAll();

        $data = \array_merge(
            \array_map($iterator, $users, \array_fill(0, \count($users), true)),
            \array_map($iterator, $users, \array_fill(0, \count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $username, $id] = $set;

            /** @noinspection DisconnectedForeachInstructionInspection */
            self::assertSame($expected, $this->repository->isUsernameAvailable($username, $id));
        }
    }

    public function testThatIsEmailAvailableMethodReturnsExpected(): void
    {
        $iterator = function (User $user, bool $expected) {
            return [
                $expected,
                $user->getEmail(),
                $expected ? $user->getId() : null,
            ];
        };

        $users = $this->repository->findAll();

        $data = \array_merge(
            \array_map($iterator, $users, \array_fill(0, \count($users), true)),
            \array_map($iterator, $users, \array_fill(0, \count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $email, $id] = $set;

            /** @noinspection DisconnectedForeachInstructionInspection */
            self::assertSame($expected, $this->repository->isEmailAvailable($email, $id));
        }
    }
}
