<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Security/SecurityUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class SecurityUserTest extends KernelTestCase
{
    /**
     * @testdox Test that `SecurityUser::getRoles` method returns expected roles
     */
    public function testThatGetRolesReturnsExpected(): void
    {
        $securityUser = new SecurityUser(new User(), ['Foo', 'Bar']);

        static::assertSame(['Foo', 'Bar'], $securityUser->getRoles());
    }

    /**
     * @testdox Test that `SecurityUser::getPassword` method returns expected when using `str_rot13` as encoder
     */
    public function testThatGetPasswordReturnsExpected(): void
    {
        $encoder = fn (string $password): string => str_rot13($password);

        $securityUser = new SecurityUser((new User())->setPassword($encoder, 'foobar'));

        static::assertSame('sbbone', $securityUser->getPassword());
    }

    /**
     * @testdox Test that `SecurityUser::getSalt` method returns null
     */
    public function testThatGetSaltReturnNothing(): void
    {
        static::assertNull((new SecurityUser(new User()))->getSalt());
    }

    /**
     * @testdox Test that `SecurityUser::getUsername` method returns expected UUID
     */
    public function testThatGetUsernameReturnsExpected(): void
    {
        $user = new User();

        static::assertSame($user->getId(), (new SecurityUser($user))->getUsername());
    }

    /**
     * @testdox Test that `SecurityUser::getUuid` method returns expected UUID
     */
    public function testThatGetUuidReturnsExpected(): void
    {
        $user = new User();

        static::assertSame($user->getId(), (new SecurityUser($user))->getUuid());
    }

    /**
     * @testdox Test that password is present after `SecurityUser::eraseCredentials` method call
     */
    public function testThatPasswordIsPresentAfterEraseCredential(): void
    {
        $encoder = fn (string $password): string => str_rot13($password);

        $securityUser = new SecurityUser((new User())->setPassword($encoder, 'foobar'));

        $securityUser->eraseCredentials();

        static::assertSame('sbbone', $securityUser->getPassword());
    }
}
