<?php
declare(strict_types=1);
/**
 * /src/Rest/DTO/Base.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\DTO;

use App\Rest\DTO\Interfaces\RestDtoInterface;

/**
 * Class Base
 *
 * @package App\Rest\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class Base implements RestDtoInterface
{
    /**
     * An array of 'visited' setter properties of current dto.
     *
     * @var array
     */
    private $visited = [];

    /**
     * Getter method for visited setters. This is needed for dto patching.
     *
     * @return array
     */
    public function getVisited(): array
    {
        return $this->visited;
    }

    /**
     * Setter for visited data. This is needed for dto patching.
     *
     * @param string $property
     *
     * @return RestDtoInterface
     */
    public function setVisited(string $property): RestDtoInterface
    {
        $this->visited[] = $property;

        return $this;
    }

    /**
     * Method to patch current dto with another one.
     *
     * @param RestDtoInterface $dto
     *
     * @return RestDtoInterface
     *
     * @throws \BadMethodCallException
     */
    public function patch(RestDtoInterface $dto): RestDtoInterface
    {
        foreach ($dto->getVisited() as $property) {
            $getters = [
                'get' . \ucfirst($property),
                'is' . \ucfirst($property),
                'has' . \ucfirst($property),
            ];

            $filter = function (string $method) use ($dto): bool {
                return \method_exists($dto, $method);
            };

            // Determine getter method
            $getter = \current(\array_filter($getters, $filter));

            // Oh noes - required getter method does not exist
            if ($getter === false) {
                $message = \sprintf(
                    'DTO class \'%s\' does not have getter method property \'%s\' - cannot patch dto',
                    \get_class($dto),
                    $property
                );

                throw new \BadMethodCallException($message);
            }

            // Determine setter method
            $setter = 'set' . \ucfirst($property);

            // Update current dto property value
            $this->$setter($dto->$getter());
        }

        return $this;
    }
}
