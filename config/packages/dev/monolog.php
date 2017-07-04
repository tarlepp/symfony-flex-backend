<?php
declare(strict_types=1);

use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\Console\Application;

$handlers = [
    'main' => [
        'type'      => 'stream',
        'path'      => '%kernel.logs_dir%/%kernel.environment%.log',
        'level'     => 'debug',
        'channels'  => ['!event'],
    ],
    'firephp' => [
        'type'  => 'firephp',
        'level' => 'info',
    ],
    'chromephp' => [
        'type'  => 'chromephp',
        'level' => 'info',
    ],
];

$container->addResource(new ClassExistenceResource(Application::class));

if (class_exists(Application::class)) {
    $handlers['console'] = [
        'type'                      => 'console',
        'process_psr_3_messages'    => false,
        'channels'                  => ['!event', '!doctrine', '!console'],
    ];
}

$container->loadFromExtension('monolog', [
    'handlers' => $handlers,
]);
