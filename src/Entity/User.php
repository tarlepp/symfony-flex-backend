<?php
declare(strict_types = 1);
/**
 * /src/Entity/User.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Doctrine\DBAL\Types\Types as AppTypes;
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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as AssertCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\Entity]
#[ORM\Table(
    name: 'user',
)]
#[ORM\UniqueConstraint(
    name: 'uq_username',
    columns: [
        'username',
    ],
)]
#[ORM\UniqueConstraint(
    name: 'uq_email',
    columns: [
        'email',
    ],
)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[AssertCollection\UniqueEntity('email')]
#[AssertCollection\UniqueEntity('username')]
class User implements EntityInterface, UserInterface, UserGroupAwareInterface
{
    use Blameable;
    use Timestampable;
    use UserRelations;
    use Uuid;

    public const SET_USER_PROFILE = 'set.UserProfile';
    public const SET_USER_BASIC = 'set.UserBasic';

    /**
     * @OA\Property(type="string", format="uuid")
     */
    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.id',

        'LogLogin.user',
        'LogLoginFailure.user',
        'LogRequest.user',

        'UserGroup.users',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'username',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.username',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 2,
        max: 255,
    )]
    private string $username = '';

    #[ORM\Column(
        name: 'first_name',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.firstName',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 2,
        max: 255,
    )]
    private string $firstName = '';

    #[ORM\Column(
        name: 'last_name',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.lastName',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 2,
        max: 255,
    )]
    private string $lastName = '';

    #[ORM\Column(
        name: 'email',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.email',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(
        name: 'language',
        type: AppTypes::ENUM_LANGUAGE,
        nullable: false,
        options: [
            'comment' => 'User language for translations',
        ],
    )]
    #[Groups([
        'User',
        'User.language',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[AppAssert\Language]
    private string $language = Localization::DEFAULT_LANGUAGE;

    #[ORM\Column(
        name: 'locale',
        type: AppTypes::ENUM_LOCALE,
        nullable: false,
        options: [
            'comment' => 'User locale for number, time, date, etc. formatting.',
        ],
    )]
    #[Groups([
        'User',
        'User.locale',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[AppAssert\Locale]
    private string $locale = Localization::DEFAULT_LOCALE;

    #[ORM\Column(
        name: 'timezone',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'User timezone which should be used to display time, date, etc.',
            'default' => Localization::DEFAULT_TIMEZONE,
        ],
    )]
    #[Groups([
        'User',
        'User.timezone',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[AppAssert\Timezone]
    private string $timezone = Localization::DEFAULT_TIMEZONE;

    #[ORM\Column(
        name: 'password',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Hashed password',
        ],
    )]
    private string $password = '';

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @see UserEntityEventListener
     */
    private string $plainPassword = '';

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
