<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Console/UserGroupType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Form\Type\Console;

use App\DTO\UserGroup\UserGroup;
use App\Entity\Role;
use App\Form\DataTransformer\RoleTransformer;
use App\Form\Type\FormTypeLabelInterface;
use App\Form\Type\Traits\AddBasicFieldToForm;
use App\Resource\RoleResource;
use App\Security\RolesService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

/**
 * Class UserGroupType
 *
 * @psalm-suppress MissingTemplateParam
 *
 * @package App\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupType extends AbstractType
{
    use AddBasicFieldToForm;

    /**
     * Base form fields
     *
     * @var array<int, array<int, mixed>>
     */
    private static array $formFields = [
        [
            'name',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'Group name',
                FormTypeLabelInterface::REQUIRED => true,
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
    ];

    public function __construct(
        private readonly RolesService $rolesService,
        private readonly RoleResource $roleResource,
        private readonly RoleTransformer $roleTransformer,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->addBasicFieldToForm($builder, self::$formFields);

        $builder
            ->add(
                'role',
                Type\ChoiceType::class,
                [
                    FormTypeLabelInterface::LABEL => 'Role',
                    FormTypeLabelInterface::CHOICES => $this->getRoleChoices(),
                    FormTypeLabelInterface::REQUIRED => true,
                ],
            );

        $builder->get('role')->addModelTransformer($this->roleTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => UserGroup::class,
        ]);
    }

    /**
     * Method to get choices array for user groups.
     *
     * @return array<string, string>
     *
     * @throws Throwable
     */
    public function getRoleChoices(): array
    {
        // Initialize output
        $choices = [];

        $iterator = function (Role $role) use (&$choices): void {
            $choices[$this->rolesService->getRoleLabel($role->getId())] = $role->getId();
        };

        array_map($iterator, $this->roleResource->find());

        return $choices;
    }
}
