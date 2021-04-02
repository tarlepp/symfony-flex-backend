<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Serializer/CollectionNormalizerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Serializer;

use App\Serializer\Normalizer\CollectionNormalizer;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Throwable;

/**
 * Class CollectionNormalizerTest
 *
 * @package App\Tests\Integration\Serializer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CollectionNormalizerTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `normalize` method calls expected `ObjectNormalizer` method with expected parameters
     */
    public function testThatNormalizeMethodCallsExpectedObjectNormalizerMethod(): void
    {
        $normalizer = $this->getMockBuilder(ObjectNormalizer::class)->disableOriginalConstructor()->getMock();
        $object = new stdClass();

        $normalizer
            ->expects(static::once())
            ->method('normalize')
            ->with($object, 'someFormat', ['someContext']);

        (new CollectionNormalizer($normalizer))->normalize([$object], 'someFormat', ['someContext']);
    }
}
