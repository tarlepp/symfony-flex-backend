<?php
declare(strict_types=1);
/**
 * /src/Rest/DTO/User.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\DTO;

use App\Entity\User as UserEntity;
use App\Entity\Interfaces\EntityInterface;
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
    private $username;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    private $firstname;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(min = 2, max = 255)
     */
    private $surname;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Email()
     */
    private $email;

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
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface|UserEntity $entity
     *
     * @return RestDtoInterface
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        $this->username = $entity->getUsername();
        $this->firstname = $entity->getFirstname();
        $this->surname = $entity->getSurname();
        $this->email = $entity->getEmail();

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

        return $entity;
    }
}
