<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Service/LocalizationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Service;

use App\Service\Localization;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class LocalizationTest
 *
 * @package App\Tests\Integration\Service
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LocalizationTest extends KernelTestCase
{
    public function testThatGetLanguagesReturnsExpected(): void
    {
        static::assertSame(['en', 'fi'], Localization::getLanguages());
    }

    public function testThatGetLocalesReturnsExpected(): void
    {
        static::assertSame(['en', 'fi'], Localization::getLocales());
    }
}
