<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/UserGroupTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\UserGroup;

/**
 * Class UserGroupTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = UserGroup::class;
}
