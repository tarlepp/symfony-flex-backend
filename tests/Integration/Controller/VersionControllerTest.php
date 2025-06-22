<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/VersionControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller;

use App\Controller\VersionController;
use App\Service\Version;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class VersionControllerTest extends KernelTestCase
{
    #[TestDox('Test that `__invoke` method calls expected service methods')]
    public function testThatInvokeMethodIsCallingExpectedMethods(): void
    {
        $version = $this->getMockBuilder(Version::class)
            ->disableOriginalConstructor()
            ->getMock();

        $version
            ->expects($this->once())
            ->method('get')
            ->willReturn('1.0.0');

        $response = new VersionController($version)();
        $content = $response->getContent();

        self::assertSame(200, $response->getStatusCode());
        self::assertNotFalse($content);
        self::assertJson($content);
        self::assertJsonStringEqualsJsonString('{"version": "1.0.0"}', $content);
    }
}
