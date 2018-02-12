<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Rest/User/UserType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Rest\User;

use App\Form\Type\FormTypeLabelInterface;
use App\Form\Type\Traits\AddBasicFieldToForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class UserType
 *
 * @package App\Form\Type\Rest\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class UserType extends AbstractType
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
                FormTypeLabelInterface::LABEL      => 'Username',
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'firstname',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL      => 'Firstname',
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'surname',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL      => 'Surname',
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'email',
            Type\EmailType::class,
            [
                FormTypeLabelInterface::LABEL      => 'Email address',
                FormTypeLabelInterface::EMPTY_DATA => '',
            ],
        ],
        [
            'password',
            Type\TextType::class,
            [
                FormTypeLabelInterface::LABEL      => 'Password',
                FormTypeLabelInterface::EMPTY_DATA => '',
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
}
