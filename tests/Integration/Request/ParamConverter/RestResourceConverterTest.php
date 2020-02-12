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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestResourceConverterTest extends KernelTestCase
{
    private RestResourceConverter $converter;

    /**
     * @dataProvider dataProviderTestThatSupportMethodReturnsExpected
     *
     * @param bool                  $expected
     * @param StringableArrayObject $configuration
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
            'name'  => 'foo',
            'class' => RoleResource::class
        ]);

        $this->converter->apply($request, $paramConverter);
    }

    /**
     * @dataProvider dataProviderTestThatApplyMethodReturnsExpected
     *
     * @param string $role
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
            'name'  => 'role',
            'class' => RoleResource::class
        ]);

        static::assertTrue($this->converter->apply($request, $paramConverter));
        static::assertInstanceOf(Role::class, $request->attributes->get('role'));
        static::assertSame('Description - ' . $role, $request->attributes->get('role')->getDescription());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSupportMethodReturnsExpected(): array
    {
        return [
            [
                false,
                new StringableArrayObject(['class' => 'FooBar']),
            ],
            [
                false,
                new StringableArrayObject(['class' => LoggerInterface::class]),
            ],
            [
                false,
                new StringableArrayObject(['class' => Role::class]),
            ],
            [
                true,
                new StringableArrayObject(['class' => RoleResource::class]),
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatApplyMethodReturnsExpected(): array
    {
        return [
            [RolesService::ROLE_LOGGED],
            [RolesService::ROLE_USER],
            [RolesService::ROLE_ADMIN],
            [RolesService::ROLE_ROOT],
            [RolesService::ROLE_API],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->converter = new RestResourceConverter(static::$container->get(ResourceCollection::class));
    }
}
