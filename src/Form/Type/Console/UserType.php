<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Console/UserType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Console;

use App\DTO\User as UserDto;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Form\Type\Label;
use App\Form\Type\Traits\AddBasicFieldToForm;
use App\Form\Type\Traits\UserGroupChoices;
use App\Resource\UserGroupResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserType
 *
 * @package App\Form\Type\Console
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserType extends AbstractType
{
    // Traits
    use AddBasicFieldToForm;
    use UserGroupChoices;

    /**
     * Base form fields
     *
     * @var array
     */
    static private $formFields = [
        [
            'username',
            Type\TextType::class,
            [
                Label::LABEL      => 'Username',
                Label::REQUIRED   => true,
                Label::EMPTY_DATA => '',
            ],
        ],
        [
            'firstname',
            Type\TextType::class,
            [
                Label::LABEL      => 'Firstname',
                Label::REQUIRED   => true,
                Label::EMPTY_DATA => '',
            ],
        ],
        [
            'surname',
            Type\TextType::class,
            [
                Label::LABEL      => 'Surname',
                Label::REQUIRED   => true,
                Label::EMPTY_DATA => '',
            ],
        ],
        [
            'email',
            Type\EmailType::class,
            [
                Label::LABEL      => 'Email address',
                Label::REQUIRED   => true,
                Label::EMPTY_DATA => '',
            ],
        ],
        [
            'password',
            Type\RepeatedType::class,
            [
                Label::TYPE           => Type\PasswordType::class,
                Label::REQUIRED       => true,
                Label::FIRST_NAME     => 'password1',
                Label::FIRST_OPTIONS  => [
                    Label::LABEL      => 'Password',
                ],
                Label::SECOND_NAME    => 'password2',
                Label::SECOND_OPTIONS => [
                    Label::LABEL      => 'Repeat password',
                ],
            ],
        ],
    ];

    /**
     * @var UserGroupTransformer
     */
    private $userGroupTransformer;

    /**
     * UserType constructor.
     *
     * @param UserGroupResource    $userGroupResource
     * @param UserGroupTransformer $userGroupTransformer
     */
    public function __construct(UserGroupResource $userGroupResource, UserGroupTransformer $userGroupTransformer)
    {
        $this->userGroupTransformer = $userGroupTransformer;
        $this->userGroupResource = $userGroupResource;
    }

    /**
     * @SuppressWarnings("unused")
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addBasicFieldToForm($builder, self::$formFields);

        $builder
            ->add(
                'userGroups',
                Type\ChoiceType::class,
                [
                    'choices'       => $this->getUserGroupChoices(),
                    'multiple'      => true,
                    Label::REQUIRED      => true,
                    Label::EMPTY_DATA    => '',
                ]
            );

        $builder->get('userGroups')->addModelTransformer($this->userGroupTransformer);
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
            'data_class' => UserDto::class
        ]);
    }
}
