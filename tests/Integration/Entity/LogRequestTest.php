<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/LogRequestTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\LogRequest;
use App\Utils\Tests\PHPUnitUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @param array $headers
     * @param array $expected
     */
    public function testThatSensitiveDataIsCleanedFromHeaders(array $headers, array $expected)
    {
        $logRequest = new LogRequest(Request::create(''), Response::create());

        $logRequest->setHeaders($headers);

        static::assertSame($expected, $logRequest->getHeaders());
    }

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @param array $parameters
     * @param array $expected
     */
    public function testThatSensitiveDataIsCleanedFromParameters(array $parameters, array $expected)
    {
        $logRequest = new LogRequest(Request::create(''), Response::create());

        $logRequest->setParameters($parameters);

        static::assertSame($expected, $logRequest->getParameters());
    }

    /**
     * @dataProvider dataProviderTestThatDetermineParametersWorksLikeExpected
     *
     * @param   string  $content
     * @param   array   $expected
     */
    public function testThatDetermineParametersWorksLikeExpected(string $content, array $expected)
    {
        $logRequest = new LogRequest(Request::create(''), Response::create());

        $request = Request::create('', 'GET', [], [], [], [], $content);

        static::assertSame($expected, PHPUnitUtil::callMethod($logRequest, 'determineParameters', [$request]));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatSensitiveDataIsCleaned(): array
    {
        return [
            [
                ['passWord' => 'password'],
                ['passWord' => '*** REPLACED ***'],
            ],
            [
                ['token' => 'secret token'],
                ['token' => '*** REPLACED ***'],
            ],
            [
                ['Authorization' => 'authorization bearer'],
                ['Authorization' => '*** REPLACED ***'],
            ],
            [
                ['cookie' => ['cookie']],
                ['cookie' => '*** REPLACED ***'],
            ],
            [
                ['someHeader' => [
                    'foo'       => 'bar',
                    'password'  => 'some password',
                ]],
                ['someHeader' => [
                    'foo'       => 'bar',
                    'password'  => '*** REPLACED ***',
                ]],
            ]
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatDetermineParametersWorksLikeExpected(): array
    {
        return [
            [
                '{"foo":"bar"}',
                ['foo' => 'bar'],
            ],
            [
                'foo=bar',
                ['foo' => 'bar'],
            ],
            [
                'false',
                [false],
            ]
        ];
    }
}
