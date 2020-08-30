<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/BlameableDecoratorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\BlameableDecorator;
use App\Resource\UserResource;
use App\Security\SecurityUser;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class BlameableDecoratorTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class BlameableDecoratorTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatSetUserValueCallsExpectedResourceMethod(): void
    {
        /**
         * @var MockObject|UserResource $resource
         */
        $resource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $user = new User();
        $domainUser = new SecurityUser($user);

        $resource
            ->expects(static::once())
            ->method('getReference')
            ->with($user->getId())
            ->willReturn($user);

        (new BlameableDecorator($resource))
            ->setUserValue($domainUser);
    }
}
