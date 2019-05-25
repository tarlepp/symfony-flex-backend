<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/RoleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\Role;

/**
 * Class RoleTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = Role::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Generic method to test that getId method returns a string and it is UUID V4 format
     */
    public function testThatGetIdReturnsUuidString(): void
    {
        static::markTestSkipped();
    }
}
