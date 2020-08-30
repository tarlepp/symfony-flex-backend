<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Provider/ApiKeyUserProviderTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Security\Provider;

use App\Entity\ApiKey;
use App\Entity\User as UserEntity;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\Provider\ApiKeyUserProvider;
use App\Security\RolesService;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Integration\Security\Provider
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatSupportClassReturnsExpected
     *
     * @param mixed $input
     *
     * @throws Throwable
     *
     * @testdox Test that `supportsClass` method returns `$expected` with `$input` input.
     */
    public function testThatSupportClassReturnsExpected(bool $expected, $input): void
    {
        /**
         * @var MockObject|ApiKeyRepository $apiKeyRepository
         * @var MockObject|RolesService $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new ApiKeyUserProvider($apiKeyRepository, $rolesService);

        static::assertSame($expected, $provider->supportsClass((string)$input));
    }

    /**
     * @throws Throwable
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('API key cannot refresh user');

        /**
         * @var MockObject|ApiKeyRepository $apiKeyRepository
         * @var MockObject|RolesService $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User('username', 'password');

        (new ApiKeyUserProvider($apiKeyRepository, $rolesService))
            ->refreshUser($user);
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameThrowsAnException(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('API key is not valid');

        /**
         * @var MockObject|ApiKeyRepository $apiKeyRepository
         * @var MockObject|RolesService $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyRepository
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn(null);

        (new ApiKeyUserProvider($apiKeyRepository, $rolesService))
            ->loadUserByUsername('guid');
    }

    /**
     * @throws Throwable
     */
    public function testThatLoadUserByUsernameCreatesExpectedApiKeyUser(): void
    {
        /**
         * @var MockObject|ApiKeyRepository $apiKeyRepository
         * @var MockObject|RolesService $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKey = new ApiKey();

        $apiKeyRepository
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn($apiKey);

        $user = (new ApiKeyUserProvider($apiKeyRepository, $rolesService))
            ->loadUserByUsername('guid');

        static::assertSame($apiKey, $user->getApiKey());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetApiKeyForTokenCallsExpectedRepositoryMethod(): void
    {
        /**
         * @var MockObject|ApiKeyRepository $apiKeyRepository
         * @var MockObject|RolesService $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyRepository
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'some_token'])
            ->willReturn(null);

        (new ApiKeyUserProvider($apiKeyRepository, $rolesService))
            ->getApiKeyForToken('some_token');
    }

    /**
     * @throws Throwable
     */
    public function dataProviderTestThatSupportClassReturnsExpected(): Generator
    {
        yield [false, true];
        yield [false, 'foobar'];
        yield [false, 123];
        yield [false, stdClass::class];
        yield [false, UserInterface::class];
        yield [false, UserEntity::class];
        yield [true, ApiKeyUser::class];
    }
}
