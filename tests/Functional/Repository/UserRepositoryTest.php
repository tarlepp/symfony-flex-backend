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
use App\Utils\Tests\PhpUnitUtil;
use function array_fill;
use function array_map;
use function array_merge;
use function count;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        PhpUnitUtil::loadFixtures(static::$kernel);
    }

    /**
     * @throws Throwable
     */
    public function testThatCountAdvancedReturnsExpected(): void
    {
        static::assertSame(6, $this->userRepository->countAdvanced());
    }

    public function testThatFindByAdvancedReturnsExpected(): void
    {
        $users = $this->userRepository->findByAdvanced(['username' => 'john']);

        static::assertCount(1, $users);
    }

    public function testThatFindIdsReturnsExpected(): void
    {
        static::assertCount(5, $this->userRepository->findIds([], ['or' => 'john-']));
    }

    /**
     * @throws Throwable
     */
    public function testThatIsUsernameAvailableMethodReturnsExpected(): void
    {
        $iterator = static function (User $user, bool $expected) {
            return [
                $expected,
                $user->getUsername(),
                $expected ? $user->getId() : null,
            ];
        };

        $users = $this->userRepository->findAll();

        $data = array_merge(
            array_map($iterator, $users, array_fill(0, count($users), true)),
            array_map($iterator, $users, array_fill(0, count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $username, $id] = $set;

            /** @noinspection DisconnectedForeachInstructionInspection */
            static::assertSame($expected, $this->userRepository->isUsernameAvailable($username, $id));
        }
    }

    /**
     * @throws Throwable
     */
    public function testThatIsEmailAvailableMethodReturnsExpected(): void
    {
        $iterator = static function (User $user, bool $expected) {
            return [
                $expected,
                $user->getEmail(),
                $expected ? $user->getId() : null,
            ];
        };

        $users = $this->userRepository->findAll();

        $data = array_merge(
            array_map($iterator, $users, array_fill(0, count($users), true)),
            array_map($iterator, $users, array_fill(0, count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $email, $id] = $set;

            /** @noinspection DisconnectedForeachInstructionInspection */
            static::assertSame($expected, $this->userRepository->isEmailAvailable($email, $id));
        }
    }

    /**
     * @depends testThatIsUsernameAvailableMethodReturnsExpected
     * @depends testThatIsEmailAvailableMethodReturnsExpected
     *
     * @throws Throwable
     */
    public function testThatResetMethodDeletesAllRecords(): void
    {
        static::assertSame(6, $this->userRepository->countAdvanced());
        static::assertSame(6, $this->userRepository->reset());
        static::assertSame(0, $this->userRepository->countAdvanced());
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->userRepository = static::$container->get(UserRepository::class);
    }
}
