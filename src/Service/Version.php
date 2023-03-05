<?php
declare(strict_types = 1);
/**
 * /src/Service/Version.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Service;

use App\Utils\JSON;
use Closure;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;
use function array_key_exists;
use function assert;
use function is_array;
use function is_string;

/**
 * Class Version
 *
 * @package App\Service
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class Version
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly CacheInterface $appCacheApcu,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Method to get application version from cache or create new entry to
     * cache with version value from composer.json file.
     */
    public function get(): string
    {
        $output = '0.0.0';

        try {
            $output = (string)$this->appCacheApcu->get('application_version', $this->getClosure());
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

            $composerData = JSON::decode((string)file_get_contents($this->projectDir . '/composer.json'), true);

            assert(is_array($composerData));

            return array_key_exists('version', $composerData) && is_string($composerData['version'])
                ? $composerData['version']
                : '0.0.0';
        };
    }
}
