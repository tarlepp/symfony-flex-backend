<?php
declare(strict_types = 1);

use App\Kernel;
use Liuggio\Fastest\Environment\FastestEnvironment;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static function (array $context): Kernel {
    if (class_exists(FastestEnvironment::class)) {
        FastestEnvironment::setFromRequest();
    }

    return new Kernel($context['APP_ENV'], (bool)$context['APP_DEBUG']);
};
