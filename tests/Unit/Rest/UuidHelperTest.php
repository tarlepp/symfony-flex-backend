<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/UuidHelperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Rest;

use App\Rest\UuidHelper;
use Generator;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class UuidHelperTest
 *
 * @package App\Tests\Unit\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UuidHelperTest extends KernelTestCase
{
    /**
     * @testdox Test that `UuidHelper::getFactory` method returns always same instance of `UuidFactory`
     */
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
     * @testdox test that `UuidHelper::getType` method returns `$expected` when using `$value` as an input
     */
    public function testThatGetTypeReturnsExpected(?string $expected, string $value): void
    {
        static::assertSame($expected, UuidHelper::getType($value));
    }

    /**
     * @testdox Test that `UuidHelper::getBytes` method throws expected exception with non UUID value
     */
    public function testThatGetBytesThrowsAnExceptionWithNonUuidValue(): void
    {
        $this->expectException(InvalidUuidStringException::class);

        UuidHelper::getBytes('foobar');
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `UuidHelper::getBytes` method returns expected when using valid UUID value
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

        yield [UuidBinaryType::NAME, UuidHelper::getFactory()->uuid2(1)->toString()];

        yield [
            UuidBinaryType::NAME,
            UuidHelper::getFactory()->uuid3(Uuid::NAMESPACE_URL, 'https://www.github.com/tarlepp')->toString()
        ];

        yield [UuidBinaryType::NAME, UuidHelper::getFactory()->uuid4()->toString()];

        yield [
            UuidBinaryType::NAME,
            UuidHelper::getFactory()->uuid5(Uuid::NAMESPACE_URL, 'https://www.github.com/tarlepp')->toString()
        ];
    }
}
