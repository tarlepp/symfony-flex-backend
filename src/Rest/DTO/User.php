<?php
declare(strict_types=1);
/**
 * /src/Rest/DTO/User.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\DTO;

use App\Entity\User as UserEntity;
use App\Entity\EntityInterface;
use App\Entity\UserGroup as UserGroupEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * @package App\Rest\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class User extends RestDto
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    private $username = '';

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    private $firstname = '';

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    private $surname = '';

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Email()
     */
    private $email = '';

    /**
     * @var array
     */
    private $userGroups = [];

    /**
     * @var string
     */
    private $plainPassword = '';

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return User
     */
    public function setUsername(string $username): User
    {
        $this->setVisited('username');

        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     *
     * @return User
     */
    public function setFirstname(string $firstname): User
    {
        $this->setVisited('firstname');

        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @return string
     */
    public function getSurname(): string
    {
        return $this->surname;
    }

    /**
     * @param string $surname
     *
     * @return User
     */
    public function setSurname(string $surname): User
    {
        $this->setVisited('surname');

        $this->surname = $surname;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->setVisited('email');

        $this->email = $email;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param array $userGroups
     *
     * @return User
     */
    public function setUserGroups(array $userGroups): User
    {
        $this->setVisited('userGroups');

        $this->userGroups = $userGroups;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     *
     * @return User
     */
    public function setPlainPassword(string $plainPassword = null): User
    {
        $this->setVisited('plainPassword');

        $this->plainPassword = $plainPassword ?? '';

        return $this;
    }

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface|UserEntity $entity
     *
     * @return RestDtoInterface|User
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        /**
         * Lambda function to extract user group id
         *
         * @param UserGroupEntity $group
         *
         * @return string
         */
        $iterator = function (UserGroupEntity $group) {
            return $group->getId();
        };

        $this->username = $entity->getUsername();
        $this->firstname = $entity->getFirstname();
        $this->surname = $entity->getSurname();
        $this->email = $entity->getEmail();
        $this->userGroups = \array_map($iterator, $entity->getUserGroups()->toArray());

        return $this;
    }

    /**
     * Method to update specified entity with DTO data.
     *
     * @param EntityInterface|UserEntity $entity
     *
     * @return EntityInterface|UserEntity
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        $entity->setUsername($this->username);
        $entity->setFirstname($this->firstname);
        $entity->setSurname($this->surname);
        $entity->setEmail($this->email);
        $entity->setPlainPassword($this->plainPassword);

        // Clear user groups
        $entity->clearUserGroups();

        // And attach user groups to entity
        foreach ($this->userGroups as $userGroup) {
            $entity->addUserGroup($userGroup);
        }

        return $entity;
    }
}
