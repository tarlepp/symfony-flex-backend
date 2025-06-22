<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/AcceptLanguageSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\AcceptLanguageSubscriber;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class AcceptLanguageSubscriberTest extends KernelTestCase
{
    #[TestDox('Test that specific default language is set')]
    public function testThatSpecifiedDefaultLanguageIsSet(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $request = new Request();
        $request->headers->set('Accept-Language', 'foo');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new AcceptLanguageSubscriber('bar');
        $subscriber->onKernelRequest($event);

        self::assertSame('bar', $request->getLocale());
    }

    #[DataProvider('dataProviderTestThatLocaleIsSetAsExpected')]
    #[TestDox('Test that when default locale is `$default` and when asking `$asked` locale result is `$expected`.')]
    public function testThatLocaleIsSetAsExpected(string $expected, string $default, string $asked): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        $request = new Request();
        $request->headers->set('Accept-Language', $asked);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        new AcceptLanguageSubscriber($default)
            ->onKernelRequest($event);

        self::assertSame($expected, $request->getLocale());
    }

    /**
     * @return Generator<array{0: string, 1: string, 2: string}>
     */
    public static function dataProviderTestThatLocaleIsSetAsExpected(): Generator
    {
        yield ['fi', 'fi', 'fi'];
        yield ['fi', 'fi', 'sv'];
        yield ['en', 'fi', 'en'];
    }
}
