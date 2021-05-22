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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use function array_keys;

/**
 * Class ApiKeyTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        // Create new apiKey group entity
        $userGroupEntity = (new UserGroup())
            ->setRole($roleEntity)
            ->setName('Some name');

        $userGroupResourceMock = $this->getMock();

        $userGroupResourceMock
            ->expects(static::once())
            ->method('find')
            ->willReturn([$userGroupEntity]);

        $userGroupResourceMock
            ->expects(static::once())
            ->method('findOne')
            ->with($userGroupEntity->getId())
            ->willReturn($userGroupEntity);

        // Create form
        $form = $this->factory->create(ApiKeyType::class);

        // Create new DTO object
        $dto = (new ApiKeyDto())
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
        static::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        static::assertSame($dto->getId(), $form->getData()->getId());
        static::assertSame($dto->getDescription(), $form->getData()->getDescription());
        static::assertSame($dto->getUserGroups(), $form->getData()->getUserGroups());

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

        $userGroupResourceMock = $this->getMock();

        // create a type instance with the mocked dependencies
        $type = new ApiKeyType($userGroupResourceMock, new UserGroupTransformer($userGroupResourceMock));

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @return MockObject&UserGroupResource
     */
    private function getMock(): MockObject | UserGroupResource
    {
        static $cache;

        if (!$cache) {
            $cache = $this->createMock(UserGroupResource::class);
        }

        return $cache;
    }
}
