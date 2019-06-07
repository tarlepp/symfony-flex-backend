<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Rest/User/UserPatchType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\Type\Rest\User;

use App\DTO\User as UserDto;
use App\Form\Type\FormTypeLabelInterface;
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
     * @var mixed[]
     */
    private static $formFields = [
        [
            'username',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'Username',
            ],
        ],
        [
            'firstName',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'First name',
            ],
        ],
        [
            'lastName',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'Last name',
            ],
        ],
        [
            'email',
            Type\EmailType::class,
            [
                FormTypeLabelInterface::LABEL => 'Email address',
            ],
        ],
        [
            'password',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL => 'Password',
            ],
        ],
    ];

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
        parent::buildForm($builder, $options);

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
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => UserDto::class,
        ]);
    }
}
