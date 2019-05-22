<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/Type/Rest/User/UserPatchTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\Type\Rest\User;

use App\DTO\User as UserDto;
use App\Form\Type\Rest\User\UserPatchType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class UserPatchTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Rest\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserPatchTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        // Create form
        $form = $this->factory->create(UserPatchType::class);

        // Create new DTO object
        $dto = new UserDto();
        $dto->setUsername('username');
        $dto->setFirstname('John');
        $dto->setSurname('Doe');
        $dto->setEmail('john.doe@test.com');

        // Specify used form data
        $formData = array(
            'username'      => 'username',
            'firstname'     => 'John',
            'surname'       => 'Doe',
            'email'         => 'john.doe@test.com',
        );

        // submit the data to the form directly
        $form->submit($formData);

        // Test that data transformers have not been failed
        static::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        static::assertEquals($dto, $form->getData());

        // Check that form renders correctly
        $view = $form->createView();
        $children = $view->children;

        foreach (\array_keys($formData) as $key) {
            static::assertArrayHasKey($key, $children);
        }

        unset($view, $dto, $form);
    }
}
