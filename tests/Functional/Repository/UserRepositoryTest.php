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
use App\Resource\UserResource;
use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

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
    private $userRepository;

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

        $this->userRepository = static::$kernel->getContainer()->get(UserResource::class)->getRepository();
    }

    /**
     * @covers \App\Repository\UserRepository::countAdvanced()
     */
    public function testThatCountAdvancedReturnsExpected(): void
    {
        static::assertSame(6, $this->userRepository->countAdvanced());
    }

    /**
     * @covers \App\Repository\UserRepository::findByAdvanced()
     */
    public function testThatFindByAdvancedReturnsExpected(): void
    {
        $users = $this->userRepository->findByAdvanced(['username' => 'john']);

        static::assertCount(1, $users);
    }

    /**
     * @covers \App\Repository\UserRepository::findIds()
     */
    public function testThatFindIdsReturnsExpected(): void
    {
        static::assertCount(5, $this->userRepository->findIds([], ['or' => 'john-']));
    }

    /**
     * @covers \App\Repository\UserRepository::isUsernameAvailable()
     */
    public function testThatIsUsernameAvailableMethodReturnsExpected(): void
    {
        $iterator = function (User $user, bool $expected) {
            return [
                $expected,
                $user->getUsername(),
                $expected ? $user->getId() : null,
            ];
        };

        $users = $this->userRepository->findAll();

        $data = \array_merge(
            \array_map($iterator, $users, \array_fill(0, \count($users), true)),
            \array_map($iterator, $users, \array_fill(0, \count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $username, $id] = $set;

            /** @noinspection DisconnectedForeachInstructionInspection */
            static::assertSame($expected, $this->userRepository->isUsernameAvailable($username, $id));
        }
    }

    /**
     * @covers \App\Repository\UserRepository::isEmailAvailable()
     */
    public function testThatIsEmailAvailableMethodReturnsExpected(): void
    {
        $iterator = function (User $user, bool $expected) {
            return [
                $expected,
                $user->getEmail(),
                $expected ? $user->getId() : null,
            ];
        };

        $users = $this->userRepository->findAll();

        $data = \array_merge(
            \array_map($iterator, $users, \array_fill(0, \count($users), true)),
            \array_map($iterator, $users, \array_fill(0, \count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $email, $id] = $set;

            /** @noinspection DisconnectedForeachInstructionInspection */
            static::assertSame($expected, $this->userRepository->isEmailAvailable($email, $id));
        }
    }

    /**
     * @covers \App\Repository\UserRepository::countAdvanced()
     * @covers \App\Repository\UserRepository::reset()
     *
     * @depends testThatIsUsernameAvailableMethodReturnsExpected
     * @depends testThatIsEmailAvailableMethodReturnsExpected
     */
    public function testThatResetMethodDeletesAllRecords(): void
    {
        static::assertSame(6, $this->userRepository->countAdvanced());
        static::assertSame(6, $this->userRepository->reset());
        static::assertSame(0, $this->userRepository->countAdvanced());
    }
}
