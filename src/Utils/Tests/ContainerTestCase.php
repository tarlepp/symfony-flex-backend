<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/ContainerTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContainerTestCase
 *
 * @package App\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class ContainerTestCase extends KernelTestCase
{
    private ?ContainerInterface $testContainer = null;

    public function getContainer(): ContainerInterface
    {
        if (!($this->testContainer instanceof ContainerInterface)) {
            self::bootKernel();

            $this->testContainer = self::$container;
        }

        return $this->testContainer;
    }
}
