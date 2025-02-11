<?php

/**
 * Sanitizable Trait
 *
 * Provides data sanitization functionality for models.
 *
 * PHP version 8+
 *
 * @created    2025-02-10
 * @modified   2025-02-11
 * @version    1.0.1
 * @license    MIT
 */

declare(strict_types=1);

namespace Bgeneto\Sanitize\Traits;

use Bgeneto\Sanitize\Sanitize;

trait SanitizableTrait
{
    /**
     * Internal storage for model-specific sanitization rules.
     *
     * @var array
     */
    protected $sanitizationRules = [];

    /**
     * Dynamically added sanitization rules at runtime.
     * These rules have the highest priority and will override both model and config rules.
     *
     * @var array
     */
    protected $dynamicSanitizationRules = [];

    /**
     * Allowed callbacks for the model.
     */
    protected array $allowedCallbacks = [
        'beforeInsert',
        'afterInsert',
        'beforeUpdate',
        'afterUpdate',
        'beforeFind',
        'afterFind',
        'beforeDelete',
        'afterDelete',
        'beforeInsertBatch',
        'afterInsertBatch',
        'beforeUpdateBatch',
        'afterUpdateBatch',
    ];

    /**
     * Register a global custom sanitization rule.
     * This is a static wrapper around Sanitize::registerRule()
     *
     * @param string   $rule     Rule name.
     * @param callable $callback Callback to execute for custom sanitization.
     */
    public static function registerCustomSanitizationRule(string $rule, callable $callback): void
    {
        Sanitize::registerRule($rule, $callback);
    }

    /**
     * Set model-specific sanitization rules.
     *
     * @param array $rules An array of sanitization rules (field => [rule1, rule2, ...]).
     */
    protected function setSanitizationRules(array $rules): void
    {
        $this->sanitizationRules = $rules;
    }

    /**
     * Dynamically add a custom sanitization rule for a specific field at runtime.
     *
     * @param string       $field The field name.
     * @param array|string $rule  The sanitization rule(s) to apply (string or array of strings).
     */
    protected function addSanitizationRule(string $field, $rule): void
    {
        if (! is_array($rule)) {
            $rule = [$rule]; // Ensure rule is always an array for consistency
        }
        $this->dynamicSanitizationRules[$field] = $rule;
    }

    public function sanitizeData(array $data): array
    {
        $sanitizer = new Sanitize();
        $modelName = class_basename($this);
        $allRules  = $this->loadAllRules($modelName);

        return $sanitizer->sanitize($modelName, $data, $allRules);
    }

    /**
     * Loads and merges all sanitization rules: dynamic, model-specific, and config.
     * Dynamic rules have the highest priority.
     *
     * @param string $modelName The name of the model.
     *
     * @return array Merged rules.
     */
    protected function loadAllRules(string $modelName): array
    {
        $sanitizer   = new Sanitize();
        $configRules = $sanitizer->loadRules($modelName, $this->sanitizationRules);

        return array_replace_recursive($configRules, $this->dynamicSanitizationRules);
    }

    /**
     * Executes the sanitization callback if allowed by the model.
     *
     * @param array $data The data array to sanitize.
     *
     * @return array The sanitized data array.
     */
    private function sanitizeCallback(array $data): array
    {
        if (isset($data['data']) && is_array($data['data']) && $this->allowCallbacks) {
            $data['data'] = $this->sanitizeData($data['data']);
        }

        return $data;
    }

    /**
     * Sanitizes data before an update operation.
     *
     * @param array $data The data array to sanitize.
     *
     * @return array The sanitized data array.
     */
    public function sanitizeUpdate(array $data): array
    {
        return $this->sanitizeCallback($data);
    }

    /**
     * Sanitizes data before an insert operation.
     *
     * @param array $data The data array to sanitize.
     *
     * @return array The sanitized data array.
     */
    public function sanitizeInsert(array $data): array
    {
        return $this->sanitizeCallback($data);
    }

    /**
     * Adds a callback to the model.
     *
     * @param string       $callback The name of the callback to add.
     * @param array|string $values   The values to merge with the existing callbacks.
     *
     * @throws \RuntimeException If the specified callback is not allowed.
     */
    public function addCallback(string $callback, array|string $values): void
    {
        if (! in_array($callback, $this->allowedCallbacks, true)) {
            throw new \RuntimeException('Invalid callback: ' . $callback);
        }

        if (! is_array($values)) {
            $values = [$values];
        }

        $this->{$callback} = array_merge($this->{$callback} ?? [], $values);
    }
}
