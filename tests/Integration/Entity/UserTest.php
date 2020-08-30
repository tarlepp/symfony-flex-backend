<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/UserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTest extends EntityTestCase
{
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
        $this->entity->setPassword($callable, $password);

        static::assertSame($expected, $this->entity->getPassword());
    }

    public function testThatSetPlainPasswordIsWorkingAsExpected(): void
    {
        // First set new password
        $this->entity->setPassword('str_rot13', 'password');

        // Set plain password
        $this->entity->setPlainPassword('plainPassword');

        static::assertEmpty($this->entity->getPassword());
        static::assertSame('plainPassword', $this->entity->getPlainPassword());
    }

    public function testThatSetEmptyPlainPasswordDoesNotResetPassword(): void
    {
        // First set new password
        $this->entity->setPassword('str_rot13', 'password');

        // Set plain password
        $this->entity->setPlainPassword('');

        static::assertNotEmpty($this->entity->getPassword());
        static::assertEmpty($this->entity->getPlainPassword());
    }

    public function testThatUserEntityCanBeSerializedAndUnSerializedAsExpected(): void
    {
        // First set some data for entity
        $this->entity->setUsername('john');
        $this->entity->setPassword('str_rot13', 'password');

        /** @var User $entity */
        $entity = unserialize(serialize($this->entity), ['allowed_classes' => true]);

        // Assert that unserialized object returns expected data
        static::assertSame('john', $entity->getUsername());
        static::assertSame('cnffjbeq', $entity->getPassword());
    }

    public function testThatGetSaltMethodReturnsNull(): void
    {
        static::assertNull($this->entity->getSalt());
    }

    public function testThatEraseCredentialsMethodWorksAsExpected(): void
    {
        $this->entity->setPlainPassword('password');
        $this->entity->eraseCredentials();

        static::assertEmpty($this->entity->getPlainPassword());
    }

    public function testThatGetRolesReturnsExpectedWithoutRoleService(): void
    {
        $group = (new UserGroup())->setRole(new Role('ROLE_ROOT'));
        $user = (new User())->addUserGroup($group);

        static::assertSame(['ROLE_ROOT'], $user->getRoles());
    }

    /**
     * Data provider for testThatPasswordHashingIsWorkingAsExpected
     */
    public function dataProviderTestThatPasswordHashingIsWorkingAsExpected(): Generator
    {
        yield ['str_rot13', 'password', 'cnffjbeq'];
        yield ['base64_encode', 'password', 'cGFzc3dvcmQ='];
    }
}
