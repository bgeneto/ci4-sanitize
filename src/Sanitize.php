<?php

/**
 * Sanitize Class
 *
 * Provides data sanitization functionality.
 *
 * PHP version 8+
 *
 * @created    2025-02-10
 * @modified   2025-02-12
 * @version    1.0.3
 * @license    MIT
 */

declare(strict_types=1);

namespace Bgeneto\Sanitize;

class Sanitize
{
    /**
     * Configuration array to define default/global sanitization rules.
     *
     * @var array
     */
    protected $configRules = [];

    /**
     * Array to hold custom sanitization rules.
     *
     * @var array<string, callable>
     */
    private static array $customRules = [];

    public function __construct()
    {
        // Load default/global configuration from file
        $this->configRules = \config('Sanitization')->rules ?? [];
        self::registerRule('strip_tags_allowed', static fn ($value, array $allowedTags = []) => strip_tags($value, $allowedTags));
    }

    /**
     * Register a custom sanitization rule.
     *
     * @param string   $rule     Rule name
     * @param callable $callback Callback to execute for custom sanitization.
     */
    public static function registerRule(string $rule, callable $callback): void
    {
        self::$customRules[$rule] = $callback;
    }

    /**
     * Load and merge sanitization rules, prioritizing model-specific rules.
     *
     * @param string $modelName  The name of the model
     * @param array  $modelRules Model-specific rules defined in the model class
     *
     * @return array Merged rules for the model
     */
    public function loadRules(string $modelName, array $modelRules = []): array
    {
        $configModelRules = $this->configRules[$modelName] ?? [];

        return \array_replace_recursive($configModelRules, $modelRules);
    }

    /**
     * Sanitize data based on configured rules for a given model.
     *
     * @param string $modelName  The name of the model (e.g., 'UserModel')
     * @param array  $data       The data array to sanitize
     * @param array  $modelRules Optional: Model-specific rules (already merged if using trait)
     *
     * @return array Sanitized data
     */
    public function sanitize(string $modelName, array $data, array $modelRules = []): array
    {
        $mergedRules = $modelRules ?: $this->loadRules($modelName, []); // Use provided rules or load default

        if ($mergedRules === []) {
            return $data; // No rules defined, return data as is
        }

        $sanitizedData = [];

        foreach ($data as $field => $value) {
            if (isset($mergedRules[$field])) {
                $rules          = $mergedRules[$field];
                $sanitizedValue = $value;

                foreach ($rules as $rule) {
                    $sanitizedValue = self::applyRuleStatic($sanitizedValue, $rule);
                }
                $sanitizedData[$field] = $sanitizedValue;
            } else {
                $sanitizedData[$field] = $value; // No rule for this field, keep original value
            }
        }

        return $sanitizedData;
    }

    /**
     * Sanitize an arbitrary data array using provided rules (static method).
     * This method can be used outside of models, e.g., in controllers.
     *
     * @param array $data  The data array to sanitize.
     * @param array $rules An array of sanitization rules (field => [rule1, rule2, ...]).
     *
     * @return array Sanitized data.
     */
    public static function sanitizeDataArray(array $data, array $rules): array
    {
        if ($rules === []) {
            return $data; // No rules provided, return data as is
        }

        $sanitizedData = [];

        foreach ($data as $field => $value) {
            if (isset($rules[$field])) {
                $fieldRules     = $rules[$field];
                $sanitizedValue = $value;

                foreach ($fieldRules as $rule) {
                    $sanitizedValue = self::applyRuleStatic($sanitizedValue, $rule);
                }
                $sanitizedData[$field] = $sanitizedValue;
            } else {
                $sanitizedData[$field] = $value; // No rule for this field, keep original value
            }
        }

        return $sanitizedData;
    }

    /**
     * Apply a single sanitization rule to a value.
     *
     * @param mixed  $value The value to sanitize
     * @param string $rule  The sanitization rule (method name)
     *
     * @return mixed Sanitized value
     */
    protected function applyRule($value, string $rule)
    {
        return self::applyRuleStatic($value, $rule);
    }

    /**
     * Static version of applyRule to be used in static methods.
     *
     * @param mixed  $value The value to sanitize
     * @param string $rule  The sanitization rule (method name)
     *
     * @return mixed Sanitized value
     */
    protected static function applyRuleStatic($value, string $rule)
    {
        // Predefined rules
        switch ($rule) {
            case 'trim':
                return mb_trim($value);

            case 'lowercase':
                return \mb_strtolower($value);

            case 'uppercase':
                return \mb_strtoupper($value);

            case 'capitalize':
                return \ucwords($value);

            case 'numbers_only':
                return \preg_replace('/[^0-9]/u', '', $value);

            case 'email':
                return \filter_var($value, FILTER_SANITIZE_EMAIL);

            case 'float':
                return \filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            case 'int':
                return (int) \filter_var($value, FILTER_SANITIZE_NUMBER_INT);

            case 'htmlspecialchars':
                return \htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); // Added htmlspecialchars rule

            case 'norm_spaces':
                return mb_trim(preg_replace('/\s+/', ' ', $value));

            case 'slug':
                $value = \transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);

                return preg_replace('/\s+/u', '-', $value);

            case 'url':
                return \filter_var($value, FILTER_SANITIZE_URL);

            case 'strip_tags':
                return \strip_tags($value);

            case 'alphanumeric':
                return \preg_replace('/[^a-zA-Z0-9]/u', '', $value);

            default:
                // Check for a registered custom rule
                if (isset(self::$customRules[$rule])) {
                    // Check if the rule has parameters
                    if (str_contains($rule, ':')) {
                        [$ruleName, $params] = explode(':', $rule, 2);
                        $paramsArray         = explode(',', $params);

                        if (isset(self::$customRules[$ruleName])) {
                            return \call_user_func_array(self::$customRules[$ruleName], [$value, $paramsArray]);
                        }
                    }

                    return \call_user_func(self::$customRules[$rule], $value);
                }

                return $value; // Rule not found, return original value
        }
    }
}
