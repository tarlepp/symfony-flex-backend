<?php
declare(strict_types = 1);
/**
 * /src/Entity/DateDimension.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Interfaces\UserGroupAwareInterface;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\Uuid;
use App\Security\RolesService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
use Symfony\Bridge\Doctrine\Validator\Constraints as AssertCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function mb_strlen;
use function random_int;

/**
 * Class ApiKey
 *
 * @AssertCollection\UniqueEntity("token")
 *
 * @ORM\Table(
 *      name="api_key",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="uq_token", columns={"token"}),
 *      },
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKey implements EntityInterface, UserGroupAwareInterface
{
    // Traits
    use Blameable;
    use Timestampable;
    use Uuid;

    /**
     * @var UuidInterface
     *
     * @Groups({
     *      "ApiKey",
     *      "ApiKey.id",
     *
     *      "LogRequest.apiKey"
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
     *      "ApiKey",
     *      "ApiKey.token",
     *  })
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(
     *      min = 40,
     *      max = 40,
     *      allowEmptyString="false",
     *  )
     *
     * @ORM\Column(
     *      name="token",
     *      type="string",
     *      length=40,
     *      nullable=false
     *  )
     */
    private string $token = '';

    /**
     * @var string
     *
     * @Groups({
     *      "ApiKey",
     *      "ApiKey.description",
     *  })
     *
     * @ORM\Column(
     *      name="description",
     *      type="text",
     *  )
     */
    private string $description = '';

    /**
     * @var Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     *
     * @Groups({
     *      "ApiKey.userGroups",
     *  })
     *
     * @ORM\ManyToMany(
     *      targetEntity="UserGroup",
     *      inversedBy="apiKeys",
     *  )
     * @ORM\JoinTable(
     *      name="api_key_has_user_group"
     *  )
     */
    private Collection $userGroups;

    /**
     * @var Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     *
     * @Groups({
     *      "ApiKey.logsRequest",
     *  })
     *
     * @ORM\OneToMany(
     *      targetEntity="App\Entity\LogRequest",
     *      mappedBy="apiKey",
     *  )
     */
    private Collection $logsRequest;

    /**
     * ApiKey constructor.
     *
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
        $this->userGroups = new ArrayCollection();
        $this->logsRequest = new ArrayCollection();

        $this->generateToken();
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
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return ApiKey
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return ApiKey
     *
     * @throws Throwable
     */
    public function generateToken(): self
    {
        $random = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = mb_strlen($chars, '8bit') - 1;

        for ($i = 0; $i < 40; ++$i) {
            $random .= $chars[random_int(0, $max)];
        }

        return $this->setToken($random);
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ApiKey
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    /**
     * Getter for user request log collection.
     *
     * @return Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     */
    public function getLogsRequest(): Collection
    {
        return $this->logsRequest;
    }

    /**
     * Getter for roles.
     *
     * @Groups({
     *      "ApiKey.roles",
     *  })
     *
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return array_values(
            array_map(
                '\strval',
                array_unique(
                    array_merge(
                        [RolesService::ROLE_API],
                        $this->userGroups
                            ->map(static fn (UserGroup $userGroup): string => $userGroup->getRole()->getId())
                            ->toArray()
                    )
                )
            )
        );
    }

    /**
     * Method to attach new userGroup to current api key.
     *
     * @param UserGroup $userGroup
     *
     * @return ApiKey|UserGroupAwareInterface
     */
    public function addUserGroup(UserGroup $userGroup): UserGroupAwareInterface
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);
            $userGroup->addApiKey($this);
        }

        return $this;
    }

    /**
     * Method to remove specified userGroup from current api key.
     *
     * @param UserGroup $userGroup
     *
     * @return ApiKey|UserGroupAwareInterface
     */
    public function removeUserGroup(UserGroup $userGroup): UserGroupAwareInterface
    {
        if ($this->userGroups->removeElement($userGroup)) {
            $userGroup->removeApiKey($this);
        }

        return $this;
    }

    /**
     * Method to remove all many-to-many userGroup relations from current api key.
     *
     * @return ApiKey|UserGroupAwareInterface
     */
    public function clearUserGroups(): UserGroupAwareInterface
    {
        $this->userGroups->clear();

        return $this;
    }
}
