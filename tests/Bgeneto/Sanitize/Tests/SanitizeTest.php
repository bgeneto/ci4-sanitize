<?php

namespace Bgeneto\Sanitize\Tests;

use Bgeneto\Sanitize\Sanitize;
use Bgeneto\Sanitize\Tests\Support\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class SanitizeTest extends CIUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Clean up custom rules before each test
        $reflection = new \ReflectionClass(Sanitize::class);
        $property   = $reflection->getProperty('customRules');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function testTrimRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['name' => '  John Doe  '];
        $rules    = ['name' => ['trim']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('John Doe', $result['name']);
    }

    public function testLowercaseRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['email' => 'John.Doe@Example.COM'];
        $rules    = ['email' => ['lowercase']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('john.doe@example.com', $result['email']);
    }

    public function testUppercaseRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['name' => 'john doe'];
        $rules    = ['name' => ['uppercase']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('JOHN DOE', $result['name']);
    }

    public function testCapitalizeRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['name' => 'john doe'];
        $rules    = ['name' => ['capitalize']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('John Doe', $result['name']);
    }

    public function testNumbersOnlyRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['phone' => '123-456-7890'];
        $rules    = ['phone' => ['numbers_only']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('1234567890', $result['phone']);
    }

    public function testEmailRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['email' => 'invalid-email'];
        $rules    = ['email' => ['email']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('invalidemail', $result['email']);
    }

    public function testFloatRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['price' => '1,234.56'];
        $rules    = ['price' => ['float']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame(1234.56, $result['price']);
    }

    public function testIntRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['quantity' => '10 units'];
        $rules    = ['quantity' => ['int']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame(10, $result['quantity']);
    }

    public function testHtmlspecialcharsRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['description' => '<p>Hello & Goodbye</p>'];
        $rules    = ['description' => ['htmlspecialchars']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('&lt;p&gt;Hello &amp; Goodbye&lt;/p&gt;', $result['description']);
    }

    public function testNormSpacesRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['text' => "  Multiple   \t spaces  "];
        $rules    = ['text' => ['norm_spaces']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('Multiple spaces', $result['text']);
    }

    public function testSlugRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['title' => 'This is a Title with Spaces and Accents'];
        $rules    = ['title' => ['slug']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('this-is-a-title-with-spaces-and-accents', $result['title']);
    }

    public function testCustomRule(): void
    {
        Sanitize::registerRule('append_custom', function ($value) {
            return $value . '-custom';
        });

        $sanitize = new Sanitize();
        $data     = ['name' => 'John Doe'];
        $rules    = ['name' => ['append_custom']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('John Doe-custom', $result['name']);
    }

    public function testMultipleRules(): void
    {
        $sanitize = new Sanitize();
        $data     = ['name' => '  john doe  '];
        $rules    = ['name' => ['trim', 'capitalize']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('John Doe', $result['name']);
    }

    public function testNoRules(): void
    {
        $sanitize = new Sanitize();
        $data     = ['name' => 'John Doe'];
        $rules    = [];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame($data, $result);
    }

    public function testNoRuleForField(): void
    {
        $sanitize = new Sanitize();
        $data     = ['name' => ' John Doe ', 'email' => 'john@example.com'];
        $rules    = ['name' => ['trim']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('John Doe', $result['name']);
        $this->assertSame('john@example.com', $result['email']);
    }

    public function testSanitizeDataArray(): void
    {
        $data  = ['name' => '  john doe  ', 'email' => ' JOHN.DOE@example.COM '];
        $rules = ['name' => ['trim', 'capitalize'], 'email' => ['trim', 'lowercase']];

        $result = Sanitize::sanitizeDataArray($data, $rules);

        $this->assertSame('John Doe', $result['name']);
        $this->assertSame('john.doe@example.com', $result['email']);
    }

    public function testConfigRulesLoaded(): void
    {
        // Set up the configuration
        $config           = new \Bgeneto\Sanitize\Config\Sanitization();
        $config->rules    = [
            'UserModel' => [
                'name' => ['trim', 'capitalize'],
            ],
        ];
        \Config\Services::injectMock('sanitization', $config);

        $sanitize = new Sanitize();
        $data     = ['name' => '  john doe  ', 'email' => ' JOHN.DOE@example.COM '];
        $result   = $sanitize->sanitize('UserModel', $data);

        $this->assertSame('John Doe', $result['name']);
        $this->assertSame(' JOHN.DOE@example.COM ', $result['email']); // Email should not be touched
    }

    public function testModelLoadRules(): void
    {
        // Set up the configuration
        $config           = new \Bgeneto\Sanitize\Config\Sanitization();
        $config->rules    = [
            'UserModel' => [
                'name' => ['trim', 'capitalize'],
            ],
        ];
        \Config\Services::injectMock('sanitization', $config);

        $model = new UserModel();
        $rules = $model->loadRules('UserModel', ['email' => ['trim', 'lowercase']]);

        $expected = [
            'name'  => ['trim', 'capitalize'],
            'email' => ['trim', 'lowercase'],
        ];
        $this->assertSame($expected, $rules);
    }
}