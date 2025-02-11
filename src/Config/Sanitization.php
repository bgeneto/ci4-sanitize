<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Sanitization extends BaseConfig
{
    public array $rules = [
        'UserModel' => [
            'name'  => ['trim', 'norm_spaces', 'uppercase'],
            'phone' => ['trim', 'numbers_only'],
        ],
        // Other models sanitization rules can be added here:
        'TestModel' => [
            'phrase' => ['trim', 'norm_spaces', 'lowercase'],
        ],
    ];
}
