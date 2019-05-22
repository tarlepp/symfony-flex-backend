<?php
declare(strict_types = 1);
/**
 * /src/Form/Type\Rest/User/UserCreateType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\Type\Rest\User;

use App\DTO\User as UserDto;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserCreateType
 *
 * @package App\Form\Type\Rest\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserCreateType extends UserType
{
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
            'data_class' => UserDto::class,
            'validation_groups' => ['Create', 'Default'],
        ]);
    }
}
