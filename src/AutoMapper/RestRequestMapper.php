<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/RestRequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper;

use App\DTO\RestDtoInterface;
use AutoMapperPlus\MapperInterface;
use InvalidArgumentException;
use LengthException;
use Symfony\Component\HttpFoundation\Request;
use function array_filter;
use function count;
use function get_class;
use function gettype;
use function is_object;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * Class RestRequestMapper
 *
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestRequestMapper implements MapperInterface
{
    /**
     * Properties to map to destination object.
     *
     * @var array<int, string>
     */
    protected static array $properties = [];

    /**
     * {@inheritdoc}
     *
     * @param array|object $source
     * @param array<int, mixed> $context
     */
    public function map($source, string $targetClass, array $context = []): RestDtoInterface
    {
        /** @var class-string $targetClass */
        $destination = new $targetClass();

        return $this->mapToObject($source, $destination, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @param array|object $source
     * @param object $destination
     * @param array<int, mixed> $context
     */
    public function mapToObject($source, $destination, array $context = []): RestDtoInterface
    {
        if (!is_object($source)) {
            throw new InvalidArgumentException(
                sprintf(
                    'RestRequestMapper expects that $source is Request object, "%s" provided',
                    gettype($source)
                )
            );
        }

        if (!$source instanceof Request) {
            throw new InvalidArgumentException(
                sprintf(
                    'RestRequestMapper expects that $source is Request object, "%s" provided',
                    get_class($source)
                )
            );
        }

        if (!$destination instanceof RestDtoInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'RestRequestMapper expects that $destination is instance of RestDtoInterface object, "%s" provided',
                    get_class($destination)
                )
            );
        }

        if (count(static::$properties) === 0) {
            throw new LengthException(
                sprintf(
                    'RestRequestMapper expects that mapper "%s::$properties" contains properties to convert',
                    static::class
                )
            );
        }

        return $this->getObject($source, $destination);
    }

    private function getObject(Request $request, RestDtoInterface $restDto): RestDtoInterface
    {
        foreach ($this->getValidProperties($request) as $property) {
            $setter = 'set' . ucfirst($property);
            $transformer = 'transform' . ucfirst($property);

            /** @var int|string|array|null $value */
            $value = $request->get($property);

            if (method_exists($this, $transformer)) {
                /** @var int|string|object|array|null $value */
                $value = $this->{$transformer}($value);
            }

            $restDto->{$setter}($value);
        }

        return $restDto;
    }

    /**
     * @return array<int, string>
     */
    private function getValidProperties(Request $request): array
    {
        return array_filter(static::$properties, static fn ($property) => $request->request->has($property));
    }
}
