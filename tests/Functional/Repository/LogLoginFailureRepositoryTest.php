<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/LogLoginFailureRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Entity\LogLoginFailure;
use App\Entity\User;
use App\Repository\LogLoginFailureRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function assert;

/**
 * Class LogLoginFailureRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LogLoginFailureRepositoryTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `clear` method works as expected
     */
    public function testThatClearMethodWorksAssExpected(): void
    {
        self::bootKernel();

        $userRepository = self::getContainer()->get(UserRepository::class);
        $logLoginFailureRepository = self::getContainer()->get(LogLoginFailureRepository::class);

        self::assertInstanceOf(UserRepository::class, $userRepository);
        self::assertInstanceOf(LogLoginFailureRepository::class, $logLoginFailureRepository);

        $user = $userRepository->find('20000000-0000-1000-8000-000000000001');

        self::assertInstanceOf(User::class, $user);

        $entity = new LogLoginFailure($user);

        $logLoginFailureRepository->save($entity);

        self::assertCount(1, $logLoginFailureRepository->findAll());
        self::assertSame(1, $logLoginFailureRepository->clear($user));
        self::assertCount(0, $logLoginFailureRepository->findAll());
    }
}
