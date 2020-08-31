<?php
declare(strict_types = 1);
/**
 * /src/Command/User/UserHelper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\User;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\User as UserEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use Closure;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function array_map;
use function sprintf;

/**
 * Class UserHelper
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserHelper
{
    private UserResource $userResource;
    private UserGroupResource $userGroupResource;

    /**
     * UserHelper constructor.
     */
    public function __construct(UserResource $userResource, UserGroupResource $userGroupResource)
    {
        $this->userResource = $userResource;
        $this->userGroupResource = $userGroupResource;
    }

    /**
     * Method to get user entity. Also note that this may return a null in
     * cases that user do not want to make any changes to users.
     *
     * @throws Throwable
     */
    public function getUser(SymfonyStyle $io, string $question): ?UserEntity
    {
        $userFound = false;
        $userEntity = null;

        while ($userFound !== true) {
            /** @var UserEntity|null $userEntity */
            $userEntity = $this->getUserEntity($io, $question);

            if ($userEntity === null) {
                break;
            }

            $userFound = $this->isCorrectUser($io, $userEntity);
        }

        return $userEntity ?? null;
    }

    /**
     * Method to get user group entity. Also note that this may return a null
     * in cases that user do not want to make any changes to user groups.
     *
     * @throws Throwable
     */
    public function getUserGroup(SymfonyStyle $io, string $question): ?UserGroupEntity
    {
        $userGroupFound = false;
        $userGroupEntity = null;

        while ($userGroupFound !== true) {
            /** @var UserGroupEntity|null $userGroupEntity */
            $userGroupEntity = $this->getUserGroupEntity($io, $question);

            if ($userGroupEntity === null) {
                break;
            }

            $userGroupFound = $this->isCorrectUserGroup($io, $userGroupEntity);
        }

        return $userGroupEntity ?? null;
    }

    /**
     * Method to get User entity. Within this user will be asked which User
     * entity he/she wants to process with.
     *
     * @return UserEntity|EntityInterface|null
     *
     * @throws Throwable
     */
    private function getUserEntity(SymfonyStyle $io, string $question): ?EntityInterface
    {
        $choices = [];
        $iterator = $this->getUserIterator($choices);

        array_map($iterator, $this->userResource->find([], ['username' => 'asc']));

        $choices['Exit'] = 'Exit command';

        return $this->userResource->findOne((string)$io->choice($question, $choices));
    }

    /**
     * Method to get UserGroup entity. Within this user will be asked which
     * UserGroup entity he/she wants to process with.
     *
     * @return UserGroupEntity|EntityInterface|null
     *
     * @throws Throwable
     */
    private function getUserGroupEntity(SymfonyStyle $io, string $question): ?EntityInterface
    {
        $choices = [];
        $iterator = $this->getUserGroupIterator($choices);

        array_map($iterator, $this->userGroupResource->find([], ['name' => 'asc']));

        $choices['Exit'] = 'Exit command';

        return $this->userGroupResource->findOne((string)$io->choice($question, $choices));
    }

    /**
     * Getter method for user formatter closure. This closure will format
     * single User entity for choice list.
     *
     * @param array<int, string> $choices
     */
    private function getUserIterator(array &$choices): Closure
    {
        return static function (UserEntity $user) use (&$choices): void {
            $message = sprintf(
                '%s (%s %s <%s>)',
                $user->getUsername(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail()
            );

            $choices[$user->getId()] = $message;
        };
    }

    /**
     * Getter method for user group formatter closure. This closure will format
     * single UserGroup entity for choice list.
     *
     * @param mixed[] $choices
     */
    private function getUserGroupIterator(array &$choices): Closure
    {
        return static function (UserGroupEntity $userGroup) use (&$choices): void {
            $choices[$userGroup->getId()] = sprintf('%s (%s)', $userGroup->getName(), $userGroup->getRole()->getId());
        };
    }

    /**
     * Helper method to confirm user that he/she has chosen correct User
     * entity to process with.
     */
    private function isCorrectUser(SymfonyStyle $io, UserEntity $userEntity): bool
    {
        $message = sprintf(
            'Is this the correct  user [%s - %s (%s %s <%s>)]?',
            $userEntity->getId(),
            $userEntity->getUsername(),
            $userEntity->getFirstName(),
            $userEntity->getLastName(),
            $userEntity->getEmail()
        );

        return (bool)$io->confirm($message, false);
    }

    /**
     * Helper method to confirm user that he/she has chosen correct UserGroup
     * entity to process with.
     */
    private function isCorrectUserGroup(SymfonyStyle $io, UserGroupEntity $userGroupEntity): bool
    {
        $message = sprintf(
            'Is this the correct user group [%s - %s (%s)]?',
            $userGroupEntity->getId(),
            $userGroupEntity->getName(),
            $userGroupEntity->getRole()->getId()
        );

        return (bool)$io->confirm($message, false);
    }
}
