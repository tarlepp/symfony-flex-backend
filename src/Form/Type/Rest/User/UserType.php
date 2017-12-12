<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Rest/User/UserType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Rest\User;

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
                'label'         => 'Username',
                'empty_data'    => '',
            ],
        ],
        [
            'firstname',
            Type\TextType::class,
            [
                'label'         => 'Firstname',
                'empty_data'    => '',
            ],
        ],
        [
            'surname',
            Type\TextType::class,
            [
                'label'         => 'Surname',
                'empty_data'    => '',
            ],
        ],
        [
            'email',
            Type\EmailType::class,
            [
                'label'         => 'Email address',
                'empty_data'    => '',
            ],
        ],
        [
            'password',
            Type\TextType::class,
            [
                'label'         => 'Password',
                'empty_data'    => '',
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
