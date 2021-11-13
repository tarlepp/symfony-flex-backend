<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/Type/Console/UserGroupTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use UnexpectedValueException;
use function array_keys;

/**
 * Class UserGroupTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTypeTest extends TypeTestCase
{
    private MockObject | RolesService | string $rolesService = '';
    private MockObject | RoleResource | string $roleResource = '';

    protected function setUp(): void
    {
        $this->rolesService = $this->createMock(RolesService::class);
        $this->roleResource = $this->createMock(RoleResource::class);

        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        $this->getRoleResourceMock()
            ->expects(self::once())
            ->method('find')
            ->willReturn([$roleEntity]);

        $this->getRoleResourceMock()
            ->expects(self::once())
            ->method('findOne')
            ->with($roleEntity->getId())
            ->willReturn($roleEntity);

        $this->getRolesServiceMock()
            ->expects(self::once())
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
        self::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        self::assertSame($dto->getId(), $form->getData()->getId());
        self::assertSame($dto->getName(), $form->getData()->getName());
        self::assertSame($dto->getRole(), $form->getData()->getRole());

        // Check that form renders correctly
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array<int, PreloadedExtension>
     */
    protected function getExtensions(): array
    {
        parent::getExtensions();

        // create a type instance with the mocked dependencies
        $type = new UserGroupType(
            $this->getRolesService(),
            $this->getRoleResource(),
            new RoleTransformer($this->getRoleResource())
        );

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    private function getRolesService(): RolesService
    {
        return $this->rolesService instanceof RolesService
            ? $this->rolesService
            : throw new UnexpectedValueException('RolesService not set');
    }

    private function getRolesServiceMock(): MockObject
    {
        return $this->rolesService instanceof MockObject
            ? $this->rolesService
            : throw new UnexpectedValueException('RolesService not set');
    }

    private function getRoleResource(): RoleResource
    {
        return $this->roleResource instanceof RoleResource
            ? $this->roleResource
            : throw new UnexpectedValueException('RoleResource not set');
    }

    private function getRoleResourceMock(): MockObject
    {
        return $this->roleResource instanceof MockObject
            ? $this->roleResource
            : throw new UnexpectedValueException('RoleResource not set');
    }
}
