<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Service/LocalizationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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

/**
 * @package App\Tests\Integration\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocalizationTest extends KernelTestCase
{
    #[TestDox('Test that `getLanguages` returns expected')]
    public function testThatGetLanguagesReturnsExpected(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $expected = Language::getValues();

        self::assertSame(
            $expected,
            (new Localization($cache, $logger, $requestStack))->getLanguages(),
        );
    }

    #[TestDox('Test that `getLocales` returns expected')]
    public function testThatGetLocalesReturnsExpected(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $expected = Locale::getValues();

        self::assertSame(
            $expected,
            (new Localization($cache, $logger, $requestStack))->getLocales(),
        );
    }

    #[TestDox('Test that `LoggerInterface::error` method is called when `CacheInterface` throws an exception')]
    public function testThatLoggerIsCalledWhenCacheThrowsAnException(): void
    {
        $exception = new Exception('test exception');

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException($exception);

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        (new Localization($cache, $logger, $requestStack))
            ->getTimezones();
    }

    #[TestDox('Test that `getFormattedTimezones` method returns expected amount of results')]
    public function testThatGetFormattedTimezonesMethodReturnsExpectedAmountOfResults(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $output = (new Localization($cache, $logger, $requestStack))
            ->getFormattedTimezones();

        self::assertCount(count(DateTimeZone::listIdentifiers()), $output);
    }

    #[TestDox('Test that `getRequestLocale` method returns expected locale when request is not set')]
    public function testThatGetRequestLocaleReturnsDefaultLocaleIfThereIsNoCurrentRequest(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $requestStack = new RequestStack();

        $cache
            ->expects($this->never())
            ->method('get');

        $logger
            ->expects($this->never())
            ->method('error');

        self::assertSame(
            Locale::getDefault()->value,
            (new Localization($cache, $logger, $requestStack))->getRequestLocale(),
        );
    }

    #[TestDox('Test that `getRequestLocale` method returns expected locale when request is set')]
    public function testThatGetRequestLocaleReturnsDefaultLocaleWhenThereIsRequest(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $request = new Request();
        $request->setLocale('en');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        self::assertSame(
            'en',
            (new Localization($cache, $logger, $requestStack))->getRequestLocale(),
        );
    }
}
