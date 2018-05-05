<?php
declare(strict_types=1);
/**
 * /tests/Integration/Utils/LoginLoggerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Utils;

use App\Repository\UserRepository;
use App\Resource\LogLoginResource;
use App\Utils\LoginLogger;
use BadMethodCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\User;

/**
 * Class LoginLoggerTest
 *
 * @package App\Tests\Integration\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLoggerTest extends KernelTestCase
{
    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testThatSetUserCallsRepositoryMethodIfWrongUserProvided(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LogLoginResource $logLoginResource
         * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository   $userRepository
         */
        $logLoginResource = $this->getMockBuilder(LogLoginResource::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $requestStack = new RequestStack();

        $userRepository
            ->expects(static::once())
            ->method('loadUserByUsername')
            ->with('username');

        // Create core user object
        $user = new User('username', 'password');

        $loginLogger = new LoginLogger($logLoginResource, $userRepository, $requestStack);
        $loginLogger->setUser($user);

        unset($loginLogger, $user, $userRepository, $requestStack, $logLoginResource);
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Could not get request from current request stack
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testThatExceptionIsThrownIfRequestIsNotAvailable(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|LogLoginResource $logLoginResource
         * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository   $userRepository
         */
        $logLoginResource = $this->getMockBuilder(LogLoginResource::class)->disableOriginalConstructor()->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $requestStack = new RequestStack();

        $loginLogger = new LoginLogger($logLoginResource, $userRepository, $requestStack);
        $loginLogger->process('');
    }
}
