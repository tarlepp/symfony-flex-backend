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
    public function testThatSupportMethodReturnsExpected(bool $expected, ParamConverter $configuration)
    {
        static::assertSame($expected, $this->converter->supports($configuration));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testThatApplyMethodThrowsAnException()
    {
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

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->converter = new RestResourceConverter(static::$kernel->getContainer()->get(Collection::class));
    }
}
