<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\ApiKey;
use App\Security\Interfaces\RolesServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function strlen;

/**
 * Class ApiKeyTest
 *
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyTest extends KernelTestCase
{
    /**
     * @testdox Test that token is generated on creation of ApiKey entity
     */
    public function testThatTokenIsGenerated(): void
    {
        self::assertSame(40, strlen((new ApiKey())->getToken()));
    }

    /**
     * @testdox Test that ApiKey entity has `ROLE_API` role
     */
    public function testThatGetRolesContainsExpectedRole(): void
    {
        self::assertContainsEquals(RolesServiceInterface::ROLE_API, (new ApiKey())->getRoles());
    }
}
