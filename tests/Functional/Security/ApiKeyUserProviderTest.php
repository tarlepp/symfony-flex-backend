<?php
declare(strict_types=1);
/**
 * /tests/Functional/Security/ApiKeyUserProviderTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Security;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\ApiKeyUserProvider;
use App\Security\RolesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Functional\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    /**
     * @var ApiKeyUserProvider
     */
    private $apiKeyUserProvider;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        // Store container and entity manager
        $container = static::$kernel->getContainer();
        $managerRegistry = $container->get('doctrine');

        $repository = ApiKeyRepository::class;

        $this->apiKeyUserProvider = new ApiKeyUserProvider(
            new $repository($managerRegistry),
            $container->get(RolesService::class)
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @param string $shortRole
     */
    public function testThatGetApiKeyReturnsExpected(string $shortRole): void
    {
        $token = \str_pad($shortRole, 40, '_');

        $apiKey = $this->apiKeyUserProvider->getApiKeyForToken($token);

        static::assertInstanceOf(ApiKey::class, $apiKey);
    }

    /**
     * @dataProvider dataProviderTestThatGetApiKeyReturnsExpected
     *
     * @param string $shortRole
     */
    public function testThatGetApiKeyReturnsNullForInvalidToken(string $shortRole): void
    {
        $token = \str_pad($shortRole, 40, '-');

        $apiKey = $this->apiKeyUserProvider->getApiKeyForToken($token);

        static::assertNull($apiKey);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage API key is not valid
     */
    public function testThatLoadUserByUsernameThrowsAnExceptionWithInvalidGuid(): void
    {
        $this->apiKeyUserProvider->loadUserByUsername((string)time());
    }

    /**
     * @dataProvider dataProviderTestThatLoadUserByUsernameWorksAsExpected
     *
     * @param string $guid
     * @param ApiKey $apiKey
     */
    public function testThatLoadUserByUsernameWorksAsExpected(string $guid, ApiKey $apiKey): void
    {
        $apiKeyUser = $this->apiKeyUserProvider->loadUserByUsername($guid);

        static::assertInstanceOf(ApiKeyUser::class, $apiKeyUser);
        static::assertSame($apiKey->getId(), $apiKeyUser->getApiKey()->getId());
        static::assertSame($apiKey->getRoles(), $apiKeyUser->getApiKey()->getRoles());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage API key cannot refresh user
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $user = new User('username', 'password');

        $this->apiKeyUserProvider->refreshUser($user);
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

        // Store container and entity manager
        $container = static::$kernel->getContainer();
        $rolesService = $container->get(RolesService::class);

        $iterator = function (string $role) use ($rolesService): array {
            return [$rolesService->getShort($role)];
        };

        return \array_map($iterator, $rolesService->getRoles());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatLoadUserByUsernameWorksAsExpected(): array
    {
        static::bootKernel();

        // Store container and entity manager
        $container = static::$kernel->getContainer();
        $managerRegistry = $container->get('doctrine');

        $repositoryClass = ApiKeyRepository::class;

        /** @var ApiKeyRepository $repository */
        $repository = new $repositoryClass($managerRegistry);

        $iterator = function (ApiKey $apiKey): array {
            return [
                $apiKey->getId(),
                $apiKey,
            ];
        };

        return \array_map($iterator, $repository->findAll());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSupportsClassReturnsExpected(): array
    {
        return [
            [false, User::class],
            [true, ApiKeyUser::class],
        ];
    }
}
