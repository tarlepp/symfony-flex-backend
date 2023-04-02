<?php
declare(strict_types = 1);
/**
 * /tests/E2E/DocumentationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E;

use App\Tests\E2E\TestCase\WebTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;

/**
 * Class DocumentationTest
 *
 * @package App\Tests\Functional
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DocumentationTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /api/doc` request returns `200`')]
    public function testThatDocumentationUiWorks(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/api/doc');

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /api/doc.json` request returns `200`')]
    public function testThatDocumentationJsonWorks(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/api/doc.json');

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
