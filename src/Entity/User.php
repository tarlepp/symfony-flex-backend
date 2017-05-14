<?php
declare(strict_types=1);
/**
 * /src/Entity/User.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use App\Utils\JSON;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * @ORM\Table(
 *      name="user",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="uq_username", columns={"username"}),
 *          @ORM\UniqueConstraint(name="uq_email", columns={"email"}),
 *      },
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Repository\UserRepository"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class User implements UserInterface, EquatableInterface, \Serializable, Interfaces\EntityInterface
{
    /**
     * @var string
     *
     * @Groups({
     *      "User",
     *      "User.id",
     *      "UserGroup.users",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="guid",
     *      nullable=false
     *  )
     * @ORM\Id()
     */
    private $id = '';

    /**
     * @var string
     *
     * @Groups({
     *      "User",
     *      "User.username",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     *
     * @ORM\Column(
     *      name="username",
     *      type="string",
     *      length=255,
     *      nullable=false
     *  )
     */
    private $username = '';

    /**
     * @var string
     *
     * @Groups({
     *      "User",
     *      "User.firstname",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     *
     * @ORM\Column(
     *      name="firstname",
     *      type="string",
     *      length=255,
     *      nullable=false
     *  )
     */
    private $firstname = '';

    /**
     * @var string
     *
     * @Groups({
     *      "User",
     *      "User.surname",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     *
     * @ORM\Column(
     *      name="surname",
     *      type="string",
     *      length=255,
     *      nullable=false
     *  )
     */
    private $surname = '';

    /**
     * @var string
     *
     * @Groups({
     *      "User",
     *      "User.email",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Email()
     *
     * @ORM\Column(
     *      name="email",
     *      type="string",
     *      length=255,
     *      nullable=false
     *  )
     */
    private $email = '';

    /**
     * @var string
     *
     * @ORM\Column(
     *      name="password",
     *      type="string",
     *      length=255,
     *      nullable=false
     *  )
     */
    private $password = '';

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var  string
     */
    private $plainPassword = '';

    /**
     * @var Collection<UserGroup>
     *
     * @Groups({
     *      "User.userGroups",
     *  })
     *
     * @ORM\ManyToMany(
     *      targetEntity="UserGroup",
     *      inversedBy="users",
     *      cascade={"all"},
     *  )
     */
    private $userGroups;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();

        $this->userGroups = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
    public function setUsername(string $username): User
    {
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
        $this->email = $email;

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
     * @param callable  $encoder
     * @param string    $plainPassword
     *
     * @return User
     */
    public function setPassword(callable $encoder, string $plainPassword): User
    {
        $this->password = $encoder($plainPassword);

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
     * @param string $plainPassword
     *
     * @return User
     */
    public function setPlainPassword(string $plainPassword): User
    {
        if (!empty($plainPassword)) {
            $this->plainPassword = $plainPassword;

            // Change some mapped values so preUpdate will get called.
            $this->password = ''; // just blank it out
        }

        return $this;
    }

    /**
     * Getter for roles.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        /**
         * Lambda iterator to get user group role information.
         *
         * @param   UserGroup   $userGroup
         *
         * @return  string
         */
        $iterator = function (UserGroup $userGroup) {
            return $userGroup->getRole();
        };

        return \array_map($iterator, $this->userGroups->toArray());
    }

    /**
     * Getter for user groups collection.
     *
     * @return Collection<UserGroup>|ArrayCollection<UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    /**
     * String representation of object
     *
     * @return string the string representation of the object
     *
     * @throws \LogicException
     */
    public function serialize(): string
    {
        return JSON::encode([
            'id'        => $this->id,
            'username'  => $this->username,
            'password'  => $this->password
        ]);
    }

    /**
     * Constructs the object
     *
     * @param string $serialized The string representation of the object.
     *
     * @throws \LogicException
     */
    public function unserialize($serialized): void
    {
        $data = JSON::decode($serialized);

        $this->id = $data->id;
        $this->username = $data->username;
        $this->password = $data->password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * Method to get login data for JWT token.
     *
     * @return array
     */
    public function getLoginData(): array
    {
        return [
            'firstname' => $this->getFirstname(),
            'surname'   => $this->getSurname(),
            'email'     => $this->getEmail()
        ];
    }

    /**
     * Method to get user checksum string.
     *
     * @return string
     */
    public function getChecksum(): string
    {
        $bits = [
            $this->getId(),
            $this->getPassword()
        ];

        return \hash('sha512', \implode('', $bits));
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = '';
    }

    /**
     * Method to attach new user group to user.
     *
     * @param UserGroup $userGroup
     *
     * @return User
     */
    public function addUserGroup(UserGroup $userGroup): User
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);
            $userGroup->addUser($this);
        }

        return $this;
    }

    /**
     * Method to remove specified user group from user.
     *
     * @param UserGroup $userGroup
     *
     * @return User
     */
    public function removeUserGroup(UserGroup $userGroup): User
    {
        if ($this->userGroups->contains($userGroup)) {
            $this->userGroups->removeElement($userGroup);
            $userGroup->removeUser($this);
        }

        return $this;
    }

    /**
     * Method to remove all many-to-many user group relations from current user.
     *
     * @return User
     */
    public function clearUserGroups(): User
    {
        $this->userGroups->clear();

        return $this;
    }

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * Also implementation should consider that $user instance may implement
     * the extended user interface `AdvancedUserInterface`.
     *
     * @param UserInterface|User $user
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
    {
        return $user->getId() === $this->getId();
    }
}
