<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Serializer/CollectionNormalizerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Serializer;

use App\Serializer\Normalizer\CollectionNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Generator;
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
            ->expects(self::once())
            ->method('normalize')
            ->with($object, 'someFormat', ['someContext']);

        (new CollectionNormalizer($normalizer))->normalize([$object], 'someFormat', ['someContext']);
    }

    /**
     * @dataProvider dataProviderTestThatSupportsNormalizationReturnsExpected
     *
     * @testdox Test that `supportsNormalization` method returns `$expected` when using `$data` + `$format`
     */
    public function testThatSupportsNormalizationReturnsExpected(bool $expected, mixed $data, ?string $format): void
    {
        $normalizer = $this->getMockBuilder(ObjectNormalizer::class)->disableOriginalConstructor()->getMock();

        self::assertSame(
            $expected,
            (new CollectionNormalizer($normalizer))->supportsNormalization($data, $format),
        );
    }

    /**
     * @return Generator<array{0: bool, 1: mixed, 2: 'json'|'not-json'|null}>
     */
    public function dataProviderTestThatSupportsNormalizationReturnsExpected(): Generator
    {
        yield [false, '', null];
        yield [false, '', 'json'];
        yield [false, new stdClass(), 'json'];
        yield [false, new ArrayCollection(), 'json'];
        yield [false, new ArrayCollection(['string']), 'json'];
        yield [false, new ArrayCollection([123]), 'json'];
        yield [false, new ArrayCollection([new stdClass()]), 'not-json'];
        yield [true, new ArrayCollection([new stdClass()]), 'json'];
    }
}
