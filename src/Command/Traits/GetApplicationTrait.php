<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/GetApplicationTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\Traits;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Trait GetApplicationTrait
 *
 * @package App\Command\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait GetApplicationTrait
{
    /**
     * @throws RuntimeException
     */
    public function getApplication(): Application
    {
        if (!($this instanceof Command)) {
            throw new RuntimeException('This trait ' . __TRAIT__ . ' is only mentioned to use with console commands.');
        }

        /** @noinspection PhpUndefinedClassInspection */
        $application = parent::getApplication();

        if ($application === null) {
            throw new RuntimeException('Cannot determine application for console command to use.');
        }

        return $application;
    }
}
