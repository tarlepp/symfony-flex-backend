<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/UserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Security\RolesService;
use function serialize;
use function ucfirst;
use function unserialize;

/**
 * Class UserTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = User::class;

    /**
     * @var User
     */
    protected $entity;

    /**
     * @dataProvider dataProviderTestThatPasswordHashingIsWorkingAsExpected
     *
     * @param callable $callable
     * @param string   $password
     * @param string   $expected
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

        unset($entity);
    }

    public function testThatGetSaltMethodReturnsNull(): void
    {
        static::assertNull($this->entity->getSalt());
    }

    public function testThatGetLoginDataMethodReturnsExpected(): void
    {
        $expected = [
            'firstname',
            'surname',
            'email',
        ];

        foreach ($expected as $key) {
            $method = 'set' . ucfirst($key);

            $this->entity->{$method}($key);
        }

        $data = $this->entity->getLoginData();

        foreach ($expected as $key) {
            static::assertArrayHasKey($key, $data);
            static::assertSame($key, $data[$key]);
        }

        unset($data);
    }

    public function testThatEraseCredentialsMethodWorksAsExpected(): void
    {
        $this->entity->setPlainPassword('password');

        $this->entity->eraseCredentials();

        static::assertEmpty($this->entity->getPlainPassword());
    }

    /**
     * @dataProvider dataProviderTestThatIsEqualToMethodWorksAsExpected
     *
     * @param bool $expected
     */
    public function testThatIsEqualToMethodWorksAsExpected(bool $expected): void
    {
        $entity = $expected ? clone $this->entity : new $this->entityName();

        $message = 'Failed to check if User entity is equal.';

        static::assertSame($expected, $this->entity->isEqualTo($entity), $message);

        unset($entity);
    }

    public function testThatGetRolesReturnsExpectedWithoutRoleService(): void
    {
        $group = (new UserGroup())->setRole(new Role('ROLE_ROOT'));
        $user = (new User())->addUserGroup($group);

        static::assertSame(['ROLE_ROOT'], $user->getRoles());
    }

    public function testThatGetRolesReturnsExpectedWithRoleService(): void
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        $group = (new UserGroup())->setRole(new Role('ROLE_ROOT'));
        $user = (new User())->addUserGroup($group)->setRolesService($rolesService);

        static::assertSame(['ROLE_ROOT', 'ROLE_ADMIN', 'ROLE_USER', 'ROLE_LOGGED'], $user->getRoles());
    }

    /**
     * Data provider for testThatPasswordHashingIsWorkingAsExpected
     *
     * @return array
     */
    public function dataProviderTestThatPasswordHashingIsWorkingAsExpected(): array
    {
        return [
            ['str_rot13', 'password', 'cnffjbeq'],
            ['base64_encode', 'password', 'cGFzc3dvcmQ='],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatIsEqualToMethodWorksAsExpected(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
