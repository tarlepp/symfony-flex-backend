<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/Type/Console/UserGroupTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Form\Type\Console;

use App\DTO\UserGroup\UserGroup as UserGroupDto;
use App\Entity\Role;
use App\Form\DataTransformer\RoleTransformer;
use App\Form\Type\Console\UserGroupType;
use App\Resource\RoleResource;
use App\Security\RolesService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Throwable;
use function array_keys;

/**
 * Class UserGroupTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTypeTest extends TypeTestCase
{
    /**
     * @var MockObject|RolesService
     */
    private MockObject $mockRoleService;

    /**
     * @var MockObject|RoleResource
     */
    private $mockRoleResource;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->mockRoleService = $this->createMock(RolesService::class);
        $this->mockRoleResource = $this->createMock(RoleResource::class);

        parent::setUp();
    }

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
        $dto = (new UserGroupDto())
            ->setName('ROLE_ADMIN')
            ->setRole($roleEntity);

        // Specify used form data
        $formData = [
            'name' => 'ROLE_ADMIN',
            'role' => 'ROLE_ADMIN',
        ];

        // submit the data to the form directly
        $form->submit($formData);

        // Test that data transformers have not been failed
        static::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        static::assertSame($dto->getId(), $form->getData()->getId());
        static::assertSame($dto->getName(), $form->getData()->getName());
        static::assertSame($dto->getRole(), $form->getData()->getRole());

        // Check that form renders correctly
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            static::assertArrayHasKey($key, $children);
        }
    }

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
