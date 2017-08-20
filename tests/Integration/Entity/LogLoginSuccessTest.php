<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/LogLoginSuccessTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\LogLoginSuccess;

/**
 * Class LogLoginSuccessTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginSuccessTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = LogLoginSuccess::class;
}
