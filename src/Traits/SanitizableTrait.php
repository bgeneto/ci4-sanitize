<?php

/**
 * Sanitizable Trait
 *
 * Provides data sanitization functionality for models.
 *
 * PHP version 8+
 *
 * @created    2025-02-10
 * @modified   2025-02-14
 * @version    1.1.0
 * @license    MIT
 */

declare(strict_types=1);

namespace Bgeneto\Sanitize\Traits;

use Bgeneto\Sanitize\Sanitizer;
use RuntimeException;

trait SanitizableTrait
{
    /**
     * Sanitizer instance.
     *
     * @var Sanitizer|null $sanitizer
     */
    protected ?Sanitizer $sanitizer = null;

    /**
     * Internal storage for model-specific sanitization rules.
     */
    protected array $sanitizationRules = [];

    /**
     * Allowed callbacks for the model.
     */
    private array $allowedCallbacks = [
        'beforeInsert',
        'beforeUpdate',
        'beforeFind',
        'beforeDelete',
        'beforeInsertBatch',
        'beforeUpdateBatch',
    ];

    /**
     * Enabled callbacks for the model.
     */
    private array $enabledCallbacks = [];

    /**
     * Ensures that the Sanitizer is initialized.
     */
    private function ensureSanitizerInitialized(): void
    {
        if ($this->sanitizer === null) {
            $this->initializeSanitizer();
        }
    }

    /**
     * Initializes the sanitizer and adds enabled callbacks.
     */
    protected function initializeSanitizer(): void
    {
        // Get default rules from sanitization config file
        $this->sanitizationRules = $this->getSanitizationConfigRules();

        // Initialize the sanitizer
        $this->sanitizer = new Sanitizer($this->sanitizationRules);
    }

    /**
     * Gets the default sanitization rules from the config file.
     *
     * @return array The sanitization rules.
     */
    private function getSanitizationConfigRules(): array
    {
        $modelName = \class_basename($this);
        $config    = \config('Sanitization');

        return $config->rules[$modelName] ?? [];
    }

    /**
     * Register a global custom sanitization rule.
     * This is a static wrapper around Sanitize::registerRule()
     *
     * @param string   $rule     Rule name.
     * @param callable $callback Callback to execute for custom sanitization.
     */
    public static function registerSanitizationRule(string $rule, callable $callback): void
    {
        Sanitizer::registerRule($rule, $callback);
    }

    /**
     * Dynamically add a new sanitization rule(s).
     *
     * @param array $rules The sanitization rule(s) to apply
     */
    public function setSanitizationRules(array $rules): void
    {
        $this->ensureSanitizerInitialized();
        $this->sanitizer->addRules($rules);
    }

    public function sanitizeData(array $data): array
    {
        $this->ensureSanitizerInitialized();

        return $this->sanitizer->sanitize($data);
    }

    /**
     * Gets all sanitization rules via Sanitize::loadRules().
     *
     * @param string      $field The field to get rules for.
     * @param string|null $field The field to get rules for.
     *
     * @return array Merged rules.
     * @return array Merged rules.
     */
    public function getSanitizationRules(?string $field = null): array
    {
        $this->ensureSanitizerInitialized();
        $rules = $this->sanitizer->getRules();

        return $field ? ($rules[$field] ?? []) : $rules;
    }

    /**
     * Executes the sanitization callback if allowed by the model.
     *
     * @param array $data The data array to sanitize.
     *
     * @return array The sanitized data array.
     */
    protected function sanitizeCallback(array $data): array
    {
        if (isset($data['data']) && \is_array($data['data']) && $this->allowCallbacks) {
            $data['data'] = $this->sanitizeData($data['data']);
        }

        return $data;
    }

    /**
     * Adds all enabled callbacks to the model.
     */
    private function addEnabledCallbacks(): void
    {
        $this->ensureSanitizerInitialized();

        foreach ($this->enabledCallbacks as $callback) {
            $this->addCallback($callback, 'sanitizeCallback');
        }
    }

    /**
     * Adds a callback to the model.
     *
     * @param string       $callback The name of the callback to add.
     * @param array|string $values   The values to merge with the existing callbacks.
     *
     * @throws RuntimeException If the specified callback is not allowed.
     */
    private function addCallback(string $callback, array|string $values): void
    {
        if (! \is_array($values)) {
            $values = [$values];
        }

        $this->{$callback} = \array_merge($this->{$callback} ?? [], $values);
    }

    /**
     * Enables the specified callbacks for the model.
     *
     * @param array $callbacks The callbacks to enable.
     */
    public function setSanitizationCallbacks(array $callbacks): void
    {
        // Checks if the callback is allowed and trigger exception if not
        foreach ($callbacks as $callback) {
            if (! \in_array($callback, $this->allowedCallbacks, true)) {
                throw new RuntimeException('Invalid (or not allowed) callback: ' . $callback);
            }
        }
        $this->enabledCallbacks = $callbacks;
        $this->addEnabledCallbacks();
    }
}
