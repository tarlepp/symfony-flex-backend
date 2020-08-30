<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Security/SecurityUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function str_rot13;

/**
 * Class SecurityUserTest
 *
 * @package App\Tests\Unit\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SecurityUserTest extends KernelTestCase
{
    public function testThatGetRolesReturnsExpected(): void
    {
        $securityUser = new SecurityUser(new User(), ['Foo', 'Bar']);

        static::assertSame(['Foo', 'Bar'], $securityUser->getRoles());
    }

    public function testThatGetPasswordReturnsExpected(): void
    {
        $encoder = fn (string $password): string => str_rot13($password);

        $securityUser = new SecurityUser((new User())
            ->setPassword($encoder, 'foobar'));

        static::assertSame('sbbone', $securityUser->getPassword());
    }

    public function testThatGetSaltReturnNothing(): void
    {
        static::assertNull((new SecurityUser(new User()))->getSalt());
    }

    public function testThatGetUsernameReturnsExpected(): void
    {
        $user = new User();

        static::assertSame($user->getId(), (new SecurityUser($user))->getUsername());
    }

    public function testThatGetUuidReturnsExpected(): void
    {
        $user = new User();

        static::assertSame($user->getId(), (new SecurityUser($user))->getUuid());
    }

    public function testThatPasswordIsPresentAfterEraseCredential(): void
    {
        $encoder = fn (string $password): string => str_rot13($password);

        $securityUser = new SecurityUser((new User())->setPassword($encoder, 'foobar'));

        $securityUser->eraseCredentials();

        static::assertSame('sbbone', $securityUser->getPassword());
    }
}
