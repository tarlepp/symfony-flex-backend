<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/UserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Tests\Integration\TestCase\EntityTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use function serialize;
use function unserialize;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method User getEntity()
 */
final class UserTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = User::class;

    #[DataProvider('dataProviderTestThatPasswordHashingIsWorkingAsExpected')]
    #[TestDox('Test that password `$password` is hashed to `$expected` when using `$callable` callable')]
    public function testThatPasswordHashingIsWorkingAsExpected(
        callable $callable,
        string $password,
        string $expected
    ): void {
        $entity = $this->getEntity();

        $entity->setPassword($callable, $password);

        self::assertSame($expected, $entity->getPassword());
    }

    #[TestDox('Test that `setPlainPassword` method works as expected')]
    public function testThatSetPlainPasswordIsWorkingAsExpected(): void
    {
        $entity = $this->getEntity();

        // First set new password
        $entity->setPassword('str_rot13', 'password');

        // Set plain password
        $entity->setPlainPassword('plainPassword');

        self::assertEmpty($entity->getPassword());
        self::assertSame('plainPassword', $entity->getPlainPassword());
    }

    #[TestDox('Test that `setPlainPassword` method with empty input does not reset password')]
    public function testThatSetEmptyPlainPasswordDoesNotResetPassword(): void
    {
        $entity = $this->getEntity();

        // First set new password
        $entity->setPassword('str_rot13', 'password');

        // Set plain password
        $entity->setPlainPassword('');

        self::assertNotEmpty($entity->getPassword());
        self::assertEmpty($entity->getPlainPassword());
    }

    #[TestDox('Test that user entity can be serialized and un-serialized as expected')]
    public function testThatUserEntityCanBeSerializedAndUnSerializedAsExpected(): void
    {
        $entity = $this->getEntity();

        // First set some data for entity
        $entity->setUsername('john');
        $entity->setPassword('str_rot13', 'password');

        /** @var User $entityUnSerialized */
        $entityUnSerialized = unserialize(
            serialize($entity),
            [
                'allowed_classes' => true,
            ]
        );

        // Assert that un-serialized object returns expected data
        self::assertSame('john', $entityUnSerialized->getUsername());
        self::assertSame('cnffjbeq', $entityUnSerialized->getPassword());
    }

    #[TestDox('Test that `eraseCredentials` method works as expected')]
    public function testThatEraseCredentialsMethodWorksAsExpected(): void
    {
        $entity = $this->getEntity();

        $entity->setPlainPassword('password');
        $entity->eraseCredentials();

        self::assertEmpty($entity->getPlainPassword());
    }

    #[TestDox('Test that `getRoles` method returns expected roles')]
    public function testThatGetRolesReturnsExpectedRoles(): void
    {
        $group = new UserGroup()->setRole(new Role('ROLE_ROOT'));
        $user = new User()->addUserGroup($group);

        self::assertSame(['ROLE_ROOT'], $user->getRoles());
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public static function dataProviderTestThatPasswordHashingIsWorkingAsExpected(): Generator
    {
        yield ['str_rot13', 'password', 'cnffjbeq'];
        yield ['base64_encode', 'password', 'cGFzc3dvcmQ='];
    }
}
