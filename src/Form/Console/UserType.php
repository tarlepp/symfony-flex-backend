<?php
declare(strict_types=1);
/**
 * /src/Form/Console/UserType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Console;

use App\Entity\UserGroup;
use App\Form\Console\DataTransformer\UserGroupTransformer;
use App\Resource\UserGroupResource;
use App\Rest\DTO\User as UserDto;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserType
 *
 * @package App\Form\Console
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * UserType constructor.
     *
     * @param ObjectManager     $objectManager
     * @param UserGroupResource $userGroupResource
     */
    public function __construct(ObjectManager $objectManager, UserGroupResource $userGroupResource)
    {
        $this->objectManager = $objectManager;
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
                    'label'     => 'Username',
                    'required'  => true,
                ]
            )
            ->add(
                'firstname',
                Type\TextType::class,
                [
                    'label'     => 'Firstname',
                    'required'  => true,
                ]
            )
            ->add(
                'surname',
                Type\TextType::class,
                [
                    'label'     => 'Surname',
                    'required'  => true,
                ]
            )
            ->add(
                'email',
                Type\EmailType::class,
                [
                    'label'     => 'Email address',
                    'required'  => true,
                ]
            )
            ->add(
                'plainPassword',
                Type\PasswordType::class,
                [
                    'label'     => 'Password',
                ]
            )
            ->add(
                'userGroups',
                Type\ChoiceType::class,
                [
                    'choices'   => $this->getUserGroupChoices(),
                    'multiple'  => true,
                    'required'  => true,
                ]
            );

        $builder->get('userGroups')->addModelTransformer(new UserGroupTransformer($this->objectManager));
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
