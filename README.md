# ci4-sanitize

[![Latest Stable Version](https://poser.pugx.org/bgeneto/ci4-sanitize/v/stable)](https://packagist.org/packages/bgeneto/ci4-sanitize)
[![Total Downloads](https://poser.pugx.org/bgeneto/ci4-sanitize/downloads)](https://packagist.org/packages/bgeneto/ci4-sanitize)
[![License](https://poser.pugx.org/bgeneto/ci4-sanitize/license)](https://packagist.org/packages/bgeneto/ci4-sanitize)

`ci4-sanitize` is a PHP library for CodeIgniter 4 that provides data sanitization functionality. It allows you to easily sanitize user input and other data using a set of predefined rules or custom rules. This helps prevent security vulnerabilities like cross-site scripting (XSS) and SQL injection.



## Quick Start

1. Install with Composer: `composer require bgeneto/ci4-sanitize`

2. Publish the config file: `php spark sanitize:publish`

3. Set up your model: 

   ```php
   <?php
   
   namespace App\Models;
   
   use CodeIgniter\Model;
   use Bgeneto\Sanitize\Traits\SanitizableTrait;
   
   class Customer extends Model
   {
       use SanitizableTrait;
   
       protected $table         = 'customers';
       protected $allowedFields = ['name', 'email', 'phone'];
   
       protected function initialize(): void
       {
           parent::initialize();
           $this->setSanitizationRules(['name' => ['uppercase'], 'email' => ['trim']]);
           $this->setSanitizationCallbacks(['beforeInsert', 'beforeUpdate']);
       }
   }
   ```



## Installation

#### Composer + Packagist

```bash
composer require bgeneto/ci4-sanitize
```

#### Composer + GitHub repo:

Just setup a repository like this in your project's `composer.json` file:

```json
{
    "require": {
        "your-project/other-dependencies": "...",
        "bgeneto/ci4-sanitize": "dev-main"
    },

    "repositories": {
        "sanitize": {
            "type": "vcs",
            "url": "https://github.com/bgeneto/ci4-sanitize.git"
        }
    }
}
```

#### Composer + Local repo:

```bash
git clone https://github.com/bgeneto/ci4-sanitize.git /path/to/your/local/ci4-sanitize
```

Now edit your `composer.json` file and add a new `path` repository:

```json
{
    "require": {
        "your-project/other-dependencies": "...",
        "bgeneto/ci4-secrets": "dev-main"
    },

    "repositories": {
        "sanitize": {
            "type": "path",
            "url": "/path/to/your/local/ci4-sanitize"
        }
    }
}
```

Publish the configuration file after installing:

```bash
php spark sanitize:publish
```



## Configuration

The package comes with a configuration file (`app/Config/Sanitization.php`) where you can define default sanitization rules for your models.

```php
<?php

namespace Bgeneto\Sanitize\Config;

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
            'phrase' => ['trim', 'norm_spaces', 'capitalize'],
        ],
    ];
}
```

You can also add custom (new) rules to this config file:

```php
class Sanitization extends BaseConfig
{
    public array $rules = [
        'UserModel' => [
            'name'  => ['trim', 'alpha_only'],  // see new rule below
            'phone' => ['trim', 'numbers_only'],
        ],
    ];

    // new custom rule:
    public static function alpha_only(string $value): string
    {
        return preg_replace('/[^\p{L}]/u', '', $value);
    }
}
```



## Usage

### Sanitizer Class

You can use the `Sanitizer` class directly to sanitize

```php
use Bgeneto\Sanitize\Sanitizer;

$rules = [
    'username' => ['trim', 'lowercase'],
    'email'    => ['trim', 'email'],
];

$sanitizer = new Sanitizer($rules);

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
You can also apply rules at the time of sanitization, which will override any previously defined rules:
```php
$sanitizer = new Sanitizer(['name' => ['trim']]);
$data = ['name' => '  John Doe  ', 'email' => ' test@example.com '];
$lateRules = ['email' => ['trim', 'email']];
$sanitizedData = $sanitizer->sanitize($data, $lateRules);
// Result: ['name' => 'John Doe', 'email' => 'test@example.com']
```

### Sanitizer Class Static Usage

The `Sanitizer` class provides several static methods for convenient sanitization:

*   **`Sanitizer::registerRule(string $rule, callable $callback)`:** Registers a custom sanitization rule.
*   **`Sanitizer::applyRule(mixed $value, string $rule)`:** Applies a sanitization rule (built-in or custom) to a value.
*   **`Sanitizer::resetRules()`:** Resets all custom rules.

**Registering and Using Custom Rules:**

```php
use Bgeneto\Sanitize\Sanitizer;

// Define a custom rule to append text to a string
Sanitizer::registerRule('append_text', function ($value, $params = []) {
    $suffix = $params[0] ?? '_appended';
    return $value . $suffix;
});

// Apply the custom rule
$sanitized = Sanitizer::applyRule('My String', 'append_text:!!!'); // $sanitized = "My String!!!"

// Another custom rule example: convert a string to a slug
Sanitizer::registerRule('my_slug', function ($value) {
    $value = \transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
    $value = \preg_replace('/[^a-z0-9]+/u', '-', $value);
    return \trim($value, '-');
});

$slug = Sanitizer::applyRule('My Awesome Title', 'my_slug'); // $slug = "my-awesome-title"

Sanitizer::resetRules(); // Removes all custom rules
```

**Applying Built-in Rules:**

```php
use Bgeneto\Sanitize\Sanitizer;

$trimmed = Sanitizer::applyRule('  Hello World  ', 'trim'); // $trimmed = "Hello World"
$lowercase = Sanitizer::applyRule('Hello World', 'lowercase'); // $lowercase = "hello world"
$numbers = Sanitizer::applyRule('Phone: 123-456-7890', 'numbers_only'); // $numbers = "1234567890"
$stripped = Sanitizer::applyRule('<p>Hello</p><a>World</a>', 'strip_tags_allowed:<p>'); // $stripped = "<p>Hello</p>World"

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
        // We just need to configure the desired sanitization callbacks 
        $this->setSanitizationCallbacks(['beforeInsert', 'beforeUpdate']);
    }
}

```

The trait will use the rules defined in the `Sanitization` config file for the `UserModel`.  You can also add rules dynamically:

```php
$model->setSanitizationRules(['name' => ['capitalize']]);
```

You can retrieve the currently applied sanitization rules using `getSanitizationRules()`:

```php
$rules = $userModel->getSanitizationRules(); // Get all rules
$nameRules = $userModel->getSanitizationRules('name'); // Get rules for the 'name' field
```

You can also sanitize arbitrary data directly using the trait:

```php
$data = [
    'name' => '  john doe  ',
    'email' => '  test@example.com  ',
];

$sanitizedData = $userModel->sanitizeData($data);
```

**Allowed Callbacks:**

The `SanitizableTrait` allows you to specify which model events should trigger sanitization. You can set these using the `setSanitizationCallbacks()` method. The allowed callbacks are:

*   `beforeInsert`
*   `beforeUpdate`
*   `beforeFind`
*   `beforeDelete`
*   `beforeInsertBatch`
*   `beforeUpdateBatch`

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
Custom rules can be used both globally (with `Sanitizer::registerRule()`) and within models that use the `SanitizableTrait`. The examples shown in the "Sanitizer Class Static Usage" section demonstrate how to define and use custom rules.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
