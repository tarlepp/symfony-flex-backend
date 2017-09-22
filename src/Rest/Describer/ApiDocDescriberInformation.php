<?php
declare(strict_types=1);
/**
 * /src/Rest/Describer/ApiDocDescriberInformation.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Describer;

use App\Utils\JSON;
use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;

/**
 * Class ApiDocDescriberInformation
 *
 * @package App\Rest\Describer
 */
class ApiDocDescriberInformation implements DescriberInterface
{
    /**
     * @var string;
     */
    private $rootDir;

    /**
     * ApiDocDescriberInformation constructor.
     *
     * @param string $rootDir
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * @param Swagger $api
     *
     * @throws \LogicException
     */
    public function describe(Swagger $api): void
    {
        // Read composer.json to an object
        $composer = JSON::decode(\file_get_contents($this->rootDir . '/../composer.json'));

        // Get API info
        $info = $api->getInfo();

        // Set information
        $info->setTitle($composer->extra->projectTitle);
        $info->setDescription($composer->description);
        $info->setVersion($composer->version);
        $info->getLicense()->setName(
            \is_array($composer->license) ? \implode(', ', $composer->license) : $composer->license
        );
    }
}
