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
use Override;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use function array_keys;

/**
 * @package App\Tests\Integration\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserGroupTypeTest extends TypeTestCase
{
    #[TestDox('Test that form submit with valid input data works as expected')]
    public function testSubmitValidData(): void
    {
        $resource = $this->getRoleResource();
        $service = $this->getRolesService();

        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        $resource
            ->expects($this->once())
            ->method('find')
            ->willReturn([$roleEntity]);

        $resource
            ->expects($this->once())
            ->method('findOne')
            ->with($roleEntity->getId())
            ->willReturn($roleEntity);

        $service
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
    #[Override]
    protected function getExtensions(): array
    {
        parent::getExtensions();

        $resource = $this->getRoleResource();
        $service = $this->getRolesService();

        // create a type instance with the mocked dependencies
        $type = new UserGroupType($service, $resource, new RoleTransformer($resource));

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @phpstan-return MockObject&RolesService
     */
    private function getRolesService(): MockObject
    {
        static $cache;

        if ($cache === null) {
            $cache = $this->createMock(RolesService::class);
        }

        return $cache;
    }

    /**
     * @phpstan-return MockObject&RoleResource
     */
    private function getRoleResource(): MockObject
    {
        static $cache;

        if ($cache === null) {
            $cache = $this->createMock(RoleResource::class);
        }

        return $cache;
    }
}
