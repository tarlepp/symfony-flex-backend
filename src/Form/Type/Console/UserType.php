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
use App\Service\Localization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;
use function array_combine;
use function array_map;

/**
 * Class UserType
 *
 * @package App\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserType extends AbstractType
{
    use AddBasicFieldToForm;
    use UserGroupChoices;

    /**
     * Base form fields
     *
     * @var array<int, array<int, mixed>>
     */
    private static array $formFields = [
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

    private UserGroupTransformer $userGroupTransformer;
    private Localization $localization;

    /**
     * UserType constructor.
     */
    public function __construct(
        UserGroupResource $userGroupResource,
        UserGroupTransformer $userGroupTransformer,
        Localization $localization
    ) {
        $this->userGroupTransformer = $userGroupTransformer;
        $this->userGroupResource = $userGroupResource;
        $this->localization = $localization;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->addBasicFieldToForm($builder, self::$formFields);
        $this->addLocalizationFieldsToForm($builder);

        $builder
            ->add(
                'userGroups',
                Type\ChoiceType::class,
                [
                    FormTypeLabelInterface::CHOICES => $this->getUserGroupChoices(),
                    FormTypeLabelInterface::REQUIRED => true,
                    FormTypeLabelInterface::EMPTY_DATA => '',
                    'multiple' => true,
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

    private function addLocalizationFieldsToForm(FormBuilderInterface $builder): void
    {
        $languages = $this->localization->getLanguages();
        $locales = $this->localization->getLocales();

        $builder
            ->add(
                'language',
                Type\ChoiceType::class,
                [
                    FormTypeLabelInterface::LABEL => 'Language',
                    FormTypeLabelInterface::REQUIRED => true,
                    FormTypeLabelInterface::EMPTY_DATA => Localization::DEFAULT_LANGUAGE,
                    FormTypeLabelInterface::CHOICES => array_combine($languages, $languages),
                ],
            );

        $builder
            ->add(
                'locale',
                Type\ChoiceType::class,
                [
                    FormTypeLabelInterface::LABEL => 'Locale',
                    FormTypeLabelInterface::REQUIRED => true,
                    FormTypeLabelInterface::EMPTY_DATA => Localization::DEFAULT_LOCALE,
                    FormTypeLabelInterface::CHOICES => array_combine($locales, $locales),
                ],
            );

        $builder
            ->add(
                'timezone',
                Type\ChoiceType::class,
                [
                    FormTypeLabelInterface::LABEL => 'Timezone',
                    FormTypeLabelInterface::REQUIRED => true,
                    FormTypeLabelInterface::EMPTY_DATA => Localization::DEFAULT_TIMEZONE,
                    FormTypeLabelInterface::CHOICES => $this->getTimeZoneChoices(),
                ],
            );
    }

    /**
     * Method to get choices array for time zones.
     *
     * @return array<string, string>
     */
    private function getTimeZoneChoices(): array
    {
        // Initialize output
        $choices = [];

        $iterator = static function (array $timezone) use (&$choices): void {
            $choices[$timezone['value'] . ' (' . $timezone['offset'] . ')'] = $timezone['identifier'];
        };

        array_map($iterator, $this->localization->getFormattedTimezones());

        return $choices;
    }
}
