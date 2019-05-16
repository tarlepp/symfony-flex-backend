<?php
declare(strict_types=1);
/**
 * /tests/Integration/Request/ParamConverter/RestResourceConverterTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Request\ParamConverter;

use App\Entity\Role;
use App\Request\ParamConverter\RestResourceConverter;
use App\Resource\Collection;
use App\Resource\RoleResource;
use App\Security\RolesService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RestResourceConverterTest
 *
 * @package App\Tests\Integration\Request\ParamConverter
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestResourceConverterTest extends KernelTestCase
{
    /**
     * @var RestResourceConverter
     */
    private $converter;

    /**
     * @dataProvider dataProviderTestThatSupportMethodReturnsExpected
     *
     * @param bool           $expected
     * @param ParamConverter $configuration
     */
    public function testThatSupportMethodReturnsExpected(bool $expected, ParamConverter $configuration): void
    {
        static::assertSame($expected, $this->converter->supports($configuration));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testThatApplyMethodThrowsAnException(): void
    {
        $request = new Request();
        $request->attributes->set('foo', 'bar');

        $paramConverter = new ParamConverter([
            'name'  => 'foo',
            'class' => RoleResource::class
        ]);

        $this->converter->apply($request, $paramConverter);

        unset($paramConverter, $request);
    }

    /**
     * @dataProvider dataProviderTestThatApplyMethodReturnsExpected
     *
     * @param string $role
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

        unset($paramConverter, $request);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSupportMethodReturnsExpected(): array
    {
        return [
            [
                false,
                new ParamConverter(['class' => 'FooBar']),
            ],
            [
                false,
                new ParamConverter(['class' => LoggerInterface::class]),
            ],
            [
                false,
                new ParamConverter(['class' => Role::class]),
            ],
            [
                true,
                new ParamConverter(['class' => RoleResource::class]),
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
        gc_enable();

        parent::setUp();

        static::bootKernel();

        $this->converter = new RestResourceConverter(static::$container->get(Collection::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->converter);

        gc_collect_cycles();
    }
}
