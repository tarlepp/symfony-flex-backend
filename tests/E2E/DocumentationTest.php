<?php
declare(strict_types = 1);
/**
 * /tests/E2E/DocumentationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E;

use App\Utils\Tests\WebTestCase;
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
     *
     * @testdox Test that `GET /api/doc/` request returns HTTP status `200`
     */
    public function testThatDocumentationUiWorks(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/api/doc/');

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `GET /api/doc.json` request returns HTTP status `200`
     */
    public function testThatDocumentationJsonWorks(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/api/doc.json');

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
