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
use Generator;
use function serialize;
use function unserialize;

/**
 * Class UserTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method User getEntity()
 */
class UserTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = User::class;

    /**
     * @dataProvider dataProviderTestThatPasswordHashingIsWorkingAsExpected
     *
     * @testdox Test that password hashing is working with `$callable` callable.
     */
    public function testThatPasswordHashingIsWorkingAsExpected(
        callable $callable,
        string $password,
        string $expected
    ): void {
        $entity = $this->getEntity();

        $entity->setPassword($callable, $password);

        static::assertSame($expected, $entity->getPassword());
    }

    public function testThatSetPlainPasswordIsWorkingAsExpected(): void
    {
        $entity = $this->getEntity();

        // First set new password
        $entity->setPassword('str_rot13', 'password');

        // Set plain password
        $entity->setPlainPassword('plainPassword');

        static::assertEmpty($this->getEntity()->getPassword());
        static::assertSame('plainPassword', $this->getEntity()->getPlainPassword());
    }

    public function testThatSetEmptyPlainPasswordDoesNotResetPassword(): void
    {
        $entity = $this->getEntity();

        // First set new password
        $entity->setPassword('str_rot13', 'password');

        // Set plain password
        $entity->setPlainPassword('');

        static::assertNotEmpty($entity->getPassword());
        static::assertEmpty($entity->getPlainPassword());
    }

    public function testThatUserEntityCanBeSerializedAndUnSerializedAsExpected(): void
    {
        $entity = $this->getEntity();

        // First set some data for entity
        $entity->setUsername('john');
        $entity->setPassword('str_rot13', 'password');

        /** @var User $entityUnSerialized */
        $entityUnSerialized = unserialize(serialize($entity), ['allowed_classes' => true]);

        // Assert that un-serialized object returns expected data
        static::assertSame('john', $entityUnSerialized->getUsername());
        static::assertSame('cnffjbeq', $entityUnSerialized->getPassword());
    }

    public function testThatEraseCredentialsMethodWorksAsExpected(): void
    {
        $entity = $this->getEntity();

        $entity->setPlainPassword('password');
        $entity->eraseCredentials();

        static::assertEmpty($entity->getPlainPassword());
    }

    public function testThatGetRolesReturnsExpectedWithoutRoleService(): void
    {
        $group = (new UserGroup())->setRole(new Role('ROLE_ROOT'));
        $user = (new User())->addUserGroup($group);

        static::assertSame(['ROLE_ROOT'], $user->getRoles());
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public function dataProviderTestThatPasswordHashingIsWorkingAsExpected(): Generator
    {
        yield ['str_rot13', 'password', 'cnffjbeq'];
        yield ['base64_encode', 'password', 'cGFzc3dvcmQ='];
    }
}
