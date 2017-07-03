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
use App\Security\Roles;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
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
    public function testThatFoo(): void
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|Roles $roles */
        $roles = $this->getMockBuilder(Roles::class)->disableOriginalConstructor()->getMock();

        // Create JWTCreatedEvent
        $event = new JWTCreatedEvent($payload, $user);

        $subscriber = new JWTCreatedSubscriber($roles, $requestStack);
        $subscriber->onJWTCreated($event);

        $keys = ['exp', 'checksum', 'id', 'firstname', 'surname', 'email', 'roles'];

        foreach ($keys as $key) {
            static::assertArrayHasKey($key, $event->getData());
        }
    }
}
