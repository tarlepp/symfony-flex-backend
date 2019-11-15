<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ArgumentResolver/EntityValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\ArgumentResolver;

use App\ArgumentResolver\EntityValueResolver;
use App\Entity\User;
use App\Resource\ResourceCollection;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Class EntityValueResolverTest
 *
 * @package App\Tests\Integration\ArgumentResolver
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EntityValueResolverTest extends KernelTestCase
{
    public function testThatSupportsReturnFalseWithNotSupportedRequestParameterType(): void
    {
        /** @var MockObject|ResourceCollection $resourceCollection */
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', null, false, false, null);

        static::assertFalse($resolver->supports(Request::create('/', 'GET', ['foo' => new stdClass()]), $metadata));
    }

    public function testThatSupportsReturnFalseWithWrongArgumentParameterType(): void
    {
        /** @var MockObject|ResourceCollection $resourceCollection */
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', stdClass::class, false, false, null);

        static::assertFalse($resolver->supports(Request::create('/', 'GET', ['foo' => 'bar']), $metadata));
    }

    public function testThatSupportsMethodCallsExpectedResourceCollectionMethods(): void
    {
        /** @var MockObject|ResourceCollection $resourceCollection */
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resourceCollection
            ->expects(static::once())
            ->method('hasEntityResource')
            ->with(User::class)
            ->willReturn(false);

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        $resolver->supports(Request::create('/', 'GET', ['foo' => 'bar']), $metadata);
    }

    public function testThatSupportsMethodReturnFalseWithNotSupportedEntityResource(): void
    {
        /** @var MockObject|ResourceCollection $resourceCollection */
        $resourceCollection = $this->getMockBuilder(ResourceCollection::class)->disableOriginalConstructor()->getMock();

        $resourceCollection
            ->expects(static::once())
            ->method('hasEntityResource')
            ->with(User::class)
            ->willReturn(false);

        $resolver = new EntityValueResolver($resourceCollection);
        $metadata = new ArgumentMetadata('foo', User::class, false, false, null);

        static::assertFalse($resolver->supports(Request::create('/', 'GET', ['foo' => 'bar']), $metadata));
    }
}
