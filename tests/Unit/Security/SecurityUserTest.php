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
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function str_rot13;

/**
 * @package App\Tests\Unit\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class SecurityUserTest extends KernelTestCase
{
    #[TestDox('Test that `SecurityUser::getRoles` method returns expected roles')]
    public function testThatGetRolesReturnsExpected(): void
    {
        $securityUser = new SecurityUser(new User(), ['Foo', 'Bar']);

        self::assertSame(['Foo', 'Bar'], $securityUser->getRoles());
    }

    #[TestDox('Test that `SecurityUser::getPassword` method returns expected when using `str_rot13` as encoder')]
    public function testThatGetPasswordReturnsExpected(): void
    {
        $encoder = fn (string $password): string => str_rot13($password);

        $securityUser = new SecurityUser(new User()->setPassword($encoder, 'foobar'));

        self::assertSame('sbbone', $securityUser->getPassword());
    }

    #[TestDox('Test that `SecurityUser::getSalt` method returns null')]
    public function testThatGetSaltReturnNothing(): void
    {
        self::assertNull(new SecurityUser(new User())->getSalt());
    }

    #[TestDox('Test that `SecurityUser::getUsername` method returns expected UUID')]
    public function testThatGetUsernameReturnsExpected(): void
    {
        $user = new User();

        self::assertSame($user->getId(), new SecurityUser($user)->getUserIdentifier());
    }

    #[TestDox('Test that `SecurityUser::getUuid` method returns expected UUID')]
    public function testThatGetUuidReturnsExpected(): void
    {
        $user = new User();

        self::assertSame($user->getId(), new SecurityUser($user)->getUuid());
    }

    #[TestDox('Test that password is present after `SecurityUser::eraseCredentials` method call')]
    public function testThatPasswordIsPresentAfterEraseCredential(): void
    {
        $encoder = fn (string $password): string => str_rot13($password);

        $securityUser = new SecurityUser(new User()->setPassword($encoder, 'foobar'));

        /** @phpstan-ignore-next-line  */
        $securityUser->eraseCredentials();

        self::assertSame('sbbone', $securityUser->getPassword());
    }
}
