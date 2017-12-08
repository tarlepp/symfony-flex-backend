<?php
declare(strict_types=1);

use EasyCorp\EasyLog\EasyLogHandler;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\Console\Application;

$handlers = [
    'main'              => [
        'type'          => 'fingers_crossed',
        'action_level'  => 'error',
        'handler'       => 'nested',
    ],
    'nested'            => [
        'type'          => 'stream',
        'path'          => '%kernel.logs_dir%/%kernel.environment%.log',
        'level'         => 'debug',
    ],
    'buffered'          => [
        'type'          => 'buffer',
        'handler'       => 'easylog',
        'channels'      => [
            '!event',
        ],
        'level'         => 'debug',
    ],
    'easylog'           => [
        'type'          => 'service',
        'id'            => EasyLogHandler::class,
    ],
];

$container->addResource(new ClassExistenceResource(Application::class));

if (class_exists(Application::class)) {
    $handlers['console'] = [
        'type'                      => 'console',
        'process_psr_3_messages'    => false,
        'channels'                  => [
            '!event',
            '!doctrine',
        ],
    ];
}

$container->loadFromExtension('monolog', [
    'handlers' => $handlers,
]);
