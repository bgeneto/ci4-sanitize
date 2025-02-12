<?php

namespace Bgeneto\Sanitize\Tests\Support;

use Bgeneto\Sanitize\Traits\SanitizableTrait;
use CodeIgniter\Model;

class TestUserModel extends Model
{
    use SanitizableTrait;

    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $allowedFields = ['name', 'email'];

    protected array $dynamicSanitizationRules = [];

    protected $beforeInsert = [];
    protected $beforeUpdate = [];

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
            'TestUserModel' => [
                'name' => ['trim', 'capitalize'],
            ],
        ];

        $configModelRules = $config->rules[$modelName] ?? [];

        return \array_replace_recursive($configModelRules, $modelRules);
    }

    public function testCallbackMethod(array $data): array
    {
        if (isset($data['data']['name'])) {
            $data['data']['name'] = 'Test Callback: ' . $data['data']['name'];
        }

        return $data;
    }
}
