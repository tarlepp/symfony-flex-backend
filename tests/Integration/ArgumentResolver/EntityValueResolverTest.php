<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ArgumentResolver/EntityValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\ArgumentResolver;

use App\ArgumentResolver\EntityValueResolver;
use App\Entity\User;
use App\Resource\ResourceCollection;
use App\Resource\UserResource;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;
use function iterator_to_array;

/**
 * Class EntityValueResolverTest
 *
 * @package App\Tests\Integration\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EntityValueResolverTest extends KernelTestCase
{
    public function testThatSupportsReturnFalseWithNotSupportedRequestParameterType(): void
    {
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', null, false, false, null);

        self::assertFalse($resolver->supports(
            Request::create(
                '/',
                'GET',
                [
                    'foo' => new stdClass(),
                ]
            ),
            $metadata
        ));
    }

    public function testThatSupportsReturnFalseWithWrongArgumentParameterType(): void
    {
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', stdClass::class, false, false, null);

        self::assertFalse($resolver->supports(Request::create('/', 'GET', [
            'foo' => 'bar',
        ]), $metadata));
    }

    public function testThatSupportsMethodCallsExpectedResourceCollectionMethods(): void
    {
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resourceCollection
            ->expects(self::once())
            ->method('hasEntityResource')
            ->with(User::class)
            ->willReturn(false);

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        $resolver->supports(
            Request::create(
                '/',
                'GET',
                [
                    'foo' => 'bar',
                ]
            ),
            $metadata
        );
    }

    public function testThatSupportsMethodReturnFalseWithNotSupportedEntityResource(): void
    {
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resourceCollection
            ->expects(self::once())
            ->method('hasEntityResource')
            ->with(User::class)
            ->willReturn(false);

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        self::assertFalse($resolver->supports(
            Request::create(
                '/',
                'GET',
                [
                    'foo' => 'bar',
                ]
            ),
            $metadata
        ));
    }

    /**
     * @throws Throwable
     */
    public function testThatResolveMethodReturnsExpected(): void
    {
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();
        $userResource = $this->getMockBuilder(UserResource::class)->disableOriginalConstructor()->getMock();

        $user = new User();

        $resourceCollection
            ->expects(self::once())
            ->method('getEntityResource')
            ->with(User::class)
            ->willReturn($userResource);

        $userResource
            ->expects(self::once())
            ->method('findOne')
            ->with($user->getId())
            ->willReturn($user);

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('user', User::class, false, false, null);
        $request = Request::create(
            '/',
            'GET',
            [
                'user' => $user->getId(),
            ]
        );

        self::assertSame([$user], iterator_to_array($resolver->resolve($request, $metadata)));
    }
}
