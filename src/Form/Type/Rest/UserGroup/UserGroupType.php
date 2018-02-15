<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Rest/UserGroup/UserGroupType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Rest\UserGroup;

use App\DTO\UserGroup as UserGroupDto;
use App\Entity\Role as RoleEntity;
use App\Form\DataTransformer\RoleTransformer;
use App\Form\Type\FormTypeLabelInterface;
use App\Form\Type\Traits\AddBasicFieldToForm;
use App\Resource\RoleResource;
use App\Security\RolesService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserGroupType
 *
 * @package App\Form\Type\Rest\UserGroup
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupType extends AbstractType
{
    // Traits
    use AddBasicFieldToForm;

    /**
     * Base form fields
     *
     * @var mixed[]
     */
    private static $formFields = [
        [
            'name',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL      => 'Group name',
                FormTypeLabelInterface::REQUIRED   => true,
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
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addBasicFieldToForm($builder, self::$formFields);

        $builder
            ->add(
                'role',
                Type\ChoiceType::class,
                [
                    FormTypeLabelInterface::LABEL    => 'Role',
                    FormTypeLabelInterface::CHOICES  => $this->getRoleChoices(),
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
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserGroupDto::class,
        ]);
    }

    /**
     * Method to  choices array for user groups.
     *
     * @return mixed[]
     */
    public function getRoleChoices(): array
    {
        // Initialize output
        $choices = [];

        $iterator = function (RoleEntity $role) use (&$choices): void {
            $name = $this->rolesService->getRoleLabel($role->getId());

            $choices[$name] = $role->getId();
        };

        \array_map($iterator, $this->roleResource->find());

        return $choices;
    }
}
