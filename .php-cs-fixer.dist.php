<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use CodeIgniter\CodingStandard\CodeIgniter4;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->in([
        __DIR__ . '/src/',
    ])
    ->exclude('build')
    ->append([
        __FILE__,
    ]);

$overrides = [
    'declare_strict_types' => false,
    'void_return'          => false,
    'modernize_strpos'     => ['modernize_stripos' => true],
];

$options = [
    'finder'    => $finder,
    'cacheFile' => '.php-cs-fixer.cache',
    'parallel'  => 8,
];

return Factory::create(new CodeIgniter4(), $overrides, $options)->forProjects();
