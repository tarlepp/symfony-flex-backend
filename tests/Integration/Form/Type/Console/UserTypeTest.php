<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Form/Type/Console/UserTypeTest.php
 */

namespace App\Tests\Integration\Form\Type\Console;

use App\DTO\User\User as UserDto;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Enum\Language;
use App\Enum\Locale;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Form\Type\Console\UserType;
use App\Resource\UserGroupResource;
use App\Service\Localization;
use Override;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use function array_keys;

final class UserTypeTest extends TypeTestCase
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
        $userGroupResourceMock = $this->getUserGroupResource();
        $localizationMock = $this->getLocalization();

        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        // Create new user group entity
        $userGroupEntity = new UserGroup()
            ->setRole($roleEntity);

        $userGroupResourceMock
            ->expects($this->once())
            ->method('find')
            ->willReturn([$userGroupEntity]);

        $userGroupResourceMock
            ->expects($this->once())
            ->method('findOne')
            ->with($userGroupEntity->getId())
            ->willReturn($userGroupEntity);

        $localizationMock
            ->expects($this->once())
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
        $dto = new UserDto()
            ->setUsername('username')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@test.com')
            ->setPassword('password')
            ->setLanguage(Language::FI)
            ->setLocale(Locale::FI)
            ->setTimezone('Europe/Stockholm')
            ->setUserGroups([$userGroupEntity]);

        // Specify used form data
        $rawData = [
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
        $form->submit($rawData);

        // Test that data transformers have not been failed
        self::assertTrue($form->isSynchronized());

        $formData = $form->getData();

        self::assertInstanceOf(UserDto::class, $formData);

        // Test that form data matches with the DTO mapping
        self::assertSame($dto->getId(), $formData->getId());
        self::assertSame($dto->getUsername(), $formData->getUsername());
        self::assertSame($dto->getFirstName(), $formData->getFirstName());
        self::assertSame($dto->getLastName(), $formData->getLastName());
        self::assertSame($dto->getEmail(), $formData->getEmail());
        self::assertSame($dto->getLanguage(), $formData->getLanguage());
        self::assertSame($dto->getLocale(), $formData->getLocale());
        self::assertSame($dto->getTimezone(), $formData->getTimezone());
        self::assertSame($dto->getUserGroups(), $formData->getUserGroups());

        // Check that form renders correctly
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($rawData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array<PreloadedExtension>
     */
    #[Override]
    protected function getExtensions(?ViolationMapperInterface $violationMapper = null): array
    {
        parent::getExtensions($violationMapper);

        $userGroupResourceMock = $this->getUserGroupResource();
        $localizationMock = $this->getLocalization();

        // create a type instance with the mocked dependencies
        $type = new UserType(
            $userGroupResourceMock,
            new UserGroupTransformer($userGroupResourceMock),
            $localizationMock,
        );

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @psalm-return MockObject&UserGroupResource
     * @phpstan-return MockObject&UserGroupResource
     */
    private function getUserGroupResource(): MockObject
    {
        /** @psalm-var MockObject&UserGroupResource|null $userGroupResourceMock */
        static $userGroupResourceMock = null;

        if ($userGroupResourceMock === null) {
            $userGroupResourceMock = $this->createMock(UserGroupResource::class);
        }

        return $userGroupResourceMock;
    }

    /**
     * @psalm-return MockObject&Localization
     * @phpstan-return MockObject&Localization
     */
    private function getLocalization(): MockObject
    {
        /** @psalm-var MockObject&Localization|null $localizationMock */
        static $localizationMock = null;

        if ($localizationMock === null) {
            $localizationMock = $this->createMock(Localization::class);
        }

        return $localizationMock;
    }
}
