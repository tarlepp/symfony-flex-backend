<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/LogRequestTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\ApiKey;
use App\Entity\LogRequest;
use App\Entity\User;
use App\Utils\Tests\PhpUnitUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_key_exists;
use function get_class;
use function in_array;
use function is_array;
use function is_object;
use function sprintf;
use function ucfirst;

/**
 * Class LogRequestTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogRequestTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = LogRequest::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array  $meta
    */
    public function testThatSetterOnlyAcceptSpecifiedType(
        string $field = null,
        string $type = null,
        array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array  $meta
     */
    public function testThatSetterReturnsInstanceOfEntity(
        string $field = null,
        string $type = null,
        array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param string $field
     * @param string $type
     * @param array $meta
     *
     * @throws Throwable
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
        }

        $logRequest = new LogRequest(
            Request::create(''),
            Response::create('abcdefgh'),
            new User(),
            new ApiKey()
        );

        $value = $logRequest->$getter();

        if (!(array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta))) {
            $type = ArrayCollection::class;

            static::assertInstanceOf($type, $value);
        }

        $message = sprintf(
            'Getter \'%s\' for field \'%s\' did not return expected type \'%s\' return value was \'%s\'',
            $getter,
            $field,
            $type,
            is_object($value) ? get_class($value) : (is_array($value) ? 'array' : $value)
        );

        try {
            if (static::isType($type)) {
                $method = 'assertIs' . ucfirst($type);

                static::$method($value, $message);
            }
        } /** @noinspection BadExceptionsProcessingInspection */ catch (Exception $error) {
            static::assertInstanceOf($type, $value, $message);
        }

        unset($logRequest);
    }

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @param array $headers
     * @param array $expected
     *
     * @throws Throwable
     */
    public function testThatSensitiveDataIsCleanedFromHeaders(array $headers, array $expected): void
    {
        $request = Request::create('');
        $request->headers->replace($headers);

        $logRequest = new LogRequest($request, Response::create());

        static::assertSame($expected, $logRequest->getHeaders());

        unset($logRequest, $request);
    }

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @param array $parameters
     * @param array $expected
     *
     * @throws Throwable
     */
    public function testThatSensitiveDataIsCleanedFromParameters(array $parameters, array $expected): void
    {
        $request = Request::create('', 'POST');
        $request->request->replace($parameters);

        $logRequest = new LogRequest($request, Response::create());

        static::assertSame($expected, $logRequest->getParameters());

        unset($logRequest, $request);
    }

    /**
     * @dataProvider dataProviderTestThatDetermineParametersWorksLikeExpected
     *
     * @param string $content
     * @param array  $expected
     *
     * @throws Throwable
     */
    public function testThatDetermineParametersWorksLikeExpected(string $content, array $expected): void
    {
        $logRequest = new LogRequest(Request::create(''), Response::create());

        $request = Request::create('', 'GET', [], [], [], [], $content);

        static::assertSame($expected, PhpUnitUtil::callMethod($logRequest, 'determineParameters', [$request]));

        unset($request, $logRequest);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatSensitiveDataIsCleaned(): Generator
    {
        yield [
            ['password' => 'password'],
            ['password' => '*** REPLACED ***'],
        ];

        yield [
            ['token' => 'secret token'],
            ['token' => '*** REPLACED ***'],
        ];

        yield [
            ['authorization' => 'authorization bearer'],
            ['authorization' => '*** REPLACED ***'],
        ];

        yield [
            ['cookie' => 'cookie'],
            ['cookie' => '*** REPLACED ***'],
        ];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatDetermineParametersWorksLikeExpected(): Generator
    {
        yield [
            '{"foo":"bar"}',
            ['foo' => 'bar'],
        ];

        yield [
            'foo=bar',
            ['foo' => 'bar'],
        ];

        yield [
            'false',
            [false],
        ];
    }
}
