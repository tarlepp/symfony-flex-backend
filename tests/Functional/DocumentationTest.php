<?php
declare(strict_types=1);
/**
 * /tests/Functional/DocumentationTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional;

use App\Utils\Tests\WebTestCase;

/**
 * Class DocumentationTest
 *
 * @package App\Tests\Functional
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DocumentationTest extends WebTestCase
{
    public function testThatDocumentationUiWorks(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/api/doc/');

        static::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testThatDocumentationJsonWorks(): void
    {
        $client = $this->getClient();
        $client->request('GET', '/api/doc.json');

        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
