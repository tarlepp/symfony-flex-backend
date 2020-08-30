<?php
declare(strict_types = 1);
/**
 * /src/Service/Version.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Service;

use App\Utils\JSON;
use Closure;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

/**
 * Class Version
 *
 * @package App\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Version
{
    private string $projectDir;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    /**
     * Version constructor.
     */
    public function __construct(string $projectDir, CacheInterface $appCacheApcu, LoggerInterface $logger)
    {
        $this->projectDir = $projectDir;
        $this->cache = $appCacheApcu;
        $this->logger = $logger;
    }

    /**
     * Method to get application version from cache or create new entry to
     * cache with version value from composer.json file.
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function get(): string
    {
        $output = '0.0.0';

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $output = $this->cache->get('application_version', $this->getClosure());
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $output;
    }

    private function getClosure(): Closure
    {
        return function (ItemInterface $item): string {
            // One year
            $item->expiresAfter(31536000);

            /** @var stdClass $composerData */
            $composerData = JSON::decode((string)file_get_contents($this->projectDir . '/composer.json'));

            return (string)($composerData->version ?? '0.0.0');
        };
    }
}
