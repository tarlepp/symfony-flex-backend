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
use App\Enum\Role as RoleEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as AssertCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\ByteString;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;
use function array_map;
use function array_unique;
use function array_values;

/**
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\Entity]
#[ORM\Table(
    name: 'api_key',
)]
#[ORM\UniqueConstraint(
    name: 'uq_token',
    columns: [
        'token',
    ],
)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[AssertCollection\UniqueEntity('token')]
class ApiKey implements EntityInterface, UserGroupAwareInterface
{
    use Blameable;
    use Timestampable;
    use Uuid;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
    )]
    #[Groups([
        'ApiKey',
        'ApiKey.id',

        'LogRequest.apiKey',
    ])]
    #[OA\Property(type: 'string', format: 'uuid')]
    private UuidInterface $id;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(
        name: 'token',
        type: Types::STRING,
        length: 40,
        options: [
            'comment' => 'Generated API key string for authentication',
        ],
    )]
    #[Groups([
        'ApiKey',
        'ApiKey.token',
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        exactly: 40,
    )]
    private string $token;

    #[ORM\Column(
        name: 'description',
        type: Types::TEXT,
    )]
    #[Groups([
        'ApiKey',
        'ApiKey.description',
    ])]
    #[Assert\NotNull]
    private string $description = '';

    /**
     * @var Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    #[ORM\JoinTable(
        name: 'api_key_has_user_group',
    )]
    #[ORM\ManyToMany(
        targetEntity: UserGroup::class,
        inversedBy: 'apiKeys',
    )]
    #[Groups([
        'ApiKey.userGroups',
    ])]
    private Collection | ArrayCollection $userGroups;

    /**
     * @var Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     */
    #[ORM\OneToMany(
        mappedBy: 'apiKey',
        targetEntity: LogRequest::class,
    )]
    #[Groups([
        'ApiKey.logsRequest',
    ])]
    private Collection | ArrayCollection $logsRequest;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
        $this->token = $this->generateToken()->getToken();
        $this->userGroups = new ArrayCollection();
        $this->logsRequest = new ArrayCollection();
    }

    #[Override]
    public function getId(): string
    {
        return $this->id->toString();
    }

    /**
     * @return non-empty-string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param non-empty-string $token
     */
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
        /** @var non-empty-string $token */
        $token = ByteString::fromRandom(40)->toString();

        return $this->setToken($token);
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
    #[Override]
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
                    [
                        RoleEnum::API->value,
                        ...$this->userGroups
                            ->map(static fn (UserGroup $userGroup): string => $userGroup->getRole()->getId())
                            ->toArray(),
                    ],
                ),
            ),
        );
    }

    #[Override]
    public function addUserGroup(UserGroup $userGroup): self
    {
        if ($this->userGroups->contains($userGroup) === false) {
            $this->userGroups->add($userGroup);
            $userGroup->addApiKey($this);
        }

        return $this;
    }

    #[Override]
    public function removeUserGroup(UserGroup $userGroup): self
    {
        if ($this->userGroups->removeElement($userGroup)) {
            $userGroup->removeApiKey($this);
        }

        return $this;
    }

    #[Override]
    public function clearUserGroups(): self
    {
        $this->userGroups->clear();

        return $this;
    }
}
