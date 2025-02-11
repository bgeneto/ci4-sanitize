# CI4 Sanitize Library

A CodeIgniter 4 (CI4) library that provides a flexible, rule-based data sanitization solution. This library includes the `Sanitize` class and the `SanitizableTrait` trait, which allow you to easily sanitize data in models and elsewhere in your application.

## Features

- **Predefined Rules:** Built-in rules such as `trim`, `uppercase`, `lowercase`, `capitalize`, `email`, `numbers_only`, and more.
- **Custom Rules:** Easily register global custom sanitization rules.
- **Flexible Rule Merging:** Merge config rules, model-specific rules, and dynamic runtime rules with priority.
- **Trait-Based Usage:** Use the `SanitizableTrait` in your models to automatically leverage sanitization before CRUD operations.
- **Static and Instance Methods:** Supports both static sanitization of arbitrary data and model-specific sanitization.

## Requirements

- PHP 8.0 or newer
- CodeIgniter 4

## Installation

1. You can clone the repository into your CodeIgniter 4 project and make a local Composer install.

    ```bash
    git clone https://github.com/bgeneto/ci4-sanitize.git
    ```

2. You can also config your current project's `composer.json` file like this to install remotely: 

    ```
        "require": {
            "codeigniter4/framework": "~4.6.0",
        ===>"bgeneto/ci4-sanitize": "dev-main"
        },
        ...
        "repositories": {
            "sanitize": {
                "type": "vcs",
                "url": "https://github.com/bgeneto/ci4-sanitize.git"
            }
        },
    ```

3. Then include the library in your project by updating your autoloader (not necessary if using CodeIgniter4 Autoloader).

## Usage

### Using the Trait in a Model

Include the `SanitizableTrait` in your model:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\SanitizableTrait;

class UserModel extends Model
{
    use SanitizableTrait;

    protected $table = 'users';
    protected $allowedFields = ['name', 'email', 'username'];

    protected function initialize(): void
    {
        parent::initialize();
        
        // append the sanitize callbacks before insert and update
        $this->addCallback('beforeInsert', ['sanitizeInsert']);
        $this->addCallback('beforeUpdate', ['sanitizeUpdate']);
        
        // set sanitization rules via config file
        $this->setSanitizationRules(\config(\Config\Sanitization::class)->rules[\class_basename($this)]);
    }
        // OR: set model-specific sanitization rules for this model
        $this->setSanitizationRules([
            'name'  => ['trim', 'capitalize', 'norm_spaces'],
            'email' => ['trim', 'email'],
            'username' => ['trim', 'lowercase']
        ]);
    }
}
```

When you also call the `sanitizeData()` method explicitly, the trait will automatically sanitize the data based on the defined rules:

```php
$data = [
    'data' => [
        'name'     => '  john doe  ',
        'email'    => ' JOHN.DOE@example.COM ',
        'username' => '  JohnDoe  '
    ]
];

$model = new UserModel();
$sanitizedData = $model->sanitizeData($data);
```

You can also set (or overwrite) a rule at runtime later like this:
```php
// Set rule later, at runtime (dynamic rule)
$model->addSanitizationRule('link', 'slug');
```

### Registering Global Custom Sanitization Rules

You can register a custom sanitization rule either directly through the `Sanitize` class or by using the trait’s wrapper method:

```php
use App\Traits\SanitizableTrait;

// Register a rule that appends '-custom' to the input
SanitizableTrait::registerCustomSanitizationRule('append_custom', function($value) {
    return $value . '-custom';
});
```

Then, include `append_custom` along with other rules:
```php
$this->setSanitizationRules([
    'name' => ['trim', 'append_custom']
]);
```

### Sanitizing Arbitrary Data

If you need to sanitize data outside of a model (e.g. in a controller), use the static method of the `Sanitize` class:

```php
use App\Libraries\Sanitize;

$data = ['name' => '  example name '];
$rules = [
    'name' => ['trim', 'capitalize']
];

$sanitizedData = Sanitizer::sanitizeDataArray($data, $rules);
```

## Configuration

Global sanitization rules can be defined in a custom configuration file (`Sanitization.php`). This file can be created after package installation via `php spark sanitize:publish` CLI command.  The library loads these rules automatically in the `Sanitize` constructor:

```php
<?php

namespace App\Config;

class Sanitization
{
    public $rules = [
        'UserModel' => [
            'name' => ['trim', 'capitalize'],
            'email' => ['trim', 'email']
        ]
    ];
}
```

Adjust the configuration to suit your application’s requirements.

## Contributing

Feel free to submit issues and pull requests to improve this library. When contributing, please adhere to the CodeIgniter coding guidelines and write relevant tests.

## License

Distributed under the MIT License. See LICENSE for more information.

## Acknowledgements

- CodeIgniter 4 Framework Community
