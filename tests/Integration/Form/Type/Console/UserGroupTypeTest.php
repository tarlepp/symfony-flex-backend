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
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use function array_keys;

/**
 * Class UserGroupTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        [$roleResourceMock, $rolesServiceMock] = $this->getMocks();

        $roleResourceMock
            ->expects(static::once())
            ->method('find')
            ->willReturn([$roleEntity]);

        $roleResourceMock
            ->expects(static::once())
            ->method('findOne')
            ->with($roleEntity->getId())
            ->willReturn($roleEntity);

        $rolesServiceMock
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

    /**
     * @return array<int, PreloadedExtension>
     */
    protected function getExtensions(): array
    {
        parent::getExtensions();

        [$roleResourceMock, $rolesServiceMock] = $this->getMocks();

        // create a type instance with the mocked dependencies
        $type = new UserGroupType(
            $rolesServiceMock,
            $roleResourceMock,
            new RoleTransformer($roleResourceMock)
        );

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&RoleResource,
     *      1: \PHPUnit\Framework\MockObject\MockObject&RolesService,
     *  }
     */
    private function getMocks(): array
    {
        static $cache;

        if (!$cache) {
            $cache = [
                $this->createMock(RoleResource::class),
                $this->createMock(RolesService::class),
            ];
        }

        return $cache;
    }
}
