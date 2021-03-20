<?php
declare(strict_types = 1);
/**
 * /src/Entity/ApiKey.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
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
 * @ORM\Table(
 *      name="api_key",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="uq_token", columns={"token"}),
 *      },
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AssertCollection\UniqueEntity('token')]
class ApiKey implements EntityInterface, UserGroupAwareInterface
{
    use Blameable;
    use Timestampable;
    use Uuid;

    /**
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
    #[Groups([
        'ApiKey',
        'ApiKey.id',

        'LogRequest.apiKey',
    ])]
    private UuidInterface $id;

    /**
     * @ORM\Column(
     *      name="token",
     *      type="string",
     *      length=40,
     *      nullable=false,
     *  )
     */
    #[Groups([
        'ApiKey',
        'ApiKey.token',
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(exactly: 40)]
    private string $token = '';

    /**
     * @ORM\Column(
     *      name="description",
     *      type="text",
     *  )
     */
    #[Groups([
        'ApiKey',
        'ApiKey.description',
    ])]
    #[Assert\NotNull]
    private string $description = '';

    /**
     * @var Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     *
     * @ORM\ManyToMany(
     *      targetEntity="UserGroup",
     *      inversedBy="apiKeys",
     *  )
     * @ORM\JoinTable(
     *      name="api_key_has_user_group",
     *  )
     */
    #[Groups([
        'ApiKey.userGroups',
    ])]
    private Collection | ArrayCollection $userGroups;

    /**
     * @var Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     *
     * @ORM\OneToMany(
     *      targetEntity="App\Entity\LogRequest",
     *      mappedBy="apiKey",
     *  )
     */
    #[Groups([
        'ApiKey.logsRequest',
    ])]
    private Collection | ArrayCollection $logsRequest;

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

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function generateToken(): self
    {
        $random = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = mb_strlen($chars, '8bit') - 1;

        for ($i = 0; $i < 40; $i++) {
            $random .= $chars[random_int(0, $max)];
        }

        return $this->setToken($random);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Getter method for user groups collection.
     *
     * @return Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection | ArrayCollection
    {
        return $this->userGroups;
    }

    /**
     * Getter method for user request log collection.
     *
     * @return Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     */
    public function getLogsRequest(): Collection | ArrayCollection
    {
        return $this->logsRequest;
    }

    /**
     * Getter for roles.
     *
     * @return array<int, string>
     */
    #[Groups([
        'ApiKey.roles',
    ])]
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
                            ->toArray(),
                    ),
                ),
            ),
        );
    }

    public function addUserGroup(UserGroup $userGroup): self
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);
            $userGroup->addApiKey($this);
        }

        return $this;
    }

    public function removeUserGroup(UserGroup $userGroup): self
    {
        if ($this->userGroups->removeElement($userGroup)) {
            $userGroup->removeApiKey($this);
        }

        return $this;
    }

    public function clearUserGroups(): self
    {
        $this->userGroups->clear();

        return $this;
    }
}
