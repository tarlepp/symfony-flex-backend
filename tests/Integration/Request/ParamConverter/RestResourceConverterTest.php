<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Request/ParamConverter/RestResourceConverterTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Request\ParamConverter;

use App\Entity\Role;
use App\Request\ParamConverter\RestResourceConverter;
use App\Resource\ResourceCollection;
use App\Resource\RoleResource;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class RestResourceConverterTest
 *
 * @package App\Tests\Integration\Request\ParamConverter
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestResourceConverterTest extends KernelTestCase
{
    private RestResourceConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        /* @noinspection PhpParamsInspection */
        $this->converter = new RestResourceConverter(static::$container->get(ResourceCollection::class));
    }

    /**
     * @dataProvider dataProviderTestThatSupportMethodReturnsExpected
     *
     * @testdox Test `supports` method returns `$expected` when using `$configuration` as ParamConverter input.
     */
    public function testThatSupportMethodReturnsExpected(bool $expected, StringableArrayObject $configuration): void
    {
        static::assertSame($expected, $this->converter->supports(new ParamConverter($configuration->getArrayCopy())));
    }

    /**
     * @throws Throwable
     */
    public function testThatApplyMethodThrowsAnException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $request = new Request();
        $request->attributes->set('foo', 'bar');

        $paramConverter = new ParamConverter([
            'name' => 'foo',
            'class' => RoleResource::class,
        ]);

        $this->converter->apply($request, $paramConverter);
    }

    /**
     * @dataProvider dataProviderTestThatApplyMethodReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `apply` method works as expected when using `$role` as a request attribute.
     */
    public function testThatApplyMethodReturnsExpected(string $role): void
    {
        $request = new Request();
        $request->attributes->set('role', $role);

        $paramConverter = new ParamConverter([
            'name' => 'role',
            'class' => RoleResource::class,
        ]);

        static::assertTrue($this->converter->apply($request, $paramConverter));
        static::assertInstanceOf(Role::class, $request->attributes->get('role'));
        static::assertSame('Description - ' . $role, $request->attributes->get('role')->getDescription());
    }

    public function dataProviderTestThatSupportMethodReturnsExpected(): Generator
    {
        yield [
            false,
            new StringableArrayObject(['class' => 'FooBar']),
        ];

        yield [
            false,
            new StringableArrayObject(['class' => LoggerInterface::class]),
        ];

        yield [
            false,
            new StringableArrayObject(['class' => Role::class]),
        ];

        yield [
            true,
            new StringableArrayObject(['class' => RoleResource::class]),
        ];
    }

    public function dataProviderTestThatApplyMethodReturnsExpected(): Generator
    {
        yield [RolesService::ROLE_LOGGED];
        yield [RolesService::ROLE_USER];
        yield [RolesService::ROLE_ADMIN];
        yield [RolesService::ROLE_ROOT];
        yield [RolesService::ROLE_API];
    }
}
