<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/RestRequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\AutoMapper;

use App\DTO\RestDtoInterface;
use AutoMapperPlus\MapperInterface;
use InvalidArgumentException;
use LengthException;
use Override;
use ReflectionClass;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Request;
use function array_filter;
use function gettype;
use function is_object;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class RestRequestMapper implements MapperInterface
{
    /**
     * Properties to map to destination object.
     *
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [];

    /**
     * {@inheritdoc}
     *
     * @psalm-param array<array-key, mixed>|object $source
     * @psalm-param array<int, mixed> $context
     */
    #[Override]
    public function map($source, string $targetClass, array $context = []): RestDtoInterface
    {
        /** @psalm-var class-string<RestDtoInterface> $targetClass */
        $destination = new $targetClass();

        return $this->mapToObject($source, $destination, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param array<array-key, mixed>|object $source
     * @psalm-param object $destination
     * @psalm-param array<int, mixed> $context
     */
    #[Override]
    public function mapToObject($source, $destination, array $context = []): RestDtoInterface
    {
        if (!is_object($source)) {
            throw new InvalidArgumentException(
                sprintf(
                    'RestRequestMapper expects that $source is Request object, "%s" provided',
                    gettype($source),
                )
            );
        }

        if (!$source instanceof Request) {
            throw new InvalidArgumentException(
                sprintf(
                    'RestRequestMapper expects that $source is Request object, "%s" provided',
                    $source::class,
                )
            );
        }

        if (!$destination instanceof RestDtoInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'RestRequestMapper expects that $destination is instance of RestDtoInterface object, "%s" provided',
                    $destination::class,
                )
            );
        }

        if (static::$properties === []) {
            throw new LengthException(
                sprintf(
                    'RestRequestMapper expects that mapper "%s::$properties" contains properties to convert',
                    static::class,
                )
            );
        }

        return $this->getObject($source, $destination);
    }

    private function getObject(Request $request, RestDtoInterface $restDto): RestDtoInterface
    {
        $reflectionClass = new ReflectionClass($restDto::class);

        foreach ($this->getValidProperties($request) as $property) {
            $setter = 'set' . ucfirst($property);
            $transformer = 'transform' . ucfirst($property);
            $type = $reflectionClass->getProperty($property)->getType();

            $value = $type instanceof ReflectionNamedType && $type->getName() === 'array'
                ? $request->request->all($property)
                : $request->request->get($property);

            if (method_exists($this, $transformer)) {
                /** @var int|string|object|array<mixed>|null $value */
                $value = $this->{$transformer}($value);
            }

            $restDto->{$setter}($value);
        }

        return $restDto;
    }

    /**
     * @return array<int, non-empty-string>
     */
    private function getValidProperties(Request $request): array
    {
        return array_filter(static::$properties, static fn ($property) => $request->request->has($property));
    }
}
