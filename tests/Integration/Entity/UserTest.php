<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/UserTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\User;
use App\Tests\Helpers\EntityTestCase;

/**
 * Class UserTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = User::class;
}
