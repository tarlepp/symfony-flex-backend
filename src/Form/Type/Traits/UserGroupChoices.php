<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/Traits/UserGroupChoices.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Form\Type\Traits;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Throwable;
use function array_map;

/**
 * Trait UserGroupChoices
 *
 * @package App\Form\Type\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UserGroupChoices
{
    protected UserGroupResource $userGroupResource;

    /**
     * Method to create choices array for user groups.
     *
     * @return array<string, string>
     *
     * @throws Throwable
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
        $iterator = static function (UserGroup $userGroup) use (&$choices): void {
            $name = $userGroup->getName() . ' [' . $userGroup->getRole()->getId() . ']';

            $choices[$name] = $userGroup->getId();
        };

        $userGroups = $this->userGroupResource->find();

        array_map($iterator, $userGroups);

        return $choices;
    }
}
