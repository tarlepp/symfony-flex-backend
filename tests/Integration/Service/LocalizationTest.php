<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Service/LocalizationTest.php
 */

namespace App\Tests\Integration\Service;

use App\Enum\Language;
use App\Enum\Locale;
use App\Service\Localization;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use function count;

final class LocalizationTest extends KernelTestCase
{
    #[TestDox('Test that `getLanguages` returns expected')]
    public function testThatGetLanguagesReturnsExpected(): void
    {
        $cacheStub = self::createStub(CacheInterface::class);
        $loggerStub = self::createStub(LoggerInterface::class);
        $requestStack = new RequestStack();

        $expected = Language::getValues();

        self::assertSame(
            $expected,
            new Localization($cacheStub, $loggerStub, $requestStack)
                ->getLanguages(),
        );
    }

    #[TestDox('Test that `getLocales` returns expected')]
    public function testThatGetLocalesReturnsExpected(): void
    {
        $cacheStub = self::createStub(CacheInterface::class);
        $loggerStub = self::createStub(LoggerInterface::class);
        $requestStack = new RequestStack();

        $expected = Locale::getValues();

        self::assertSame(
            $expected,
            new Localization($cacheStub, $loggerStub, $requestStack)
                ->getLocales(),
        );
    }

    #[TestDox('Test that `LoggerInterface::error` method is called when `CacheInterface` throws an exception')]
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        $exception = new Exception('test exception');

        $cacheMock = $this->getMockBuilder(CacheInterface::class)->getMock();
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $cacheMock
            ->expects($this->once())
            ->method('get')
            ->willThrowException($exception);

        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        new Localization($cacheMock, $loggerMock, $requestStack)
            ->getTimezones();
    }

    #[TestDox('Test that `getFormattedTimezones` method returns expected amount of results')]
    public function testThatGetFormattedTimezonesMethodReturnsExpectedAmountOfResults(): void
    {
        $cacheStub = self::createStub(CacheInterface::class);
        $loggerStub = self::createStub(LoggerInterface::class);
        $requestStack = new RequestStack();

        $output = new Localization($cacheStub, $loggerStub, $requestStack)
            ->getFormattedTimezones();

        self::assertCount(count(DateTimeZone::listIdentifiers()), $output);
    }

    #[TestDox('Test that `getRequestLocale` method returns expected locale when request is not set')]
    public function testThatGetRequestLocaleReturnsDefaultLocaleIfThereIsNoCurrentRequest(): void
    {
        $cacheMock = $this->getMockBuilder(CacheInterface::class)->getMock();
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $cacheMock
            ->expects($this->never())
            ->method('get');

        $loggerMock
            ->expects($this->never())
            ->method('error');

        self::assertSame(
            Locale::getDefault()->value,
            new Localization($cacheMock, $loggerMock, $requestStack)
                ->getRequestLocale(),
        );
    }

    #[TestDox('Test that `getRequestLocale` method returns expected locale when request is set')]
    public function testThatGetRequestLocaleReturnsDefaultLocaleWhenThereIsRequest(): void
    {
        $cacheStub = self::createStub(CacheInterface::class);
        $loggerStub = self::createStub(LoggerInterface::class);

        $request = new Request();
        $request->setLocale('en');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        self::assertSame(
            'en',
            new Localization($cacheStub, $loggerStub, $requestStack)
                ->getRequestLocale(),
        );
    }
}
