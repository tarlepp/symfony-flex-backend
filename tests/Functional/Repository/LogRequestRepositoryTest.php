<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Repository/LogRequestRepositoryTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Functional\Repository;

use App\Repository\LogRequestRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Functional\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LogRequestRepositoryTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatCleanHistoryReturnsExpected(): void
    {
        $repository = self::getContainer()->get(LogRequestRepository::class);

        self::assertSame(0, $repository->cleanHistory());
    }
}
