<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/Type/Console/ApiKeyTypeTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\Type\Console;

use App\DTO\ApiKey as ApiKeyDto;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Form\Type\Console\ApiKeyType;
use App\Repository\UserGroupRepository;
use App\Resource\UserGroupResource;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class ApiKeyTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Console
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyTypeTest extends TypeTestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    private $mockObjectManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|UserGroupResource
     */
    private $mockUserGroupResource;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|UserGroupRepository
     */
    private $mockUserGroupRepository;

    public function testSubmitValidData(): void
    {
        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        // Create new apiKey group entity
        $userGroupEntity = new UserGroup();
        $userGroupEntity->setRole($roleEntity);

        $this->mockObjectManager
            ->expects(static::once())
            ->method('getRepository')
            ->willReturn($this->mockUserGroupRepository);

        $this->mockUserGroupRepository
            ->expects(static::once())
            ->method('find')
            ->willReturn($userGroupEntity);

        $this->mockUserGroupResource
            ->expects(static::once())
            ->method('find')
            ->willReturn([$userGroupEntity]);

        // Create form
        $form = $this->factory->create(ApiKeyType::class);

        // Create new DTO object
        $dto = new ApiKeyDto();
        $dto->setDescription('description');
        $dto->setUserGroups([$userGroupEntity]);

        // Specify used form data
        $formData = array(
            'description'   => 'description',
            'userGroups'    => [$userGroupEntity->getId()],
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
        $this->mockObjectManager = $this->createMock(ObjectManager::class);
        $this->mockUserGroupResource = $this->createMock(UserGroupResource::class);
        $this->mockUserGroupRepository = $this->createMock(UserGroupRepository::class);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions(): array
    {
        parent::getExtensions();

        // create a type instance with the mocked dependencies
        $type = new ApiKeyType($this->mockObjectManager, $this->mockUserGroupResource);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }
}
