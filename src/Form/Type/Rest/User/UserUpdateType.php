<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Rest/User/UserUpdateType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\Type\Rest\User;

use App\DTO\User as UserDto;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserUpdateType
 *
 * @package App\Form\Type\Rest\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserUpdateType extends UserType
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
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => UserDto::class,
        ]);
    }
}
