<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Console/UserGroupType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\Type\Console;

use App\DTO\UserGroup\UserGroup;
use App\Entity\Role as RoleEntity;
use App\Form\DataTransformer\RoleTransformer;
use App\Form\Type\FormTypeLabelInterface;
use App\Form\Type\Traits\AddBasicFieldToForm;
use App\Resource\RoleResource;
use App\Security\RolesService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

/** @noinspection EmptyClassInspection */
/**
 * Class UserGroupType
 *
 * @package App\Form\Type\Console
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupType extends AbstractType
{
    // Traits
    use AddBasicFieldToForm;

    /**
     * Base form fields
     *
     * @var array<int, array<int, mixed>>
     */
    private static $formFields = [
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

    /**
     * @var RolesService
     */
    private $rolesService;

    /**
     * @var RoleResource
     */
    private $roleResource;

    /**
     * @var RoleTransformer
     */
    private $roleTransformer;

    /**
     * UserGroupType constructor.
     *
     * @param RolesService    $rolesService
     * @param RoleResource    $roleResource
     * @param RoleTransformer $roleTransformer
     */
    public function __construct(
        RolesService $rolesService,
        RoleResource $roleResource,
        RoleTransformer $roleTransformer
    ) {
        $this->rolesService = $rolesService;
        $this->roleResource = $roleResource;
        $this->roleTransformer = $roleTransformer;
    }

    /**
     * @SuppressWarnings("unused")
     *
     * @param FormBuilderInterface $builder
     * @param mixed[]              $options
     *
     * @throws InvalidArgumentException
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
                ]
            );

        $builder->get('role')->addModelTransformer($this->roleTransformer);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => UserGroup::class,
        ]);
    }

    /**
     * Method to  choices array for user groups.
     *
     * @return mixed[]
     *
     * @throws Throwable
     */
    public function getRoleChoices(): array
    {
        // Initialize output
        $choices = [];

        $iterator = function (RoleEntity $role) use (&$choices): void {
            $name = $this->rolesService->getRoleLabel($role->getId());

            $choices[$name] = $role->getId();
        };

        /** @var RoleEntity[] $roles */
        $roles = $this->roleResource->find();

        array_map($iterator, $roles);

        return $choices;
    }
}
