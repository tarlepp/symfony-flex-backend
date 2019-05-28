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
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User;
use Throwable;
use function array_map;
use function str_pad;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Functional\Security\Provider
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    /**
     * @var ApiKeyUserProvider
     */
    private $apiKeyUserProvider;

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @param string $shortRole
     */
    public function testThatGetApiKeyReturnsExpected(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '_');

        $apiKey = $this->apiKeyUserProvider->getApiKeyForToken($token);

        static::assertInstanceOf(ApiKey::class, $apiKey);

        unset($apiKey);
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @param string $shortRole
     */
    public function testThatGetApiKeyReturnsNullForInvalidToken(string $shortRole): void
    {
        $token = str_pad($shortRole, 40, '-');

        $apiKey = $this->apiKeyUserProvider->getApiKeyForToken($token);

        static::assertNull($apiKey);

        unset($apiKey);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage API key is not valid
     *
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionWithInvalidGuid(): void
    {
        $this->apiKeyUserProvider->loadUserByUsername((string)time());
    }

    /**
     * @dataProvider dataProviderTestThatLoadUserByUsernameWorksAsExpected
     *
     * @param string $token
     * @param array $roles
     *
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameWorksAsExpected(string $token, array $roles): void
    {
        $apiKeyUser = $this->apiKeyUserProvider->loadUserByUsername($token);

        static::assertInstanceOf(ApiKeyUser::class, $apiKeyUser);
        static::assertSame($roles, $apiKeyUser->getApiKey()->getRoles());

        unset($apiKeyUser);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage API key cannot refresh user
     *
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $user = new User('username', 'password');

        $this->apiKeyUserProvider->refreshUser($user);

        unset($user);
    }

    /**
     * @dataProvider dataProviderTestThatSupportsClassReturnsExpected
     *
     * @param bool   $expected
     * @param string $class
     */
    public function testThatSupportsClassReturnsExpected(bool $expected, string $class): void
    {
        static::assertSame($expected, $this->apiKeyUserProvider->supportsClass($class));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetApiKeyReturnsExpected(): array
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        $iterator = static function (string $role) use ($rolesService): array {
            return [$rolesService->getShort($role)];
        };

        return array_map($iterator, $rolesService->getRoles());
    }

    /**
     * @return array
     */
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
                $apiKey->getRoles(),
            ];
        };

        return array_map($iterator, $repository->findAll());
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatSupportsClassReturnsExpected(): Generator
    {
        yield [false, User::class];
        yield [true, ApiKeyUser::class];
    }

    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        static::bootKernel();

        $managerRegistry = static::$container->get('doctrine');

        $repository = ApiKeyRepository::class;

        $this->apiKeyUserProvider = new ApiKeyUserProvider(
            new $repository($managerRegistry),
            static::$container->get(RolesService::class)
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->apiKeyUserProvider);
    }
}
