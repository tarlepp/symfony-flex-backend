<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/Type/Console/ApiKeyTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Form\Type\Console;

use App\DTO\ApiKey\ApiKey as ApiKeyDto;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Form\Type\Console\ApiKeyType;
use App\Resource\UserGroupResource;
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
final class ApiKeyTypeTest extends TypeTestCase
{
    #[TestDox('Test that form submit with valid input data works as expected')]
    public function testSubmitValidData(): void
    {
        $resource = $this->getUserGroupResource();

        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        // Create new apiKey group entity
        $userGroupEntity = new UserGroup()
            ->setRole($roleEntity)
            ->setName('Some name');

        $resource
            ->expects($this->once())
            ->method('find')
            ->willReturn([$userGroupEntity]);

        $resource
            ->expects($this->once())
            ->method('findOne')
            ->with($userGroupEntity->getId())
            ->willReturn($userGroupEntity);

        // Create form
        $form = $this->factory->create(ApiKeyType::class);

        // Create new DTO object
        $dto = new ApiKeyDto()
            ->setDescription('description')
            ->setUserGroups([$userGroupEntity]);

        // Specify used form data
        $formData = [
            'description' => 'description',
            'userGroups' => [$userGroupEntity->getId()],
        ];

        // submit the data to the form directly
        $form->submit($formData);

        // Test that data transformers have not been failed
        self::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        self::assertSame($dto->getId(), $form->getData()->getId());
        self::assertSame($dto->getDescription(), $form->getData()->getDescription());
        self::assertSame($dto->getUserGroups(), $form->getData()->getUserGroups());

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

        $resource = $this->getUserGroupResource();

        // create a type instance with the mocked dependencies
        $type = new ApiKeyType($resource, new UserGroupTransformer($resource));

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @phpstan-return MockObject&UserGroupResource
     */
    private function getUserGroupResource(): MockObject
    {
        static $cache;

        if ($cache === null) {
            $cache = $this->createMock(UserGroupResource::class);
        }

        return $cache;
    }
}
