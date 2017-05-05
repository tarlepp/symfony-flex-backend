<?php
declare(strict_types = 1);
/**
 * This file contains all base IP addresses that are allowed to access to application via development environment.
 *
 * Note that you can add your own IP addresses to 'allowed_addresses_local.php' file which is ignored in VCS.
 * Just create this file with following content:
 *
 * <code>
 *  <?php
 *
 *  return [
 *      'Your IP address here',
 *      'Your another IP address here',
 *      '*' // this allows all ip's
 *  ];
 * </code>
 *
 * @package App
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

$configFile = __DIR__ . '/allowed_addresses_local.php';

// Get local IP addresses
$local = file_exists($configFile) ? require_once $configFile : [];

// By default allow 'localhost'
$base = [
    '127.0.0.1',
    'fe80::1',
    '::1',
];

return array_merge($base, $local);
