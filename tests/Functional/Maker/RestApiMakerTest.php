<?php
declare(strict_types=1);

namespace App\Tests\Functional\Maker;

use App\Maker\RestApiMaker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class RestApiMakerTest
 *
 * @package App\Tests\Functional\Maker
 */
class RestApiMakerTest extends KernelTestCase
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $targetDir;

    public function setUp()
    {
        $this->targetDir = \sys_get_temp_dir() .'/' . \uniqid('sf_maker_', true);
        $this->fs = new Filesystem();
        $this->fs->mkdir($this->targetDir);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->fs->remove($this->targetDir);
    }

    public function testThatCommandRunsWithSuccess(): void
    {
        $maker = new RestApiMaker();
        $inputs = [
            'Book',
            'TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>',
            'Library',
        ];

        $command = new MakerCommand($maker, $this->createGenerator());
        $command->setCheckDependencies(false);

        $tester = new CommandTester($command);
        $tester->setInputs($inputs);
        $tester->execute(array());

        $this->assertContains('Success', $tester->getDisplay());
    }

    /**
     * @expectedException \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException
     * @expectedExceptionMessage "123Book" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)
     */
    public function testThatCommandReturnsAnErrorWithInvalidInput(): void
    {
        $maker = new RestApiMaker();
        $inputs = [
            '123Book',
            'TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>',
            'Library',
        ];

        $command = new MakerCommand($maker, $this->createGenerator());

        $tester = new CommandTester($command);
        $tester->setInputs($inputs);
        $tester->execute(array());

        $this->assertContains('ERROR', $tester->getDisplay());
    }

    /**
     * @return Generator
     */
    private function createGenerator(): Generator
    {
        return new Generator(new FileManager(new Filesystem(), $this->targetDir));
    }
}
