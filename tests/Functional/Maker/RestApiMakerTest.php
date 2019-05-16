<?php
declare(strict_types=1);

namespace App\Tests\Functional\Maker;

use App\Maker\RestApiMaker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
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

    public function testThatCommandRunsWithSuccess(): void
    {
        $maker = new RestApiMaker();
        $inputs = [
            'Book',
            'TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>',
            'Library',
        ];

        $command = new MakerCommand($maker, $this->createFileManager(), $this->createGenerator());
        $command->setCheckDependencies(false);

        $tester = new CommandTester($command);
        $tester->setInputs($inputs);
        $tester->execute(array());

        static::assertContains('Success', $tester->getDisplay());

        // Clean up files
        $this->fs->remove($maker->getCreatedFiles());
    }

    /**
     * @expectedException \Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException
     * @expectedExceptionMessage "App\Controller\123BookController" is not valid as a PHP class name (it must start with a letter or underscore, followed by any number of letters, numbers, or underscores)
     */
    public function testThatCommandReturnsAnErrorWithInvalidInput(): void
    {
        $maker = new RestApiMaker();
        $inputs = [
            '123Book',
            'TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>',
            'Library',
        ];

        $command = new MakerCommand($maker, $this->createFileManager(), $this->createGenerator());

        $tester = new CommandTester($command);
        $tester->setInputs($inputs);
        $tester->execute([]);

        static::assertContains('ERROR', $tester->getDisplay());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fs = new Filesystem();
    }

    /**
     * @return FileManager
     */
    private function createFileManager(): FileManager
    {
        $autoLoaderUtil = new AutoloaderUtil(new ComposerAutoloaderFinder());

        return new FileManager($this->fs, $autoLoaderUtil, __DIR__ . '/../../../');
    }

    /**
     * @return Generator
     */
    private function createGenerator(): Generator
    {
        return new Generator($this->createFileManager(), 'App');
    }
}
