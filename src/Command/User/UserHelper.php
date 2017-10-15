<?php
declare(strict_types=1);
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
     * ApiKeyHelper constructor.
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
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return UserEntity
     */
    public function getUser(SymfonyStyle $io, string $question): ?UserEntity
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

    /**
     * @param SymfonyStyle $io
     * @param string       $question
     *
     * @return UserGroupEntity
     */
    public function getUserGroup(SymfonyStyle $io, string $question): UserGroupEntity
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

        return $this->userGroupResource->findOne($io->choice($question, $choices));
    }
}
