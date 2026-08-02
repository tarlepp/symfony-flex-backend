<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Controller/VersionControllerTest.php
 */

namespace App\Tests\Integration\Controller;

use App\Controller\VersionController;
use App\Service\Version;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
