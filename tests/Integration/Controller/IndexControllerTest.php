<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/IndexControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller;

use App\Controller\IndexController;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IndexControllerTest extends KernelTestCase
{
    #[TestDox('Test that `__invoke` method returns proper response')]
    public function testThatInvokeMethodReturnsExpectedResponse(): void
    {
        $response = new IndexController()();
        $content = $response->getContent();

        self::assertSame(200, $response->getStatusCode());
        self::assertNotFalse($content);
        self::assertJson($content);
        self::assertJsonStringEqualsJsonString('{}', $content);
    }
}
