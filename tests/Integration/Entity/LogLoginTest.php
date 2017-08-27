<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/LogLoginTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\LogLogin;

/**
 * Class LogLoginTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = LogLogin::class;
}
