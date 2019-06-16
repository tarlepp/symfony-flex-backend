<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Console/UserType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\Type\Console;

use App\DTO\User\User as UserDto;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Form\Type\FormTypeLabelInterface;
use App\Form\Type\Traits\AddBasicFieldToForm;
use App\Form\Type\Traits\UserGroupChoices;
use App\Resource\UserGroupResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
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
     * @var mixed[]
     */
    private static $formFields = [
        [
            'username',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'Username',
                FormTypeLabelInterface::REQUIRED => true,
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'firstName',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'First name',
                FormTypeLabelInterface::REQUIRED => true,
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'lastName',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'Last name',
                FormTypeLabelInterface::REQUIRED => true,
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'email',
            Type\EmailType::class,
            [
                FormTypeLabelInterface::LABEL => 'Email address',
                FormTypeLabelInterface::REQUIRED => true,
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'password',
            Type\RepeatedType::class,
            [
                FormTypeLabelInterface::TYPE => Type\PasswordType::class,
                FormTypeLabelInterface::REQUIRED => true,
                FormTypeLabelInterface::FIRST_NAME => 'password1',
                FormTypeLabelInterface::FIRST_OPTIONS => [
                    FormTypeLabelInterface::LABEL => 'Password',
                ],
                FormTypeLabelInterface::SECOND_NAME => 'password2',
                FormTypeLabelInterface::SECOND_OPTIONS => [
                    FormTypeLabelInterface::LABEL => 'Repeat password',
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
     * @param mixed[]              $options
     *
     * @throws InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->addBasicFieldToForm($builder, self::$formFields);

        $builder
            ->add(
                'userGroups',
                Type\ChoiceType::class,
                [
                    'choices' => $this->getUserGroupChoices(),
                    'multiple' => true,
                    FormTypeLabelInterface::REQUIRED => true,
                    FormTypeLabelInterface::EMPTY_DATA => '',
                ]
            );

        $builder->get('userGroups')->addModelTransformer($this->userGroupTransformer);
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
            'data_class' => UserDto::class,
        ]);
    }
}
