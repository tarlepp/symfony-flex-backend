<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/Rest/UserGroup/UserGroupTypeTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\Rest\UserGroup;

use App\Entity\Role;
use App\Form\Rest\UserGroup\UserGroupType;
use App\Repository\RoleRepository;
use App\Rest\DTO\UserGroup as UserGroupDto;
use App\Security\Roles;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class UserGroupTypeTest
 *
 * @package App\Tests\Integration\Form\Rest\UserGroup
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTypeTest extends TypeTestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Roles
     */
    private $mockRoles;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|RoleRepository
     */
    private $mockRoleRepository;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    private $mockObjectManager;

    public function testSubmitValidData(): void
    {
        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        $this->mockRoleRepository
            ->expects(static::once())
            ->method('findAll')
            ->willReturn([$roleEntity]);

        $this->mockRoleRepository
            ->expects(static::once())
            ->method('find')
            ->willReturn($roleEntity);

        $this->mockObjectManager
            ->expects(static::once())
            ->method('getRepository')
            ->willReturn($this->mockRoleRepository);

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
    }

    protected function setUp(): void
    {
        $this->mockRoles = $this->createMock(Roles::class);
        $this->mockRoleRepository = $this->createMock(RoleRepository::class);
        $this->mockObjectManager = $this->createMock(ObjectManager::class);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions(): array
    {
        parent::getExtensions();

        // create a type instance with the mocked dependencies
        $type = new UserGroupType($this->mockRoles, $this->mockRoleRepository, $this->mockObjectManager);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }
}
