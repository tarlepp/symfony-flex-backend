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
use App\Utils\Tests\StringableArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogRequestTest extends EntityTestCase
{
    protected string $entityName = LogRequest::class;

    /**
     * @throws Throwable
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function setUp(): void
    {
        static::bootKernel();

        // Store container and entity manager
        $this->testContainer = static::$kernel->getContainer();

        /* @noinspection MissingService */
        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->entityManager = $this->testContainer->get('doctrine.orm.default_entity_manager');

        // Create new entity object
        $this->entity = new $this->entityName([]);

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->repository = $this->entityManager->getRepository($this->entityName);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array $meta
     *
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array $meta
     *
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @throws Throwable
     *
     * @testdox Test that getter method for `$field` with `$type` returns expected.
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
        }

        $logRequest = new LogRequest(
            [],
            Request::create(''),
            new Response('abcdefgh'),
            new User(),
            new ApiKey()
        );

        $value = $logRequest->{$getter}();

        if (!(array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta))) {
            $type = ArrayCollection::class;

            static::assertInstanceOf($type, $value);
        }

        $returnValue = $value;

        if (is_object($value)) {
            $returnValue = get_class($value);
        } elseif (is_array($value)) {
            $returnValue = 'array';
        }

        $message = sprintf(
            'Getter \'%s\' for field \'%s\' did not return expected type \'%s\' return value was \'%s\'',
            $getter,
            $field,
            $type,
            $returnValue
        );

        try {
            if (static::isType($type)) {
                $method = 'assertIs' . ucfirst($type);

                static::$method($value, $message);
            }
        } catch (Throwable $error) {
            static::assertInstanceOf($type, $value, $message . ' - ' . $error->getMessage());
        }
    }

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @throws Throwable
     *
     * @testdox Test that sensitive data `$properties` from `$headers` is cleaned and output is expected `$expected`.
     */
    public function testThatSensitiveDataIsCleanedFromHeaders(
        StringableArrayObject $properties,
        StringableArrayObject $headers,
        StringableArrayObject $expected
    ): void {
        $request = Request::create('');
        $request->headers->replace($headers->getArrayCopy());

        $logRequest = new LogRequest($properties->getArrayCopy(), $request, new Response());

        static::assertSame($expected->getArrayCopy(), $logRequest->getHeaders());
    }

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @throws Throwable
     *
     * @testdox Test that sensitive data `$properties` from `parameters` is cleaned and output is expected `$expected`.
     */
    public function testThatSensitiveDataIsCleanedFromParameters(
        StringableArrayObject $properties,
        StringableArrayObject $parameters,
        StringableArrayObject $expected
    ): void {
        $request = Request::create('', 'POST');
        $request->request->replace($parameters->getArrayCopy());

        $logRequest = new LogRequest($properties->getArrayCopy(), $request, new Response());

        static::assertSame($expected->getArrayCopy(), $logRequest->getParameters());
    }

    /**
     * @dataProvider dataProviderTestThatDetermineParametersWorksLikeExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `determineParameters` method returns `$expected` when using `$content` as input.
     */
    public function testThatDetermineParametersWorksLikeExpected(string $content, StringableArrayObject $expected): void
    {
        $logRequest = new LogRequest([], Request::create(''), new Response());

        $request = Request::create('', 'GET', [], [], [], [], $content);

        static::assertSame(
            $expected->getArrayCopy(),
            PhpUnitUtil::callMethod($logRequest, 'determineParameters', [$request])
        );
    }

    public function dataProviderTestThatSensitiveDataIsCleaned(): Generator
    {
        yield [
            new StringableArrayObject(['password']),
            new StringableArrayObject(['password' => 'password']),
            new StringableArrayObject(['password' => '*** REPLACED ***']),
        ];

        yield [
            new StringableArrayObject(['token']),
            new StringableArrayObject(['token' => 'secret token']),
            new StringableArrayObject(['token' => '*** REPLACED ***']),
        ];

        yield [
            new StringableArrayObject(['authorization']),
            new StringableArrayObject(['authorization' => 'authorization bearer']),
            new StringableArrayObject(['authorization' => '*** REPLACED ***']),
        ];

        yield [
            new StringableArrayObject(['cookie']),
            new StringableArrayObject(['cookie' => 'cookie']),
            new StringableArrayObject(['cookie' => '*** REPLACED ***']),
        ];

        yield [
            new StringableArrayObject([
                'password',
                'token',
                'authorization',
                'cookie',
            ]),
            new StringableArrayObject([
                'password' => 'password',
                'token' => 'secret token',
                'authorization' => 'authorization bearer',
                'cookie' => 'cookie',
            ]),
            new StringableArrayObject([
                'password' => '*** REPLACED ***',
                'token' => '*** REPLACED ***',
                'authorization' => '*** REPLACED ***',
                'cookie' => '*** REPLACED ***',
            ]),
        ];
    }

    public function dataProviderTestThatDetermineParametersWorksLikeExpected(): Generator
    {
        yield [
            '{"foo":"bar"}',
            new StringableArrayObject(['foo' => 'bar']),
        ];

        yield [
            'foo=bar',
            new StringableArrayObject(['foo' => 'bar']),
        ];

        yield [
            'false',
            new StringableArrayObject([false]),
        ];
    }
}
