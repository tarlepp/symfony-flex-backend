<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Entity/UserGroupTest.php
 */

namespace App\Tests\Integration\Entity;

use App\Entity\UserGroup;
use App\Tests\Integration\TestCase\EntityTestCase;

/**
 * @method UserGroup getEntity()
 */
final class UserGroupTest extends EntityTestCase
{
    /**
     * @var class-string<\App\Entity\Interfaces\EntityInterface>
     */
    protected static string $entityName = UserGroup::class;
}
