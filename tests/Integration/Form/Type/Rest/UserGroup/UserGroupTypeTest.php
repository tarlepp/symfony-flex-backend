<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/Type/Rest/UserGroup/UserGroupTypeTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\Type\Rest\UserGroup;

use App\DTO\UserGroup as UserGroupDto;
use App\Entity\Role;
use App\Form\DataTransformer\RoleTransformer;
use App\Form\Type\Rest\UserGroup\UserGroupType;
use App\Resource\RoleResource;
use App\Security\RolesService;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class UserGroupTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Rest\UserGroup
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTypeTest extends TypeTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RolesService
     */
    private $mockRoleService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RoleResource
     */
    private $mockRoleResource;

    public function testSubmitValidData(): void
    {
        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        $this->mockRoleResource
            ->expects(static::once())
            ->method('find')
            ->willReturn([$roleEntity]);

        $this->mockRoleResource
            ->expects(static::once())
            ->method('findOne')
            ->with($roleEntity->getId())
            ->willReturn($roleEntity);

        $this->mockRoleService
            ->expects(static::once())
            ->method('getRoleLabel')
            ->willReturn('role name');

        // Create form
        $form = $this->factory->create(UserGroupType::class);

        // Create new DTO object
        $dto = new UserGroupDto();
        $dto->setName('ROLE_ADMIN');
        $dto->setRole($roleEntity);

        // Specify used form data
        $formData = array(
            'name'  => 'ROLE_ADMIN',
            'role'  => 'ROLE_ADMIN',
        );

        // submit the data to the form directly
        $form->submit($formData);

        // Test that data transformers have not been failed
        $this->assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        $this->assertEquals($dto, $form->getData());

        // Check that form renders correctly
        $view = $form->createView();
        $children = $view->children;

        foreach (\array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }

        unset($view, $dto, $form, $roleEntity);
    }

    protected function setUp(): void
    {
        gc_enable();

        $this->mockRoleService = $this->createMock(RolesService::class);
        $this->mockRoleResource = $this->createMock(RoleResource::class);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockRoleService, $this->mockRoleResource);

        gc_collect_cycles();
    }

    /**
     * @return array
     */
    protected function getExtensions(): array
    {
        parent::getExtensions();

        // create a type instance with the mocked dependencies
        $type = new UserGroupType(
            $this->mockRoleService,
            $this->mockRoleResource,
            new RoleTransformer($this->mockRoleResource)
        );

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }
}
