<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/LogLoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\LogLogin;
use App\Entity\User;
use App\Utils\Tests\PhpUnitUtil;
use DeviceDetector\DeviceDetector;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use function array_key_exists;
use function in_array;
use function ucfirst;

/**
 * Class LogLoginTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method LogLogin getEntity()
 */
class LogLoginTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = LogLogin::class;

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function createEntity(): LogLogin
    {
        $request = Request::create('');

        // Parse user agent data with device detector
        $deviceDetector = new DeviceDetector((string)$request->headers->get('User-Agent'));
        $deviceDetector->parse();

        return new LogLogin('', $request, $deviceDetector, new User());
    }
}
