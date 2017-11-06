<?php
declare(strict_types = 1);
/**
 * /src/DTO/RestDto.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DTO;

/**
 * Class RestDto
 *
 * @package App\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RestDto implements RestDtoInterface
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
     * @throws \LogicException
     * @throws \BadMethodCallException
     */
    public function patch(RestDtoInterface $dto): RestDtoInterface
    {
        foreach ($dto->getVisited() as $property) {
            // Determine getter method
            $getter = $this->determineGetterMethod($dto, $property);

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

    /**
     * Method to determine used getter method for DTO property.
     *
     * @param RestDtoInterface $dto
     * @param string           $property
     *
     * @return string|bool
     *
     * @throws \LogicException
     */
    private function determineGetterMethod(RestDtoInterface $dto, string $property)
    {
        $getters = [
            'get' . \ucfirst($property),
            'is' . \ucfirst($property),
            'has' . \ucfirst($property),
        ];

        $filter = function (string $method) use ($dto): bool {
            return \method_exists($dto, $method);
        };

        // Determine getter method
        $filtered = \array_filter($getters, $filter);

        if (\count($filtered) > 1) {
            $message = \sprintf(
                'Property \'%s\' has multiple getter methods - this is insane!',
                $property
            );

            throw new \LogicException($message);
        }

        return \current($filtered);
    }
}
