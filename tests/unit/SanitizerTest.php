<?php

namespace Tests\Unit;

use Bgeneto\Sanitize\Sanitizer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SanitizerTest extends TestCase
{
    protected function tearDown(): void
    {
        Sanitizer::resetRules();
    }

    public function testConstructWithEmptyConfig()
    {
        $sanitizer = new Sanitizer();
        $this->assertSame([], $sanitizer->getRules());
    }

    public function testConstructWithCustomConfig()
    {
        $config = [
            'field1' => ['trim', 'lowercase'],
            'field2' => ['uppercase'],
        ];
        $sanitizer = new Sanitizer($config);
        $this->assertSame($config, $sanitizer->getRules());
    }

    public function testRegisterAndApplyCustomRule()
    {
        Sanitizer::registerRule('my_rule', static fn ($value) => $value . '_modified');

        $sanitizer     = new Sanitizer();
        $data          = ['field' => 'test'];
        $rules         = ['field' => ['my_rule']];
        $sanitizedData = $sanitizer->sanitize($data, $rules);
        $this->assertSame(['field' => 'test_modified'], $sanitizedData);
    }

    public function testApplyBuiltInRules()
    {
        $sanitizer = new Sanitizer();
        $data      = [
            'field1'  => '  Trimmed  ',
            'field2'  => 'LOWERCASE',
            'field3'  => 'uppercase',
            'field4'  => 'MiXeDcAsE',
            'field5'  => '  123-456  ',
            'field6'  => ' test@example.com ',
            'field7'  => '123.45',
            'field8'  => '123',
            'field9'  => '<p>Test</p>',
            'field10' => '  multiple   spaces  ',
            'field11' => 'This is a test with special chars: ação!@#$%^&*()',
            'field12' => 'https://www.example.com',
            'field13' => '<p>Test</p><script>alert("XSS")</script>',
            'field14' => '<a href="#">Link</a>',
            'field15' => 'Test1234',
        ];
        $rules = [
            'field1'  => ['trim'],
            'field2'  => ['lowercase'],
            'field3'  => ['uppercase'],
            'field4'  => ['capitalize'],
            'field5'  => ['numbers_only'],
            'field6'  => ['email', 'trim'],
            'field7'  => ['float'],
            'field8'  => ['int'],
            'field9'  => ['htmlspecialchars'],
            'field10' => ['norm_spaces'],
            'field11' => ['slug'],
            'field12' => ['url'],
            'field13' => ['strip_tags'],
            'field14' => ['strip_tags_allowed:<a>'],
            'field15' => ['alphanumeric'],
        ];
        $sanitizedData = $sanitizer->sanitize($data, $rules);
        $this->assertSame([
            'field1'  => 'Trimmed',
            'field2'  => 'lowercase',
            'field3'  => 'UPPERCASE',
            'field4'  => 'Mixedcase',
            'field5'  => '123456',
            'field6'  => 'test@example.com',
            'field7'  => '123.45',
            'field8'  => 123,
            'field9'  => '&lt;p&gt;Test&lt;/p&gt;',
            'field10' => 'multiple spaces',
            'field11' => 'this-is-a-test-with-special-chars-acao',
            'field12' => 'https://www.example.com',
            'field13' => 'Testalert("XSS")',
            'field14' => '<a href="#">Link</a>',
            'field15' => 'Test1234',
        ], $sanitizedData);
    }

    public function testSanitizeWithEmptyData()
    {
        $sanitizer     = new Sanitizer();
        $data          = [];
        $rules         = ['field1' => ['trim']];
        $sanitizedData = $sanitizer->sanitize($data, $rules);
        $this->assertSame([], $sanitizedData);
    }

    public function testSanitizeWithNoRules()
    {
        $sanitizer     = new Sanitizer();
        $data          = ['field1' => '  test  '];
        $sanitizedData = $sanitizer->sanitize($data);
        $this->assertSame(['field1' => '  test  '], $sanitizedData);
    }

    public function testSanitizeWithFieldWithoutRule()
    {
        $sanitizer     = new Sanitizer();
        $data          = ['field1' => '  test  ', 'field2' => 'value'];
        $rules         = ['field1' => ['trim']];
        $sanitizedData = $sanitizer->sanitize($data, $rules);
        $this->assertSame(['field1' => 'test', 'field2' => 'value'], $sanitizedData);
    }

    public function testGetRules()
    {
        $config = [
            'field1' => ['trim', 'lowercase'],
        ];
        $sanitizer = new Sanitizer($config);
        $this->assertSame($config, $sanitizer->getRules());
    }

    public function testAddRules()
    {
        $sanitizer = new Sanitizer();
        $rules     = ['field1' => ['trim']];
        $sanitizer->addRules($rules);
        $this->assertSame($rules, $sanitizer->getRules());

        $moreRules = ['field1' => ['lowercase'], 'field2' => ['uppercase']];
        $sanitizer->addRules($moreRules);
        $this->assertSame(['field1' => ['trim', 'lowercase'], 'field2' => ['uppercase']], $sanitizer->getRules());
    }

    public function testStripTagsAllowed()
    {
        $sanitizer = new Sanitizer();
        $data      = ['field1' => '<p>Test</p><a>Link</a>'];
        $rules     = ['field1' => ['strip_tags_allowed:<a>']];

        $sanitizedData = $sanitizer->sanitize($data, $rules);
        $this->assertSame(['field1' => 'Test<a>Link</a>'], $sanitizedData);

        $rules2         = ['field1' => ['strip_tags_allowed']];
        $sanitizedData2 = $sanitizer->sanitize($data, $rules2);
        $this->assertSame(['field1' => 'TestLink'], $sanitizedData2);

        $rules3         = ['field1' => ['strip_tags_allowed:<a>,<p>']];
        $sanitizedData3 = $sanitizer->sanitize($data, $rules3);
        $this->assertSame(['field1' => '<p>Test</p><a>Link</a>'], $sanitizedData3);
    }

    public function testGenerateSlug()
    {
        $sanitizer     = new Sanitizer();
        $data          = ['field1' => 'This is a Test String'];
        $rules         = ['field1' => ['slug']];
        $sanitizedData = $sanitizer->sanitize($data, $rules);
        $this->assertSame(['field1' => 'this-is-a-test-string'], $sanitizedData);

        $data2          = ['field1' => '  Trimmed  Slug   '];
        $rules2         = ['field1' => ['slug']];
        $sanitizedData2 = $sanitizer->sanitize($data2, $rules2);
        $this->assertSame(['field1' => 'trimmed-slug'], $sanitizedData2);

        $data3          = ['field1' => 123];
        $rules3         = ['field1' => ['slug']];
        $sanitizedData3 = $sanitizer->sanitize($data3, $rules3);
        $this->assertSame(['field1' => ''], $sanitizedData3);
    }

    public function testLateRules()
    {
        $config = [
            'field1' => ['trim'],
        ];
        $sanitizer     = new Sanitizer($config);
        $data          = ['field1' => '  test  ', 'field2' => '  value  '];
        $rules         = ['field2' => ['trim']];
        $sanitizedData = $sanitizer->sanitize($data, $rules);
        $this->assertSame(['field1' => 'test', 'field2' => 'value'], $sanitizedData);
    }

    public function testStaticUsage()
    {
        Sanitizer::resetRules();
        Sanitizer::registerRule('append_text', static function ($value, $params = []) {
            $suffix = $params[0] ?? '_appended';

            return $value . $suffix;
        });

        $sanitizedData = Sanitizer::applyRule('My String', 'append_text:!!!');
        $this->assertSame('My String!!!', $sanitizedData);
    }
}
