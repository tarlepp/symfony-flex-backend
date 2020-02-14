<?php
declare(strict_types = 1);
/**
 * /src/Entity/User.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Interfaces\UserGroupAwareInterface;
use App\Entity\Interfaces\UserInterface;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\UserRelations;
use App\Entity\Traits\Uuid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
use Symfony\Bridge\Doctrine\Validator\Constraints as AssertCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

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
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class User implements EntityInterface, UserInterface, UserGroupAwareInterface
{
    // Traits
    use Blameable;
    use Timestampable;
    use UserRelations;
    use Uuid;

    /**
     * @var UuidInterface
     *
     * @Groups({
     *      "User",
     *      "User.id",
     *      "UserGroup.users",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     *
     * @SWG\Property(type="string", format="uuid")
     */
    private UuidInterface $id;

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
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      allowEmptyString="false",
     *  )
     *
     * @ORM\Column(
     *      name="username",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private string $username = '';

    /**
     * @var string
     *
     * @Groups({
     *      "User",
     *      "User.firstName",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      allowEmptyString="false",
     *  )
     *
     * @ORM\Column(
     *      name="first_name",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private string $firstName = '';

    /**
     * @var string
     *
     * @Groups({
     *      "User",
     *      "User.lastName",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      allowEmptyString="false",
     *  )
     *
     * @ORM\Column(
     *      name="last_name",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private string $lastName = '';

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
    private string $email = '';

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
    private string $password = '';

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var  string
     */
    private string $plainPassword = '';

    /**
     * User constructor.
     *
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->getUuid();

        $this->userGroups = new ArrayCollection();
        $this->logsRequest = new ArrayCollection();
        $this->logsLogin = new ArrayCollection();
        $this->logsLoginFailure = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id->toString();
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
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

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
    public function setPassword(callable $encoder, string $plainPassword): self
    {
        $this->password = (string)$encoder($plainPassword);

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
    public function setPlainPassword(string $plainPassword): self
    {
        if ($plainPassword !== '') {
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
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = '';
    }
}
