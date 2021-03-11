<?php
declare(strict_types = 1);
/**
 * /src/Serializer/CollectionNormalizer.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Serializer\Normalizer;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use function is_object;

/**
 * Class CollectionNormalizer
 *
 * @package App\Serializer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CollectionNormalizer implements NormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param mixed $object
     *
     * @return array<int, mixed>
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $output = [];

        foreach ($object as $value) {
            $output[] = $this->normalizer->normalize($value, $format, $context);
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $format === 'json' && is_object($data) && $data instanceof Collection && is_object($data->first());
    }
}
