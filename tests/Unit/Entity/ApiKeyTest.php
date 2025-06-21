<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\ApiKey;
use App\Enum\Role;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function strlen;

/**
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyTest extends KernelTestCase
{
    #[TestDox('Test that token is generated on creation of ApiKey entity')]
    public function testThatTokenIsGenerated(): void
    {
        self::assertSame(40, strlen(new ApiKey()->getToken()));
    }

    #[TestDox('Test that ApiKey entity has `ROLE_API` role')]
    public function testThatGetRolesContainsExpectedRole(): void
    {
        self::assertContainsEquals(Role::API->value, new ApiKey()->getRoles());
    }
}
