<?php
declare(strict_types=1);
/**
 * /tests/Functional/Repository/LogLoginFailureRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Repository;

use App\Entity\User;
use App\Repository\LogLoginFailureRepository;
use App\Resource\LogLoginFailureResource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LogLoginFailureRepositoryTest
 *
 * @package App\Tests\Functional\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginFailureRepositoryTest extends KernelTestCase
{
    /**
     * @var LogLoginFailureRepository;
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(LogLoginFailureResource::class)->getRepository();
    }

    public function testThatClearReturnsExpected(): void
    {
        static::markTestIncomplete('TODO implemented this test');
    }
}
