<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/v1/Localization/LocaleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\v1\Localization;

use App\Controller\v1\Localization\LocaleController;
use App\Service\Localization;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Integration\Controller\v1\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LocaleControllerTest extends KernelTestCase
{
    #[TestDox('Test that controller calls expected service method(s) and returns expected response')]
    public function testThatInvokeMethodCallsExpectedServiceMethods(): void
    {
        $Localization = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();

        $Localization
            ->expects(static::once())
            ->method('getLocales')
            ->willReturn(['fi', 'en']);

        $response = new LocaleController($Localization)();
        $content = $response->getContent();

        self::assertSame(200, $response->getStatusCode());
        self::assertNotFalse($content);
        self::assertJson('["fi", "en"]', $content);
    }
}
