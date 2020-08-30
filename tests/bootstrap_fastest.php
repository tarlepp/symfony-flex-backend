<?php
declare(strict_types = 1);
/**
 * /tests/bootstrap_fastest.php
 *
 * @package App\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
$pattern = sprintf('%s%stest_database_cache*', sys_get_temp_dir(), DIRECTORY_SEPARATOR);

array_map('unlink', glob($pattern));
