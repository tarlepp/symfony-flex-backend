<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Traits/UserGroupChoices.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Form\Type\Traits;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;

/**
 * Trait UserGroupChoices
 *
 * @package App\Form\Type\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UserGroupChoices
{
    /**
     * @var UserGroupResource
     */
    protected $userGroupResource;

    /**
     * Method to create choices array for user groups.
     *
     * @return  array
     */
    protected function getUserGroupChoices(): array
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
