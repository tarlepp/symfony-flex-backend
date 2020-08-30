<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/UuidHelperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Rest;

use App\Rest\UuidHelper;
use Generator;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class UuidHelperTest
 *
 * @package App\Tests\Unit\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UuidHelperTest extends KernelTestCase
{
    public function testThatGetFactoryReturnsSameInstance(): void
    {
        $factory = UuidHelper::getFactory();

        self::assertSame($factory, UuidHelper::getFactory());
        self::assertSame($factory, UuidHelper::getFactory());
        self::assertSame($factory, UuidHelper::getFactory());
    }

    /**
     * @dataProvider dataProviderTestThatGetTypeReturnsExpected
     *
     * @testdox test that `getType` method returns `$expected` with `$value` value.
     */
    public function testThatGetTypeReturnsExpected(?string $expected, string $value): void
    {
        static::assertSame($expected, UuidHelper::getType($value));
    }

    public function testThatGetBytesThrowsAnExceptionWithNonUuidValue(): void
    {
        $this->expectException(InvalidUuidStringException::class);

        UuidHelper::getBytes('foobar');
    }

    /**
     * @throws Throwable
     */
    public function testThatGetBytesReturnsExpected(): void
    {
        $factory = UuidHelper::getFactory();
        $uuid = $factory->uuid1();

        static::assertSame($uuid->getBytes(), UuidHelper::getBytes($uuid->toString()));
    }

    /**
     * @throws Throwable
     */
    public function dataProviderTestThatGetTypeReturnsExpected(): Generator
    {
        yield [null, 'foo'];

        yield [UuidBinaryOrderedTimeType::NAME, UuidHelper::getFactory()->uuid1()->toString()];
    }
}
