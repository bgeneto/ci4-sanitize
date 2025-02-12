<?php

namespace Bgeneto\Sanitize\Tests\Support;

use Bgeneto\Sanitize\Sanitize;
use Bgeneto\Sanitize\Traits\SanitizableTrait;
use CodeIgniter\Model;

class UserModel extends Model
{
    use SanitizableTrait;

    protected array $dynamicSanitizationRules = [];
    public array $allowedCallbacks = [
        'beforeInsert',
    ];

    public function getSanitizationRules(): array
    {
        return $this->sanitizationRules;
    }

    public function getDynamicSanitizationRules(): array
    {
        return $this->dynamicSanitizationRules;
    }

    public function loadRules(string $modelName, array $modelRules = []): array
    {
        $config = new \Bgeneto\Sanitize\Config\Sanitization();
        $config->rules = [
            'UserModel' => [
                'name' => ['trim', 'capitalize'],
            ],
        ];

        $configModelRules = $config->rules[$modelName] ?? [];

        return \array_replace_recursive($configModelRules, $modelRules);
    }
}
