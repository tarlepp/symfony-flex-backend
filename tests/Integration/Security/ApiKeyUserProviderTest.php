<?php
declare(strict_types=1);
/**
 * /tests/Integration/Utils/ApiKeyUserProviderTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Security;

use App\Entity\ApiKey;
use App\Entity\User as UserEntity;
use App\Repository\ApiKeyRepository;
use App\Security\ApiKeyUser;
use App\Security\ApiKeyUserProvider;
use App\Security\RolesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ApiKeyUserProviderTest
 *
 * @package App\Tests\Integration\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserProviderTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatSupportClassReturnsExpected
     *
     * @param bool   $expected
     * @param string $input
     */
    public function testThatSupportClassReturnsExpected(bool $expected, string $input): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ApiKeyRepository $apiKeyRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService     $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $provider = new ApiKeyUserProvider($apiKeyRepository, $rolesService);

        static::assertSame($expected, $provider->supportsClass($input));

        unset($provider, $rolesService, $apiKeyRepository);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage API key cannot refresh user
     */
    public function testThatRefreshUserThrowsAnException(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ApiKeyRepository $apiKeyRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService     $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $user = new User('username', 'password');

        $provider = new ApiKeyUserProvider($apiKeyRepository, $rolesService);
        $provider->refreshUser($user);

        unset($provider, $user, $rolesService, $apiKeyRepository);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage API key is not valid
     */
    public function testThatLoadUserByUsernameThrowsAnException(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ApiKeyRepository $apiKeyRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService     $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $apiKeyRepository
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn(null);

        $provider = new ApiKeyUserProvider($apiKeyRepository, $rolesService);
        $provider->loadUserByUsername('guid');

        unset($provider, $apiKeyRepository, $rolesService);
    }

    public function testThatLoadUserByUsernameCreatesExpectedApiKeyUser(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ApiKeyRepository $apiKeyRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService     $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $apiKey = new ApiKey();

        $apiKeyRepository
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'guid'])
            ->willReturn($apiKey);

        $provider = new ApiKeyUserProvider($apiKeyRepository, $rolesService);
        $user = $provider->loadUserByUsername('guid');

        static::assertSame($apiKey, $user->getApiKey());

        unset($user, $provider, $apiKeyRepository, $apiKey, $rolesService);
    }

    public function testThatGetApiKeyForTokenCallsExpectedRepositoryMethod(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ApiKeyRepository $apiKeyRepository
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService     $rolesService
         */
        $apiKeyRepository = $this->getMockBuilder(ApiKeyRepository::class)->disableOriginalConstructor()->getMock();
        $rolesService = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        $apiKeyRepository
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['token' => 'some_token'])
            ->willReturn(null);

        $provider = new ApiKeyUserProvider($apiKeyRepository, $rolesService);
        $provider->getApiKeyForToken('some_token');

        unset($provider, $apiKeyRepository, $rolesService);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSupportClassReturnsExpected(): array
    {
        return [
            [false, \stdClass::class],
            [false, UserInterface::class],
            [false, UserEntity::class],
            [true, ApiKeyUser::class]
        ];
    }
}
