<?php
declare(strict_types = 1);
/**
 * /src/Rest/RestResource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use App\DTO\RestDtoInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Rest\Interfaces\RestResourceInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnexpectedValueException;
use function array_keys;
use function sprintf;

/**
 * Class RestResource
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestResource implements RestResourceInterface
{
    use Traits\RestResourceBaseMethods;

    private BaseRepositoryInterface $repository;
    private ValidatorInterface $validator;
    private string $dtoClass = '';

    public function getSerializerContext(): array
    {
        return [];
    }

    public function getRepository(): BaseRepositoryInterface
    {
        return $this->repository;
    }

    public function setRepository(BaseRepositoryInterface $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    public function setValidator(ValidatorInterface $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    public function getDtoClass(): string
    {
        if ($this->dtoClass === '') {
            $message = sprintf(
                'DTO class not specified for \'%s\' resource',
                static::class
            );

            throw new UnexpectedValueException($message);
        }

        return $this->dtoClass;
    }

    public function setDtoClass(string $dtoClass): RestResourceInterface
    {
        $this->dtoClass = $dtoClass;

        return $this;
    }

    public function getEntityName(): string
    {
        return $this->getRepository()->getEntityName();
    }

    /**
     * {@inheritdoc}
     */
    public function getReference(string $id)
    {
        return $this->getRepository()->getReference($id);
    }

    public function getAssociations(): array
    {
        return array_keys($this->getRepository()->getAssociations());
    }

    public function getDtoForEntity(
        string $id,
        string $dtoClass,
        RestDtoInterface $dto,
        ?bool $patch = null
    ): RestDtoInterface {
        $patch ??= false;

        // Fetch entity
        $entity = $this->getEntity($id);

        // Create new instance of DTO and load entity to that.
        /** @var RestDtoInterface $restDto */
        /** @var class-string<RestDtoInterface> $dtoClass */
        $restDto = (new $dtoClass())
            ->setId($id);

        if ($patch === true) {
            $restDto->load($entity);
        }

        $restDto->patch($dto);

        return $restDto;
    }
}
