<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/RestRequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\AutoMapper;

use App\DTO\RestDtoInterface;
use AutoMapperPlus\CustomMapper\CustomMapper;
use InvalidArgumentException;
use LengthException;
use Symfony\Component\HttpFoundation\Request;
use function count;
use function get_class;
use function sprintf;

/**
 * Class RestRequestMapper
 *
 * @package App\AutoMapper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestRequestMapper extends CustomMapper
{
    /**
     * Properties to map to destination object.
     *
     * @var string[]
     */
    protected static $properties = [];

    /**
     * @inheritdoc
     *
     * @param Request|object          $source
     * @param RestDtoInterface|object $destination
     */
    public function mapToObject($source, $destination)
    {
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

    /**
     * @param Request          $request
     * @param RestDtoInterface $restDto
     *
     * @return RestDtoInterface
     */
    private function getObject(Request $request, RestDtoInterface $restDto): RestDtoInterface
    {
        foreach (static::$properties as $property) {
            if ($request->request->has($property)) {
                $setter = 'set' . ucfirst($property);
                $transformer = 'transform' . ucfirst($property);

                $value = $request->request->get($property);

                if (method_exists($this, $transformer)) {
                    $value = $this->{$transformer}($value);
                }

                $restDto->{$setter}($value);
            }
        }

        return $restDto;
    }
}
