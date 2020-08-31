<?php
declare(strict_types = 1);
/**
 * /tests/Functional/Security/Provider/ApiKeyUserProviderTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Functional\Security\Provider;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\Provider\ApiKeyUserProvider;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Throwable;
use function array_map;
use function str_pad;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Functional\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    private ApiKeyUserProvider $apiKeyUserProvider;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $managerRegistry = static::$container->get('doctrine');

        $repository = ApiKeyRepository::class;

        $this->apiKeyUserProvider = new ApiKeyUserProvider(
            new $repository($managerRegistry),
            static::$container->get(RolesService::class)
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @testdox Test that `getApiKeyForToken` method returns expected when using `$shortRole` as token base.
     */
    public function testThatGetApiKeyReturnsExpected(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '_');

        $apiKey = $this->apiKeyUserProvider->getApiKeyForToken($token);

        static::assertInstanceOf(ApiKey::class, $apiKey);
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @testdox Test that `getApiKeyForToken` method returns null when using `$shortRole` as an invalid token base.
     */
    public function testThatGetApiKeyReturnsNullForInvalidToken(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '-');

        $apiKey = $this->apiKeyUserProvider->getApiKeyForToken($token);

        static::assertNull($apiKey);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionWithInvalidGuid(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('API key is not valid');

        $this->apiKeyUserProvider->loadUserByUsername((string)time());
    }

    /**
     * @dataProvider dataProviderTestThatLoadUserByUsernameWorksAsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `loadUserByUsername` returns `ApiKeyUser` with `$roles` roles when using `$token` as an input.
     */
    public function testThatLoadUserByUsernameWorksAsExpected(string $token, StringableArrayObject $roles): void
    {
        $apiKeyUser = $this->apiKeyUserProvider->loadUserByUsername($token);

        static::assertInstanceOf(ApiKeyUser::class, $apiKeyUser);
        static::assertSame($roles->getArrayCopy(), $apiKeyUser->getApiKey()->getRoles());
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('API key cannot refresh user');

        $user = new User('username', 'password');

        $this->apiKeyUserProvider->refreshUser($user);
    }

    /**
     * @dataProvider dataProviderTestThatSupportsClassReturnsExpected
     *
     * @testdox Test that `supportsClass` returns `$expected` when using `$class` as an input.
     */
    public function testThatSupportsClassReturnsExpected(bool $expected, string $class): void
    {
        static::assertSame($expected, $this->apiKeyUserProvider->supportsClass($class));
    }

    public function dataProviderTestThatGetApiKeyReturnsExpected(): array
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        $iterator = static function (string $role) use ($rolesService): array {
            return [$rolesService->getShort($role)];
        };

        return array_map($iterator, $rolesService->getRoles());
    }

    public function dataProviderTestThatLoadUserByUsernameWorksAsExpected(): array
    {
        static::bootKernel();

        $managerRegistry = static::$container->get('doctrine');
        $repositoryClass = ApiKeyRepository::class;

        /** @var ApiKeyRepository $repository */
        $repository = new $repositoryClass($managerRegistry);

        $iterator = static function (ApiKey $apiKey): array {
            return [
                $apiKey->getToken(),
                new StringableArrayObject($apiKey->getRoles()),
            ];
        };

        return array_map($iterator, $repository->findAll());
    }

    public function dataProviderTestThatSupportsClassReturnsExpected(): Generator
    {
        yield [false, User::class];
        yield [true, ApiKeyUser::class];
    }
}
