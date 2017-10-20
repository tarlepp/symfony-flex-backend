<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Console/ApiKeyType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Console;

use App\DTO\ApiKey;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Form\Type\Traits\UserGroupChoices;
use App\Resource\UserGroupResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ApiKeyType
 *
 * @package App\Form\Type\Console
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyType extends AbstractType
{
    // Traits
    use UserGroupChoices;

    /**
     * @var UserGroupResource
     */
    protected $userGroupResource;

    /**
     * @var UserGroupTransformer
     */
    private $userGroupTransformer;

    /**
     * ApiKeyType constructor.
     *
     * @param UserGroupResource    $userGroupResource
     * @param UserGroupTransformer $userGroupTransformer
     */
    public function __construct(UserGroupResource $userGroupResource, UserGroupTransformer $userGroupTransformer)
    {
        $this->userGroupResource = $userGroupResource;
        $this->userGroupTransformer = $userGroupTransformer;
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
                'description',
                Type\TextType::class,
                [
                    'label'         => 'Description',
                    'required'      => true,
                    'empty_data'    => '',
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
            'data_class' => ApiKey::class
        ]);
    }
}
