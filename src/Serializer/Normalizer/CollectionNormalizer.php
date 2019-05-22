<?php
declare(strict_types = 1);
/**
 * /src/Serializer/CollectionNormalizer.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use function is_object;

/**
 * Class CollectionNormalizer
 *
 * @package App\Serializer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CollectionNormalizer implements NormalizerInterface
{
    /**
     * @var ObjectNormalizer
     */
    private $normalizer;

    /**
     * CollectionNormalizer constructor.
     *
     * @param ObjectNormalizer $normalizer
     */
    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /** @noinspection ParameterDefaultValueIsNotNullInspection */
    /**
     * @inheritdoc
     *
     * @param Collection|ArrayCollection|mixed $collection
     */
    public function normalize($collection, $format = null, array $context = [])
    {
        $output = [];

        foreach ($collection as $value) {
            $output[] = $this->normalizer->normalize($value, $format, $context);
        }

        return $output;
    }

    /**
     * @inheritdoc
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $format === 'json' && is_object($data) && $data instanceof Collection && is_object($data->first());
    }
}
