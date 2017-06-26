<?php
declare(strict_types=1);
/**
 * /tests/Functional/Rest/Traits/Actions/RootActionsTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Rest\Traits\Actions;

use App\Utils\Tests\WebTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RootActionsTest
 *
 * @package App\Tests\Functional\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RootActionsTest extends WebTestCase
{
    /**
     * @dataProvider dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CountAction::countAction()
     *
     * @param string $method
     */
    public function testThatCountRouteDoesNotAllowNotSupportedHttpMethods(string $method): void
    {
        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatCountRouteWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CountAction::countAction()
     *
     * @param string $method
     */
    public function testThatCountRouteWorksWithAllowedHttpMethods(string $method): void
    {
        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions/count');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CreateAction::createAction()
     * @covers \App\Rest\Traits\Actions\Root\FindAction::findAction()
     *
     * @param string $method
     */
    public function testThatRootRouteDoesNotAllowNotSupportedHttpMethods(string $method): void
    {
        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\CreateAction::createAction()
     * @covers \App\Rest\Traits\Actions\Root\FindAction::findAction()
     *
     * @param string $method
     */
    public function testThatRootRouteWorksWithAllowedHttpMethods(string $method): void
    {
        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\DeleteAction::deleteAction()
     * @covers \App\Rest\Traits\Actions\Root\FindOneAction::findOneAction()
     * @covers \App\Rest\Traits\Actions\Root\UpdateAction::updateAction()
     *
     * @param string $method
     */
    public function testThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(string $method): void
    {
        $uuid = Uuid::uuid4()->toString();

        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions/' . $uuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\DeleteAction::deleteAction()
     * @covers \App\Rest\Traits\Actions\Root\FindOneAction::findOneAction()
     * @covers \App\Rest\Traits\Actions\Root\UpdateAction::updateAction()
     *
     * @param string $method
     */
    public function testThatRootRouteWithIdWorksWithAllowedHttpMethods(string $method): void
    {
        $uuid = Uuid::uuid4()->toString();

        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions/' . $uuid);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\IdsAction::idsAction()
     *
     * @param string $method
     */
    public function testThatIdsRouteDoesNotAllowNotSupportedHttpMethods(string $method): void
    {
        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode());
    }

    /**
     * @dataProvider dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods
     *
     * @covers \App\Rest\Traits\Actions\Root\IdsAction::idsAction()
     *
     * @param string $method
     */
    public function testThatIdsRouteWorksWithAllowedHttpMethods(string $method): void
    {
        $client = $this->getClientForRootUser();
        $client->request($method, '/test_root_actions/ids');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(500, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function dataProviderTestThatCountRouteDoesNotAllowNotSupportedHttpMethods(): array
    {
        return [
            ['HEAD'],
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatCountRouteWorksWithAllowedHttpMethods(): array
    {
        return [
            ['GET'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteDoesNotAllowNotSupportedHttpMethods(): array
    {
        return [
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteWorksWithAllowedHttpMethods(): array
    {
        return [
            ['GET'],
            ['POST'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteWithIdDoesNotAllowNotSupportedHttpMethods(): array
    {
        return [
            ['POST'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatRootRouteWithIdWorksWithAllowedHttpMethods(): array
    {
        return [
            ['DELETE'],
            ['GET'],
            ['PUT'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatIdsRouteDoesNotAllowNotSupportedHttpMethods(): array
    {
        return [
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['foobar'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatIdsRouteWorksWithAllowedHttpMethods(): array
    {
        return [
            ['GET']
        ];
    }

    /**
     * @return Client
     */
    private function getClientForRootUser(): Client
    {
        return $this->getClient('john-root', 'password-root');
    }
}
