<?php

/**
 * Sanitize Class
 *
 * Provides data sanitization functionality.
 *
 * PHP version 8+
 *
 * @created    2025-02-10
 * @modified   2025-02-14
 * @version    1.1.0
 * @license    MIT
 */

declare(strict_types=1);

namespace Bgeneto\Sanitize;

use ReflectionClass;
use ReflectionMethod;

class Sanitizer
{
    /**
     * Configuration array to define default/global sanitization rules.
     */
    protected array $rules = [];

    /**
     * Array to hold custom sanitization rules.
     *
     * @var array<string, callable>
     */
    protected static array $customRules = [];

    /**
     * Array to hold dynamic sanitization rules.
     */
    protected array $dynamicRules = [];

    /**
     * Constructor.
     *
     * @param array $rules Optional configuration array.
     */
    public function __construct(array $rules = [])
    {
        // Load default/global configuration
        $this->rules = $rules;

        // Load custom rules from sanitization config file if they exist
        $sanitizationConfig = \config('Sanitization');
        $configClass        = $sanitizationConfig::class;
        $reflection         = new ReflectionClass($sanitizationConfig);
        $methods            = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

        foreach ($methods as $method) {
            if ($method->class === $configClass) {
                $ruleName = $method->getName();
                self::registerRule($ruleName, [$configClass, $ruleName]);
            }
        }
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
     * Add dynamic sanitization rules.
     */
    public function addRules(array $rules): void
    {
        foreach ($rules as $field => $newRules) {
            if (isset($this->dynamicRules[$field])) {
                $this->dynamicRules[$field] = \array_merge($this->dynamicRules[$field], $newRules);
            } else {
                $this->dynamicRules[$field] = $newRules;
            }
        }
    }

    /**
     * Gets merged sanitization rules, prioritizing dynamic rules.
     *
     * @return array Merged rules for the model
     */
    public function getRules(): array
    {
        return \array_replace_recursive($this->rules, self::$customRules, $this->dynamicRules);
    }

    /**
     * Sanitize data based on configured rules for a given model.
     *
     * @param array $data  The data array to sanitize
     * @param array $rules Optional: late rules
     *
     * @return array Sanitized data
     */
    public function sanitize(mixed $data, array $rules = []): array
    {
        $mergedRules = \array_replace_recursive($this->getRules(), $rules);

        if ($mergedRules === []) {
            // No rules defined, return data as is
            return $data;
        }
		
		// Check if the the value is an array
		if(is_array($data)){
			
			$sanitizedData = [];
			
			foreach ($data as $field => $value) {
				if (isset($mergedRules[$field])) {
					$rules          = $mergedRules[$field];
					$sanitizedValue = $value;

					foreach ($rules as $rule) {
						$sanitizedValue = self::applyRule($sanitizedValue, $rule);
					}
					$sanitizedData[$field] = $sanitizedValue;
				} else {
					$sanitizedData[$field] = $value; // No rule for this field, keep original value
				}
			}
			
			return $sanitizedData;
		}
		// Single value
		else{
			
			$sanitizedValue = $value;

			foreach ($rules as $rule) {
				$sanitizedValue = self::applyRule($sanitizedValue, $rule);
			}
			
			return $sanitizedValue;
		}
    }

    /**
     * Applies a sanitization rule to a value.
     *
     * @param mixed $value The value to sanitize.
     *
     * @return mixed The sanitized value.
     */
    public static function applyRule(mixed $value, string $rule): mixed
    {
        // Check if the rule has parameters
        if (\str_contains($rule, ':')) {
            return self::applyRuleWithParams($value, $rule);
        }

		// Check if the the value is not null
		if($value !== NULL && $value != '') {
			
			// Handle predefined rules without parameters
			return match ($rule) {
				'trim'               => \mb_trim($value),
				'lowercase'          => \mb_strtolower($value),
				'uppercase'          => \mb_strtoupper($value),
				'capitalize'         => \ucwords(\mb_strtolower($value)),
				'numbers_only'       => \preg_replace('/[^0-9]/u', '', (string) $value),
				'email'              => \filter_var($value, FILTER_SANITIZE_EMAIL),
				'float'              => \filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
				'int'                => (int) \filter_var($value, FILTER_SANITIZE_NUMBER_INT),
				'htmlspecialchars'   => \htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
				'norm_spaces'        => \mb_trim(\preg_replace('/\s+/u', ' ', (string) $value)),
				'slug'               => self::generateSlug($value),
				'url'                => \filter_var($value, FILTER_SANITIZE_URL),
				'strip_tags'         => \strip_tags($value),
				'strip_tags_allowed' => self::stripTagsAllowed($value),
				'alphanumeric'       => \preg_replace('/[^a-zA-Z0-9]/u', '', (string) $value),
				default              => self::applyCustomRule($value, $rule),
			};
			
		} else {
			return $value;
		}       
    }

    /**
     * Applies a rule with parameters.
     */
    private static function applyRuleWithParams(mixed $value, string $rule): mixed
    {
        [$ruleName, $params] = \explode(':', $rule, 2);
        $paramsArray         = \explode(',', $params);

        return match ($ruleName) {
            'strip_tags_allowed' => self::stripTagsAllowed($value, $paramsArray),
            default              => self::applyCustomRule($value, $ruleName, $paramsArray), // Custom rules can also have parameters
        };
    }

    /**
     * Applies a custom sanitization rule.
     *
     * @param mixed  $value  The value to sanitize.
     * @param string $rule   The custom rule name.
     * @param string $rule   The custom rule name.
     * @param array  $params The parameters for the custom rule.
     *
     * @return mixed The sanitized value.
     */
    private static function applyCustomRule(mixed $value, string $rule, array $params = []): mixed
    {
        if (isset(self::$customRules[$rule])) {
            return \call_user_func_array(self::$customRules[$rule], [$value, $params]);
        }

        return $value; // Rule not found, return original value
    }

    /**
     * Generate a URL-friendly slug.
     *
     * @param mixed $value The value to slugify.
     *
     * @return string The generated slug.
     */
    private static function generateSlug(mixed $value): string
    {
        if (! \is_string($value)) {
            return '';
        }

        $value = \transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
        $value = \preg_replace('/[^a-z0-9]+/u', '-', $value);

        return \trim($value, '-');
    }

    /**
     * Strip tags from a string, allowing specified tags.
     *
     * @param mixed $value       The value to strip tags from.
     * @param array $allowedTags The allowed tags (defaults to empty array).
     *
     * @return string The string with tags stripped.
     */
    private static function stripTagsAllowed(mixed $value, array $allowedTags = []): string
    {
        if (! \is_string($value)) {
            return '';
        }

        return \strip_tags($value, $allowedTags);
    }

    /**
     * Resets the custom rules array.
     */
    public static function resetRules(): void
    {
        self::$customRules = [];
    }
}
