<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/UserGroupTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\UserGroup;
use App\Tests\Integration\TestCase\EntityTestCase;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method UserGroup getEntity()
 */
final class UserGroupTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = UserGroup::class;
}
