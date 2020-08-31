<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/User/User.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DTO\User;

use App\DTO\RestDto;
use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Interfaces\UserGroupAwareInterface;
use App\Entity\User as Entity;
use App\Entity\UserGroup as UserGroupEntity;
use App\Service\Localization;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use function array_map;

/**
 * Class User
 *
 * @AppAssert\UniqueEmail()
 * @AppAssert\UniqueUsername()
 *
 * @package App\DTO\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class User extends RestDto
{
    /**
     * @var array<string, string>
     */
    protected static array $mappings = [
        'password' => 'updatePassword',
        'userGroups' => 'updateUserGroups',
    ];

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      allowEmptyString="false",
     *  )
     */
    protected string $username = '';

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      allowEmptyString="false",
     *  )
     */
    protected string $firstName = '';

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      allowEmptyString="false",
     *  )
     */
    protected string $lastName = '';

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Email()
     */
    protected string $email = '';

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @AppAssert\Language()
     */
    protected string $language = Localization::DEFAULT_LANGUAGE;

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @AppAssert\Locale()
     */
    protected string $locale = Localization::DEFAULT_LOCALE;

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @AppAssert\Timezone()
     */
    protected string $timezone = Localization::DEFAULT_TIMEZONE;

    /**
     * @var UserGroupEntity[]|array<int, UserGroupEntity>
     *
     * @AppAssert\EntityReferenceExists(entityClass=UserGroupEntity::class)
     */
    protected array $userGroups = [];

    /**
     * @Assert\Length(
     *      min = 8,
     *      max = 255,
     *      allowEmptyString="false",
     *  )
     */
    protected string $password = '';

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->setVisited('username');

        $this->username = $username;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->setVisited('firstName');

        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->setVisited('lastName');

        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->setVisited('email');

        $this->email = $email;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->setVisited('language');

        $this->language = $language;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->setVisited('locale');

        $this->locale = $locale;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->setVisited('timezone');

        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return array<int, UserGroupEntity>
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param array<int, UserGroupEntity> $userGroups
     */
    public function setUserGroups(array $userGroups): self
    {
        $this->setVisited('userGroups');

        $this->userGroups = $userGroups;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

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
     * @param EntityInterface|Entity $entity
     *
     * @return RestDtoInterface|User
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        if ($entity instanceof Entity) {
            $this->id = $entity->getId();
            $this->username = $entity->getUsername();
            $this->firstName = $entity->getFirstName();
            $this->lastName = $entity->getLastName();
            $this->email = $entity->getEmail();
            $this->language = $entity->getLanguage();
            $this->locale = $entity->getLocale();
            $this->timezone = $entity->getTimezone();

            /** @var array<int, UserGroupEntity> $groups */
            $groups = $entity->getUserGroups()->toArray();

            $this->userGroups = $groups;
        }

        return $this;
    }

    /**
     * Method to update User entity password.
     */
    protected function updatePassword(Entity $entity, string $value): self
    {
        $entity->setPlainPassword($value);

        return $this;
    }

    /**
     * Method to update User entity user groups.
     *
     * @param array<int, UserGroupEntity> $value
     */
    protected function updateUserGroups(UserGroupAwareInterface $entity, array $value): void
    {
        $entity->clearUserGroups();

        array_map([$entity, 'addUserGroup'], $value);
    }
}
