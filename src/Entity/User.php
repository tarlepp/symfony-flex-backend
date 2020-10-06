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
use App\Service\Localization;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
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
 * @ORM\UniqueConstraint(name="uq_username", columns={"username"}),
 * @ORM\UniqueConstraint(name="uq_email", columns={"email"}),
 *      },
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class User implements EntityInterface, UserInterface, UserGroupAwareInterface
{
    use Blameable;
    use Timestampable;
    use UserRelations;
    use Uuid;

    /**
     * @Groups({
     *      "User",
     *      "User.id",
     *
     *      "LogLogin.user",
     *      "LogLoginFailure.user",
     *      "LogRequest.user",
     *
     *      "UserGroup.users",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
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
     * @OA\Property(type="string", format="uuid")
     */
    private UuidInterface $id;

    /**
     * @Groups({
     *      "User",
     *      "User.username",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
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
     * @Groups({
     *      "User",
     *      "User.firstName",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
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
     * @Groups({
     *      "User",
     *      "User.lastName",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
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
     * @Groups({
     *      "User",
     *      "User.email",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
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
     *      nullable=false,
     *  )
     */
    private string $email = '';

    /**
     * @Groups({
     *      "User",
     *      "User.language",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @AppAssert\Language()
     *
     * @ORM\Column(
     *      name="language",
     *      type="EnumLanguage",
     *      nullable=false,
     *      options={
     *          "comment": "User language for translations",
     *      }
     *  )
     */
    private string $language = Localization::DEFAULT_LANGUAGE;

    /**
     * @Groups({
     *      "User",
     *      "User.locale",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @AppAssert\Locale()
     *
     * @ORM\Column(
     *      name="locale",
     *      type="EnumLocale",
     *      nullable=false,
     *      options={
     *          "comment": "User locale for number, time, date, etc. formatting.",
     *      }
     *  )
     */
    private string $locale = Localization::DEFAULT_LOCALE;

    /**
     * * @Groups({
     *      "User",
     *      "User.timezone",
     *
     *      "set.UserProfile",
     *      "set.UserBasic",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @AppAssert\Timezone()
     *
     * @ORM\Column(
     *      name="timezone",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *      options={
     *          "comment": "User timezone which should be used to display time, date, etc.",
     *          "default": "Europe/Helsinki",
     *      },
     *  )
     */
    private string $timezone = Localization::DEFAULT_TIMEZONE;

    /**
     * @ORM\Column(
     *      name="password",
     *      type="string",
     *      length=255,
     *      nullable=false,
     *  )
     */
    private string $password = '';

    /**
     * Plain password. Used for model validation. Must not be persisted.
     */
    private string $plainPassword = '';

    /**
     * User constructor.
     *
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();

        $this->userGroups = new ArrayCollection();
        $this->logsRequest = new ArrayCollection();
        $this->logsLogin = new ArrayCollection();
        $this->logsLoginFailure = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(callable $encoder, string $plainPassword): self
    {
        $this->password = (string)$encoder($plainPassword);

        return $this;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        if ($plainPassword !== '') {
            $this->plainPassword = $plainPassword;

            // Change some mapped values so preUpdate will get called - just blank it out
            $this->password = '';
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
