<?php
declare(strict_types = 1);
/**
 * /tests/Integration/ArgumentResolver/RestDtoValueResolverTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\ArgumentResolver;

use App\ArgumentResolver\RestDtoValueResolver;
use App\Rest\ControllerCollection;
use AutoMapperPlus\AutoMapperInterface;
use BadMethodCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

/**
 * Class RestDtoValueResolverTest
 *
 * @package App\Tests\Integration\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RestDtoValueResolverTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `resolve` method throws an exception if support method is not called
     */
    public function testThatResolveMethodThrowsAnExceptionIfSupportMethodIsNotCalledFirst(): void
    {
        $controllerCollection = $this->getMockBuilder(ControllerCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $autoMapper = $this->getMockBuilder(AutoMapperInterface::class)->getMock();

        $resolver = new RestDtoValueResolver($controllerCollection, $autoMapper);
        $metadata = new ArgumentMetadata('foo', null, false, false, null);
        $request = Request::create('/');

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            sprintf(
                'You cannot call `%1$s::resolve(...)` method without calling `%1$s::supports(...)` first',
                RestDtoValueResolver::class
            )
        );

        // Note that we need to actually get current value here
        $resolver->resolve($request, $metadata)->current();
    }
}
