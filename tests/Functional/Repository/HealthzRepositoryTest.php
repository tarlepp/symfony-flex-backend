<?php
declare(strict_types=1);
/**
 * /tests/Functional/Repository/HealthzRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Repository;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;
use App\Resource\HealthzResource;
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
class HealthzRepositoryTest extends KernelTestCase
{
    /**
     * @var HealthzRepository;
     */
    private $repository;

    private static $initialized = false;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        if (!self::$initialized) {
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

            self::$initialized = true;
        }

        $this->repository = static::$kernel->getContainer()->get(HealthzResource::class)->getRepository();
    }

    /**
     * @covers \App\Repository\HealthzRepository::read()
     */
    public function testThatReadValueMethodReturnsExpectedWithEmptyDatabase(): void
    {
        static::assertNull($this->repository->read());
    }

    /**
     * @covers \App\Repository\HealthzRepository::create()
     *
     * @depends testThatReadValueMethodReturnsExpectedWithEmptyDatabase
     */
    public function testThatCreateValueReturnsExpected(): void
    {
        static::assertInstanceOf(Healthz::class, $this->repository->create());
    }

    /**
     * @covers \App\Repository\HealthzRepository::read()
     *
     * @depends testThatCreateValueReturnsExpected
     */
    public function testThatReadValueReturnExpectedAfterCreate(): void
    {
        static::assertNotNull($this->repository->read());
    }

    /**
     * @covers \App\Repository\HealthzRepository::cleanup()
     *
     * @depends testThatReadValueReturnExpectedAfterCreate
     */
    public function testThatCleanupMethodClearsDatabaseReturnsExpected(): void
    {
        static::assertSame(0, $this->repository->cleanup());
    }
}
