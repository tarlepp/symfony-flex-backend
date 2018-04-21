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
use function array_map;

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
     * @return mixed[]
     */
    protected function getUserGroupChoices(): array
    {
        // Initialize output
        $choices = [];

        /**
         * Lambda function to iterate all user groups and to create necessary choices array.
         *
         * @param UserGroup $userGroup
         */
        $iterator = function (UserGroup $userGroup) use (&$choices): void {
            $name = $userGroup->getName() . ' [' . $userGroup->getRole()->getId() . ']';

            $choices[$name] = $userGroup->getId();
        };

        /** @var UserGroup[] $userGroups */
        $userGroups = $this->userGroupResource->find();

        array_map($iterator, $userGroups);

        return $choices;
    }
}
