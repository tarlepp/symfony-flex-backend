<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Form/Type/Console/UserGroupTypeTest.php
 */

namespace App\Tests\Integration\Form\Type\Console;

use App\DTO\UserGroup\UserGroup as UserGroupDto;
use App\Entity\Role;
use App\Form\DataTransformer\RoleTransformer;
use App\Form\Type\Console\UserGroupType;
use App\Resource\RoleResource;
use App\Security\RolesService;
use Override;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use function array_keys;

final class UserGroupTypeTest extends TypeTestCase
{
    #[Override]
    protected function setUp(): void
    {
        $this->dispatcher = self::createStub(EventDispatcherInterface::class);

        parent::setUp();
    }

    #[TestDox('Test that form submit with valid input data works as expected')]
    public function testSubmitValidData(): void
    {
        $resourceMock = $this->getRoleResourceMock();
        $rolesServiceMock = $this->getRolesServiceMock();

        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        $resourceMock
            ->expects($this->once())
            ->method('find')
            ->willReturn([$roleEntity]);

        $resourceMock
            ->expects($this->once())
            ->method('findOne')
            ->with($roleEntity->getId())
            ->willReturn($roleEntity);

        $rolesServiceMock
            ->expects($this->once())
            ->method('getRoleLabel')
            ->willReturn('role name');

        // Create form
        $form = $this->factory->create(UserGroupType::class);

        // Create new DTO object
        $dto = new UserGroupDto()
            ->setName('ROLE_ADMIN')
            ->setRole($roleEntity);

        // Specify used form data
        $submitData = [
            'name' => 'ROLE_ADMIN',
            'role' => 'ROLE_ADMIN',
        ];

        // submit the data to the form directly
        $form->submit($submitData);

        // Test that data transformers have not been failed
        self::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        /** @var UserGroupDto $formData */
        $formData = $form->getData();
        self::assertSame($dto->getId(), $formData->getId());
        self::assertSame($dto->getName(), $formData->getName());
        self::assertSame($dto->getRole(), $formData->getRole());

        // Check that form renders correctly
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($submitData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array<int, PreloadedExtension>
     */
    #[Override]
    protected function getExtensions(?ViolationMapperInterface $violationMapper = null): array
    {
        parent::getExtensions($violationMapper);

        $resourceMock = $this->getRoleResourceMock();
        $rolesServiceMock = $this->getRolesServiceMock();

        // create a type instance with the mocked dependencies
        $type = new UserGroupType($rolesServiceMock, $resourceMock, new RoleTransformer($resourceMock));

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @psalm-return MockObject&RolesService
     * @phpstan-return MockObject&RolesService
     */
    private function getRolesServiceMock(): MockObject
    {
        /** @psalm-var MockObject&RolesService|null $rolesServiceMock */
        static $rolesServiceMock = null;

        if ($rolesServiceMock === null) {
            $rolesServiceMock = $this->createMock(RolesService::class);
        }

        return $rolesServiceMock;
    }

    /**
     * @psalm-return MockObject&RoleResource
     * @phpstan-return MockObject&RoleResource
     */
    private function getRoleResourceMock(): MockObject
    {
        /** @psalm-var MockObject&RoleResource|null $roleResourceMock */
        static $roleResourceMock = null;

        if ($roleResourceMock === null) {
            $roleResourceMock = $this->createMock(RoleResource::class);
        }

        return $roleResourceMock;
    }
}
