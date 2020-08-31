<?php
declare(strict_types = 1);
/**
 * /tests/E2E/DocumentationTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E;

use App\Utils\Tests\WebTestCase;
use Throwable;

/**
 * Class DocumentationTest
 *
 * @package App\Tests\Functional
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DocumentationTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatDocumentationUiWorks(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/api/doc/');

        static::assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @throws Throwable
     */
    public function testThatDocumentationJsonWorks(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/api/doc.json');

        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
