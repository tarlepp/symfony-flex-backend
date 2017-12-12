<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Rest/User/UserPatchType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Rest\User;

use App\DTO\User as UserDto;
use App\Form\Type\Traits\AddBasicFieldToForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserPatchType
 *
 * @package App\Form\Type\Rest\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserPatchType extends AbstractType
{
    // Traits
    use AddBasicFieldToForm;

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
                'label' => 'Username',
            ],
        ],
        [
            'firstname',
            Type\TextType::class,
            [
                'label' => 'Firstname',
            ],
        ],
        [
            'surname',
            Type\TextType::class,
            [
                'label' => 'Surname',
            ],
        ],
        [
            'email',
            Type\EmailType::class,
            [
                'label' => 'Email address',
            ],
        ],
        [
            'password',
            Type\TextType::class,
            [
                'label' => 'Password',
            ],
        ],
    ];

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
