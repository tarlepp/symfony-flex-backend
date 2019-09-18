<?php
declare(strict_types = 1);
/**
 * /src/Service/Version.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Service;

use App\Helpers\LoggerAwareTrait;
use App\Utils\JSON;
use Exception;
use stdClass;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class Version
 *
 * @package App\Service
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Version
{
    // Traits
    use LoggerAwareTrait;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * Version constructor.
     *
     * @param string         $projectDir
     * @param CacheInterface $appVersionCache
     */
    public function __construct(string $projectDir, CacheInterface $appVersionCache)
    {
        $this->projectDir = $projectDir;
        $this->cache = $appVersionCache;
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @return string
     *
     * @psalm-suppress InvalidThrow
     */
    public function get(): string
    {
        $output = '0.0.0';

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $output = $this->cache->get('application_version', function (ItemInterface $item): string {
                $item->expiresAfter(31536000); // One year

                /** @var stdClass $composerData */
                $composerData = JSON::decode((string)file_get_contents($this->projectDir . '/composer.json'));

                return (string)($composerData->version ?? '0.0.0');
            });
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $output;
    }
}
