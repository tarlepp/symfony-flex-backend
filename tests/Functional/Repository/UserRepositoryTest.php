<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/UserRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Tests\PhpUnitUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function array_fill;
use function array_map;
use function array_merge;
use function count;

/**
 * Class UserRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserRepositoryTest extends KernelTestCase
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
    public function testThatCountAdvancedReturnsExpected(): void
    {
        static::assertSame(6, $this->getRepository()->countAdvanced());
    }

    /**
     * @throws Throwable
     */
    public function testThatFindByAdvancedReturnsExpected(): void
    {
        $users = $this->getRepository()->findByAdvanced(['username' => 'john']);

        static::assertCount(1, $users);
    }

    public function testThatFindIdsReturnsExpected(): void
    {
        static::assertCount(5, $this->getRepository()->findIds([], ['or' => 'john-']));
    }

    /**
     * @throws Throwable
     */
    public function testThatIsUsernameAvailableMethodReturnsExpected(): void
    {
        $iterator = static fn (User $user, bool $expected): array => [
            $expected,
            $user->getUsername(),
            $expected ? $user->getId() : null,
        ];

        $users = $this->getRepository()->findAll();

        $data = array_merge(
            array_map($iterator, $users, array_fill(0, count($users), true)),
            array_map($iterator, $users, array_fill(0, count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $username, $id] = $set;

            static::assertSame($expected, $this->getRepository()->isUsernameAvailable($username, $id));
        }
    }

    /**
     * @throws Throwable
     */
    public function testThatIsEmailAvailableMethodReturnsExpected(): void
    {
        $iterator = static fn (User $user, bool $expected): array => [
            $expected,
            $user->getEmail(),
            $expected ? $user->getId() : null,
        ];

        $users = $this->getRepository()->findAll();

        $data = array_merge(
            array_map($iterator, $users, array_fill(0, count($users), true)),
            array_map($iterator, $users, array_fill(0, count($users), false))
        );

        foreach ($data as $set) {
            [$expected, $email, $id] = $set;

            static::assertSame($expected, $this->getRepository()->isEmailAvailable($email, $id));
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
        static::assertSame(6, $this->getRepository()->countAdvanced());
        static::assertSame(6, $this->getRepository()->reset());
        static::assertSame(0, $this->getRepository()->countAdvanced());
    }

    private function getRepository(): UserRepository
    {
        static $cache;

        if ($cache === null) {
            static::bootKernel();

            $cache = static::getContainer()->get(UserRepository::class);
        }

        return $cache;
    }
}
