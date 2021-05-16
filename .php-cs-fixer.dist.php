<?php
declare(strict_types = 1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('somedir');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
