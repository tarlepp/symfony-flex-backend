<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Console/UserType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Console;

use App\DTO\User as UserDto;
use App\Entity\UserGroup;
use App\Form\DataTransformer\UserGroupTransformer;
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
    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

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
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'username',
                Type\TextType::class,
                [
                    'label'         => 'Username',
                    'required'      => true,
                    'empty_data'    => '',
                ]
            )
            ->add(
                'firstname',
                Type\TextType::class,
                [
                    'label'         => 'Firstname',
                    'required'      => true,
                    'empty_data'    => '',
                ]
            )
            ->add(
                'surname',
                Type\TextType::class,
                [
                    'label'         => 'Surname',
                    'required'      => true,
                    'empty_data'    => '',
                ]
            )
            ->add(
                'email',
                Type\EmailType::class,
                [
                    'label'         => 'Email address',
                    'required'      => true,
                    'empty_data'    => '',
                ]
            )
            ->add(
                'password',
                Type\RepeatedType::class,
                [
                    'type'              => Type\PasswordType::class,
                    'required'          => true,
                    'first_name'        => 'password1',
                    'first_options'     => [
                        'label' => 'Password',
                    ],
                    'second_name'       => 'password2',
                    'second_options'    => [
                        'label' => 'Repeat password',
                    ],
                ]
            )
            ->add(
                'userGroups',
                Type\ChoiceType::class,
                [
                    'choices'       => $this->getUserGroupChoices(),
                    'multiple'      => true,
                    'required'      => true,
                    'empty_data'    => '',
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

    /**
     * Method to create choices array for user groups.
     *
     * @return  array
     */
    private function getUserGroupChoices(): array
    {
        // Initialize output
        $choices = [];

        /**
         * Lambda function to iterate all user groups and to create necessary choices array.
         *
         * @param UserGroup $userGroup
         *
         * @return void
         */
        $iterator = function (UserGroup $userGroup) use (&$choices) {
            $name = $userGroup->getName() . ' [' . $userGroup->getRole()->getId() . ']';

            $choices[$name] = $userGroup->getId();
        };

        \array_map($iterator, $this->userGroupResource->find());

        return $choices;
    }
}
