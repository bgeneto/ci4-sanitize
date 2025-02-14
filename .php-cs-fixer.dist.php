<?php

declare(strict_types=1);

use CodeIgniter\CodingStandard\CodeIgniter4;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->in([
        __DIR__ . '/src/',
        __DIR__ . '/tests/',
    ])
    ->exclude('build')
    ->append([
        __FILE__,
    ]);

$overrides = [
    'declare_strict_types'    => false,
    'void_return'             => false,
    'modernize_strpos'        => ['modernize_stripos' => true],
    'mb_str_functions'        => true,
    'global_namespace_import' => [
        'import_constants' => false,
        'import_functions' => false,
        'import_classes'   => true,
    ],
    'native_function_invocation' => [
        'include' => ['@all'],
        'scope'   => 'all',
        'strict'  => true,
    ],
];

$options = [
    'finder'    => $finder,
    'cacheFile' => 'build/.php-cs-fixer.cache',
    'parallel'  => 8,
];

return Factory::create(new CodeIgniter4(), $overrides, $options)->forProjects();
