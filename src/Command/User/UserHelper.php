<?php
declare(strict_types = 1);
/**
 * /src/Command/User/UserHelper.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Entity\User as UserEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class UserHelper
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserHelper
{
    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * UserHelper constructor.
     *
     * @param UserResource      $userResource
     * @param UserGroupResource $userGroupResource
     */
    public function __construct(UserResource $userResource, UserGroupResource $userGroupResource)
    {
        $this->userResource = $userResource;
        $this->userGroupResource = $userGroupResource;
    }

    /**
     * Method to get user entity. Also note that this may return a null in cases that user do not want to make any
     * changes to users.
     *
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return UserEntity|null
     */
    public function getUser(SymfonyStyle $io, string $question): ?UserEntity
    {
        $userFound = false;

        while ($userFound === false) {
            $userEntity = $this->getUserEntity($io, $question);

            if ($userEntity === null) {
                break;
            }

            $message = \sprintf(
                'Is this the correct  user [%s - %s (%s %s <%s>)]?',
                $userEntity->getId(),
                $userEntity->getUsername(),
                $userEntity->getFirstname(),
                $userEntity->getSurname(),
                $userEntity->getEmail()
            );

            $userFound = $io->confirm($message, false);
        }

        return $userEntity ?? null;
    }

    /**
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return UserGroupEntity|null
     */
    public function getUserGroup(SymfonyStyle $io, string $question): ?UserGroupEntity
    {
        $choices = [];

        /**
         * Lambda function create user group choices
         *
         * @param UserGroupEntity $userGroup
         */
        $iterator = function (UserGroupEntity $userGroup) use (&$choices): void {
            $choices[$userGroup->getId()] = \sprintf('%s (%s)', $userGroup->getName(), $userGroup->getRole()->getId());
        };

        \array_map($iterator, $this->userGroupResource->find([], ['name' => 'asc']));

        $choices['Exit'] = 'Exit command';

        return $this->userGroupResource->findOne($io->choice($question, $choices));
    }

    /**
     * Method to get user entity
     *
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return UserEntity|null
     */
    private function getUserEntity(SymfonyStyle $io, string $question): ?UserEntity
    {
        $choices = [];

        /**
         * Lambda function create user choices
         *
         * @param UserEntity $user
         */
        $iterator = function (UserEntity $user) use (&$choices): void {
            $message = \sprintf(
                '%s (%s %s <%s>)',
                $user->getUsername(),
                $user->getFirstname(),
                $user->getSurname(),
                $user->getEmail()
            );

            $choices[$user->getId()] = $message;
        };

        \array_map($iterator, $this->userResource->find([], ['username' => 'asc']));

        $choices['Exit'] = 'Exit command';

        return $this->userResource->findOne($io->choice($question, $choices));
    }
}
