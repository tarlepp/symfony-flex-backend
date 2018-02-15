<?php
declare(strict_types = 1);
/**
 * /src/Entity/User.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\UserRelations;
use App\Entity\Traits\UserSerializer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints as AssertCollection;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as CoreUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * @AssertCollection\UniqueEntity("email")
 * @AssertCollection\UniqueEntity("username")
 *
 * @ORM\Table(
 *      name="user",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="uq_username", columns={"username"}),
 *          @ORM\UniqueConstraint(name="uq_email", columns={"email"}),
 *      },
 *  )
 * @ORM\Entity(
 *      repositoryClass="App\Security\UserProvider"
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class User implements CoreUserInterface, EquatableInterface, \Serializable, EntityInterface, UserInterface
{
    // Traits
    use Blameable;
    use Timestampable;
    use UserRelations;
    use UserSerializer;

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
    private $id;

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
     * User constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();

        $this->userGroups = new ArrayCollection();
        $this->logsRequest = new ArrayCollection();
        $this->logsLogin = new ArrayCollection();
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
     * @return mixed[]
     */
    public function getLoginData(): array
    {
        return [
            'id'        => $this->getId(),
            'firstname' => $this->getFirstname(),
            'surname'   => $this->getSurname(),
            'email'     => $this->getEmail(),
            'roles'     => $this->getRoles(),
        ];
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
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * Also implementation should consider that $user instance may implement
     * the extended user interface `AdvancedUserInterface`.
     *
     * @param CoreUserInterface|User $user
     *
     * @return bool
     */
    public function isEqualTo(CoreUserInterface $user): bool
    {
        return $user instanceof self && $user->getId() === $this->getId();
    }
}
