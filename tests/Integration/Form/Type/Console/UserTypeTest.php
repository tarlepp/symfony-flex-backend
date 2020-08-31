<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/Type/Console/UserTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Form\Type\Console;

use App\DTO\User\User as UserDto;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Form\Type\Console\UserType;
use App\Resource\UserGroupResource;
use App\Service\Localization;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Throwable;
use function array_keys;

/**
 * Class UserTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTypeTest extends TypeTestCase
{
    /**
     * @var MockObject|UserGroupResource
     */
    private MockObject $mockUserGroupResource;

    /**
     * @var MockObject|Localization
     */
    private MockObject $mockLocalization;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->mockUserGroupResource = $this->createMock(UserGroupResource::class);
        $this->mockLocalization = $this->createMock(Localization::class);

        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        // Create new user group entity
        $userGroupEntity = (new UserGroup())
            ->setRole($roleEntity);

        $this->mockUserGroupResource
            ->expects(static::once())
            ->method('find')
            ->willReturn([$userGroupEntity]);

        $this->mockUserGroupResource
            ->expects(static::once())
            ->method('findOne')
            ->with($userGroupEntity->getId())
            ->willReturn($userGroupEntity);

        $this->mockLocalization
            ->expects(static::once())
            ->method('getLanguages')
            ->willReturn(['en', 'fi']);

        $this->mockLocalization
            ->expects(static::once())
            ->method('getLocales')
            ->willReturn(['en', 'fi']);

        $this->mockLocalization
            ->expects(static::once())
            ->method('getFormattedTimezones')
            ->willReturn([
                [
                    'timezone' => 'Europe',
                    'identifier' => 'Europe/Helsinki',
                    'offset' => 'GMT+2:00',
                    'value' => 'Europe/Helsinki',
                ],
                [
                    'timezone' => 'Europe',
                    'identifier' => 'Europe/Stockholm',
                    'offset' => 'GMT+1:00',
                    'value' => 'Europe/Stockholm',
                ],
            ]);

        // Create form
        $form = $this->factory->create(UserType::class);

        // Create new DTO object
        $dto = (new UserDto())
            ->setUsername('username')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@test.com')
            ->setPassword('password')
            ->setLanguage('fi')
            ->setLocale('fi')
            ->setTimezone('Europe/Stockholm')
            ->setUserGroups([$userGroupEntity]);

        // Specify used form data
        $formData = [
            'username' => 'username',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@test.com',
            'password' => [
                'password1' => 'password',
                'password2' => 'password',
            ],
            'language' => 'fi',
            'locale' => 'fi',
            'timezone' => 'Europe/Stockholm',
            'userGroups' => [$userGroupEntity->getId()],
        ];

        // submit the data to the form directly
        $form->submit($formData);

        // Test that data transformers have not been failed
        static::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        static::assertSame($dto->getId(), $form->getData()->getId());
        static::assertSame($dto->getUsername(), $form->getData()->getUsername());
        static::assertSame($dto->getFirstName(), $form->getData()->getFirstName());
        static::assertSame($dto->getLastName(), $form->getData()->getLastName());
        static::assertSame($dto->getEmail(), $form->getData()->getEmail());
        static::assertSame($dto->getLanguage(), $form->getData()->getLanguage());
        static::assertSame($dto->getLocale(), $form->getData()->getLocale());
        static::assertSame($dto->getTimezone(), $form->getData()->getTimezone());
        static::assertSame($dto->getUserGroups(), $form->getData()->getUserGroups());

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
        $type = new UserType(
            $this->mockUserGroupResource,
            new UserGroupTransformer($this->mockUserGroupResource),
            $this->mockLocalization
        );

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }
}
