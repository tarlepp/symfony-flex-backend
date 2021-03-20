<?php
declare(strict_types = 1);
/**
 * /tests/bootstrap_fastest.php
 *
 * @package App\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
$files = glob(sprintf('%s%stest_database_cache*', sys_get_temp_dir(), DIRECTORY_SEPARATOR));

is_array($files) ? array_map('unlink', $files) : throw new RuntimeException('Cannot real cache files...');
