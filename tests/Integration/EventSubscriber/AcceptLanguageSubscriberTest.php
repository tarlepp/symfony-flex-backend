<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/AcceptLanguageSubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\AcceptLanguageSubscriber;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class AcceptLanguageSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AcceptLanguageSubscriberTest extends KernelTestCase
{
    public function testThatSpecifiedDefaultLanguageIsSet(): void
    {
        static::bootKernel();

        $request = new Request();
        $request->headers->set('Accept-Language', 'foo');

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $subscriber = new AcceptLanguageSubscriber('bar');
        $subscriber->onKernelRequest($event);

        static::assertSame('bar', $request->getLocale());
    }

    /**
     * @dataProvider dataProviderTestThatLocaleIsSetAsExpected
     *
     * @testdox Test that when default locale is `$default` and when asking `$asked` locale result is `$expected`.
     */
    public function testThatLocaleIsSetAsExpected(string $expected, string $default, string $asked): void
    {
        static::bootKernel();

        $request = new Request();
        $request->headers->set('Accept-Language', $asked);

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        (new AcceptLanguageSubscriber($default))
            ->onKernelRequest($event);

        static::assertSame($expected, $request->getLocale());
    }

    public function dataProviderTestThatLocaleIsSetAsExpected(): Generator
    {
        yield ['fi', 'fi', 'fi'];
        yield ['fi', 'fi', 'sv'];
        yield ['en', 'fi', 'en'];
    }
}
