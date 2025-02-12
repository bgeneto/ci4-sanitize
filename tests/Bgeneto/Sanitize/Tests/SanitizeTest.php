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

        $this->assertSame('invalid-email', $result['email']);
    }

    public function testFloatRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['price' => '1,234.56'];
        $rules    = ['price' => ['float']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertEqualsWithDelta(1234.56, $result['price'], 2**(-6));
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

    public function testUrlRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['website' => 'https://www.example.com/path?query=string#fragment'];
        $rules    = ['website' => ['url']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);
        $this->assertSame('https://www.example.com/path?query=string#fragment', $result['website']);
    }

    public function testStripTagsRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['html' => '<p>This is <b>bold</b>.</p>'];
        $rules    = ['html' => ['strip_tags']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);
        $this->assertSame('This is bold.', $result['html']);
    }

    public function testStripTagsAllowedRule(): void
    {
        $sanitize = new Sanitize();
        $data = ['html' => '<p>This is <b>bold</b> and <a href="#">a link</a>.</p>'];
        $rules = ['html' => ['strip_tags_allowed:<p>,<a>']];
        $result = $sanitize->sanitize('TestModel', $data, $rules);

        $this->assertSame('<p>This is <b>bold</b> and <a href="#">a link</a>.</p>', $result['html']);


    }

    public function testAlphanumericRule(): void
    {
        $sanitize = new Sanitize();
        $data     = ['username' => 'user_name-123.'];
        $rules    = ['username' => ['alphanumeric']];
        $result   = $sanitize->sanitize('TestModel', $data, $rules);
        $this->assertSame('username123', $result['username']);
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
        $sanitize = new Sanitize();
        $data     = ['name' => '  john doe  ', 'email' => ' JOHN.DOE@example.COM '];
        $config           = new \Bgeneto\Sanitize\Config\Sanitization();
        $config->rules    = [
            'UserModel' => [
                'name' => ['trim', 'capitalize'],
                'email' => ['email', 'trim', 'capitalize'],
            ],
        ];
        \Config\Services::injectMock('sanitization', $config);
        $rules  = $sanitize->loadRules('UserModel', $config->rules['UserModel']);
        $result = $sanitize->sanitize('UserModel', $data, $rules);

        $this->assertSame('JOHN DOE', $result['name']);
        $this->assertSame('john.doe@example.com', $result['email']);
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
