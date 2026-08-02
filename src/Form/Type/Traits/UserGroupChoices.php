<?php
declare(strict_types = 1);

/**
 * /src/Form/Type/Traits/UserGroupChoices.php
 */

namespace App\Form\Type\Traits;

use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Throwable;
use function array_map;

/**
 * @property UserGroupResource $userGroupResource
 */
trait UserGroupChoices
{
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
         * Lambda function to iterate all user groups and to create necessary
         * choices array.
         */
        $iterator = static function (UserGroup $userGroup) use (&$choices): void {
            $name = $userGroup->getName() . ' [' . $userGroup->getRole()->getId() . ']';

            $choices[$name] = $userGroup->getId();
        };

        array_map($iterator, $this->userGroupResource->find());

        return $choices;
    }
}
