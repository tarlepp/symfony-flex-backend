<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/User.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DTO;

use App\Entity\EntityInterface;
use App\Entity\User as UserEntity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Entity\UserInterface;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use function array_map;

/**
 * Class User
 *
 * @AppAssert\UniqueEmail()
 * @AppAssert\UniqueUsername()
 *
 * @package App\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class User extends RestDto implements UserInterface
{
    /**
     * @var mixed[]
     */
    protected static $mappings = [
        'password' => 'updatePassword',
        'userGroups' => 'updateUserGroups',
    ];

    /**
     * @var string|null
     */
    protected $id;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    protected $username = '';

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    protected $firstname = '';

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    protected $surname = '';

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Email()
     */
    protected $email = '';

    /**
     * @var string[]|UserGroupEntity[]
     */
    protected $userGroups = [];

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"Create"})
     * @Assert\NotNull(groups={"Create"})
     * @Assert\Length(groups={"Create"}, min = 2, max = 255)
     */
    protected $password = '';

    /**
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param null|string $id
     *
     * @return User
     */
    public function setId(?string $id = null): self
    {
        $this->setVisited('id');

        $this->id = $id;

        return $this;
    }

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
    public function setUsername(string $username): self
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
    public function setFirstname(string $firstname): self
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
    public function setSurname(string $surname): self
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
    public function setEmail(string $email): self
    {
        $this->setVisited('email');

        $this->email = $email;

        return $this;
    }

    /**
     * @return string[]|UserGroupEntity[]
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param string[]|UserGroupEntity[] $userGroups
     *
     * @return User
     */
    public function setUserGroups(array $userGroups): self
    {
        $this->setVisited('userGroups');

        $this->userGroups = $userGroups;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     *
     * @return User
     */
    public function setPassword(?string $password = null): self
    {
        if ($password !== null) {
            $this->setVisited('password');

            $this->password = $password;
        }

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
        $iterator = function (UserGroupEntity $group): string {
            return $group->getId();
        };

        $this->id = $entity->getId();
        $this->username = $entity->getUsername();
        $this->firstname = $entity->getFirstname();
        $this->surname = $entity->getSurname();
        $this->email = $entity->getEmail();
        $this->userGroups = $entity->getUserGroups()->map($iterator)->toArray();

        return $this;
    }

    /**
     * Method to update User entity password.
     *
     * @param UserEntity $entity
     * @param string     $value
     *
     * @return User
     */
    protected function updatePassword(UserEntity $entity, string $value): self
    {
        $entity->setPlainPassword($value);

        return $this;
    }

    /**
     * Method to update User entity user groups.
     *
     * @param UserEntity        $entity
     * @param UserGroupEntity[] $value
     *
     * @return User
     */
    protected function updateUserGroups(UserEntity $entity, array $value): self
    {
        $entity->clearUserGroups();

        array_map([$entity, 'addUserGroup'], $value);

        return $this;
    }
}
