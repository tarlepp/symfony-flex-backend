<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/LoginLogTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\LoginLog;

/**
 * Class LoginLogTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLogTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = LoginLog::class;
}
