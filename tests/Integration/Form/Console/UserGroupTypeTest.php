<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/Console/UserGroupTypeTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\Console;

use App\Form\Console\UserGroupType;
use App\Repository\RoleRepository;
use App\Rest\DTO\UserGroup as UserGroupDto;
use App\Security\Roles;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactory;

/**
 * Class UserGroupTypeTest
 *
 * @package App\Tests\Integration\Form\Console
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTypeTest extends KernelTestCase
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->formFactory = static::$kernel->getContainer()->get('form.factory');
    }

    /**
     * @dataProvider dataProviderTestSubmitValidData
     *
     * @param string $roleName
     */
    public function testSubmitValidData(string $roleName): void
    {
        // Create form
        $form = $this->formFactory->create(UserGroupType::class);

        /** @var RoleRepository $roleRepository */
        $roleRepository = static::$kernel->getContainer()->get(RoleRepository::class);
        $roleEntity = $roleRepository->find($roleName);

        // Create new DTO object
        $dto = new UserGroupDto();
        $dto->setName($roleName);
        $dto->setRole($roleEntity);

        // Specify used form data
        $formData = array(
            'name'  => $roleName,
            'role'  => $roleName,
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

    /**
     * @return array
     */
    public function dataProviderTestSubmitValidData(): array
    {
        static::bootKernel();

        $formatter = function (string $role) {
            return (array)$role;
        };

        return \array_map($formatter, static::$kernel->getContainer()->get(Roles::class)->getRoles());
    }
}
