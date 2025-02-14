# ci4-sanitize

[![Latest Stable Version](https://poser.pugx.org/bgeneto/ci4-sanitize/v/stable)](https://packagist.org/packages/bgeneto/ci4-sanitize)
[![Total Downloads](https://poser.pugx.org/bgeneto/ci4-sanitize/downloads)](https://packagist.org/packages/bgeneto/ci4-sanitize)
[![License](https://poser.pugx.org/bgeneto/ci4-sanitize/license)](https://packagist.org/packages/bgeneto/ci4-sanitize)

`ci4-sanitize` is a PHP library for CodeIgniter 4 that provides data sanitization functionality. It allows you to easily sanitize user input and other data using a set of predefined rules or custom rules. This helps prevent security vulnerabilities like cross-site scripting (XSS) and SQL injection.

## Installation

Install the package using Composer:

```bash
composer require bgeneto/ci4-sanitize
```

Then, publish the configuration file:

```bash
php spark sanitize:publish
```

## Configuration

The package comes with a configuration file (`app/Config/Sanitization.php`) where you can define default sanitization rules for your models.

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Sanitization extends BaseConfig
{
    public array $rules = [
        'UserModel' => [
            'name'  => ['trim', 'norm_spaces', 'uppercase'],
            'phone' => ['trim', 'numbers_only'],
        ],
        // Add rules for other models here
    ];
}
```

## Usage

### Sanitizer Class

You can use the `Sanitizer` class directly to sanitize

```php
use Bgeneto\Sanitize\Sanitizer;

$config = [
    'username' => ['trim', 'lowercase'],
    'email'    => ['trim', 'email'],
];

$sanitizer = new Sanitizer($config);

$data = [
    'username' => '  MyUser  ',
    'email'    => '  test@example.com  ',
    'age'      => '30',
];

$sanitizedData = $sanitizer->sanitize($data);

// Output:
// [
//     'username' => 'myuser',
//     'email'    => 'test@example.com',
//     'age'      => '30', // No rule for 'age', so it remains unchanged
// ]
```
You can also add rules dynamically:
```php
$sanitizer->addRules(['username' => ['alphanumeric']]);
$sanitizedData = $sanitizer->sanitize($data);
```

The `Sanitizer` class provides several static methods for convenient, global access to sanitization functionality.  This is particularly useful for registering custom rules and applying rules directly without needing a class instance.

**Registering Custom Rules**

```php
use Bgeneto\Sanitize\Sanitizer;

// Define a custom rule to convert a string to a slug
Sanitizer::registerRule('my_slug', function ($value) {
    $value = \transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
    $value = \preg_replace('/[^a-z0-9]+/u', '-', $value);
    return \trim($value, '-');
});

// You can now use 'my_slug' in your sanitization rules anywhere in your application.
```

**Applying Rules Directly**

You can use `Sanitizer::applyRuleStatic()` (and related methods like `applyRuleWithParams()`, `applyCustomRule()`, `generateSlug()`, and `stripTagsAllowed()`) to directly sanitize values without creating a `Sanitizer` object:

```php
use Bgeneto\Sanitize\Sanitizer;

$cleanedValue = Sanitizer::applyRuleStatic('  Hello World!  ', 'trim'); // $cleanedValue = "Hello World!"

// With custom rules and parameters
$appended = Sanitizer::applyRuleStatic('MyValue', 'append_text:!'); // $appended = "MyValue!" (assuming append_text is registered)

// Applying a built-in rule with parameters
$stripped = Sanitizer::applyRuleStatic('<p>Hello</p><a>World</a>', 'strip_tags_allowed:<p>'); // $stripped = "<p>Hello</p>World"
```

These static methods provide a quick and easy way to leverage the sanitization capabilities without the need for object instantiation, making the library more flexible and convenient to use.

**Resetting Custom Rules**

The `Sanitizer::resetRules()` method allows you to clear all previously registered custom rules.  This is useful if you need to ensure a clean slate for your sanitization rules, particularly in testing scenarios or when dealing with different contexts that require distinct sets of custom rules.

```php
use Bgeneto\Sanitize\Sanitizer;

Sanitizer::registerRule('my_rule', function ($value) { return $value . '_modified'; });

// ... later ...

Sanitizer::resetRules(); // All custom rules are now removed.

Sanitizer::registerRule('new_rule', function ($value) { return $value . '_new'; });

```

### Sanitizable Trait

The `SanitizableTrait` is designed for use with CodeIgniter 4 models. It automatically applies sanitization rules before inserting or updating data.

```php
<?php

namespace App\Models;

use CodeIgniter\Model;
use Bgeneto\Sanitize\Traits\SanitizableTrait;

class UserModel extends Model
{
    use SanitizableTrait;

    protected $table         = 'users';
    protected $allowedFields = ['name', 'email', 'phone'];

	protected function initialize(): void
    {
        parent::initialize();
        // we just need to set the desired callbacks here
        $this->setSanitizationCallbacks(['beforeInsert', 'beforeUpdate']);
    }
}
```

The trait will use the rules defined in the `Sanitization` config file for the `UserModel`. You can also add rules dynamically:

```php
$userModel = new UserModel();
$userModel->setSanitizationRules(['name' => ['capitalize']]);
```

### Built-in Rules

The following built-in rules are available:

*   `trim`: Removes whitespace from the beginning and end of a string.
*   `lowercase`: Converts a string to lowercase.
*   `uppercase`: Converts a string to uppercase.
*   `capitalize`: Capitalizes the first character of each word in a string.
*   `numbers_only`: Removes all non-numeric characters from a string.
*   `email`: Sanitizes an email address.
*   `float`: Sanitizes a floating-point number.
*   `int`: Sanitizes an integer.
*   `htmlspecialchars`: Converts special characters to HTML entities.
*   `norm_spaces`: Normalizes whitespace in a string (removes multiple spaces).
*   `slug`: Generates a URL-friendly slug.
*   `url`: Sanitizes a URL.
*   `strip_tags`: Strips HTML and PHP tags from a string.
*   `strip_tags_allowed`: Strips HTML and PHP tags, allowing specified tags (e.g., `strip_tags_allowed:<p>,<a>`).
*   `alphanumeric`: Removes all non-alphanumeric characters from a string.

### Custom Rules

You can register custom sanitization rules using the `registerRule` method:

```php
use Bgeneto\Sanitize\Sanitizer;

// Globally (for both Sanitizer and SanitizableTrait)
Sanitizer::registerRule('append_text', function ($value, $params = []) {
     if (!empty($params)) {
        return $value . $params[0];
     }
    return $value . '_appended';
});

// Using the Sanitizer class
$sanitizer = new Sanitizer();
$data = ['field' => 'test'];
$rules = ['field' => ['append_text:!!!']];
$sanitizedData = $sanitizer->sanitize($data, $rules); // Output: ['field' => 'test!!!']

// Or, within a model using the SanitizableTrait:
$userModel = new \App\Models\UserModel(); // Assuming you have a UserModel
$data = ['name' => 'John Doe'];
$userModel->setSanitizationRules(['name' => ['prepend_text']]);
$sanitizedData = $userModel->sanitizeData($data); // ['name' => 'prefix_John Doe']

```

### Applying Late Rules
You can also apply rules at the time of sanitization, which will override any previously defined rules:
```php
$sanitizer = new Sanitizer(['name' => ['trim']]);
$data = ['name' => '  John Doe  ', 'email' => ' test@example.com '];
$lateRules = ['email' => ['trim', 'email']];
$sanitizedData = $sanitizer->sanitize($data, $lateRules);
// Result: ['name' => 'John Doe', 'email' => 'test@example.com']
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
