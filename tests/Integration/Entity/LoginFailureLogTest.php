<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/LoginFailureLogTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\LoginFailureLog;

/**
 * Class LoginFailureLogTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginFailureLogTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = LoginFailureLog::class;
}
