<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/JWTCreatedSubscriberTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\JWTCreatedSubscriber;
use App\Security\RolesService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class JWTCreatedSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JWTCreatedSubscriberTest extends KernelTestCase
{
    public function testThatPayloadContainsExpectedDataWhenRequestIsPresent(): void
    {
        // Create empty JWT payload
        $payload = [];

        // Create new user for JWT
        $user = new User();
        $user->setFirstname('firstname');
        $user->setSurname('surname');
        $user->setEmail('firstname.surname@test.com');

        // Create pure Request
        $request = new Request();

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();
        $requestStack->push($request);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService $roles
         */
        $roles = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent($payload, $user);

        $subscriber = new JWTCreatedSubscriber($roles, $requestStack);
        $subscriber->onJWTCreated($event);

        $keys = ['exp', 'checksum', 'id', 'firstname', 'surname', 'email', 'roles'];

        foreach ($keys as $key) {
            static::assertArrayHasKey($key, $event->getData());
        }
    }

    public function testThatPayloadContainsExpectedDataWhenRequestIsNotPresent(): void
    {
        // Create empty JWT payload
        $payload = [];

        // Create new user for JWT
        $user = new User();
        $user->setFirstname('firstname');
        $user->setSurname('surname');
        $user->setEmail('firstname.surname@test.com');

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService    $roles
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface $logger
         */
        $roles = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent($payload, $user);

        $subscriber = new JWTCreatedSubscriber($roles, $requestStack);
        $subscriber->setLogger($logger);
        $subscriber->onJWTCreated($event);

        $keys = ['exp', 'id', 'firstname', 'surname', 'email', 'roles'];

        foreach ($keys as $key) {
            static::assertArrayHasKey($key, $event->getData());
        }

        static::assertArrayNotHasKey('checksum', $event->getData());
    }

    public function testThatLoggerAlertIsCalledIfRequestDoesNotExist(): void
    {
        // Create empty JWT payload
        $payload = [];

        // Create new user for JWT
        $user = new User();
        $user->setFirstname('firstname');
        $user->setSurname('surname');
        $user->setEmail('firstname.surname@test.com');

        // Create RequestStack and push pure Request to it
        $requestStack = new RequestStack();

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|RolesService    $roles
         * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface $logger
         */
        $roles = $this->getMockBuilder(RolesService::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $logger
            ->expects(static::once())
            ->method('alert')
            ->with('Request not available');

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent($payload, $user);

        $subscriber = new JWTCreatedSubscriber($roles, $requestStack);
        $subscriber->setLogger($logger);
        $subscriber->onJWTCreated($event);
    }
}
