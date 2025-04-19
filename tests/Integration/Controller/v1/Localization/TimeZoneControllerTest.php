<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Localization/TimeZoneControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Localization;

use App\Controller\v1\Localization\TimeZoneController;
use App\Service\Localization;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Integration\Controller\v1\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class TimeZoneControllerTest extends KernelTestCase
{
    #[TestDox('Test that controller calls expected service method(s) and returns expected response')]
    public function testThatInvokeMethodCallsExpectedServiceMethods(): void
    {
        $Localization = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();

        $Localization
            ->expects(static::once())
            ->method('getTimeZones')
            ->willReturn([
                [
                    'timezone' => 'Europe',
                    'identifier' => 'Europe/Helsinki',
                    'offset' => 'GMT+2:00',
                    'value' => 'Europe/Helsinki',
                ],
            ]);

        $response = new TimeZoneController($Localization)();
        $content = $response->getContent();

        self::assertSame(200, $response->getStatusCode());
        self::assertNotFalse($content);
        self::assertJson(
            '[{"timezone":"Europe","identifier":"Europe/Helsinki","offset":"GMT+2:00","value":"Europe/Helsinki"}]',
            $content,
        );
    }
}
