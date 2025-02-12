<?php

namespace Bgeneto\Sanitize\Tests\Traits;

use Bgeneto\Sanitize\Sanitize;
use Bgeneto\Sanitize\Tests\Support\TestUserModel;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class SanitizableTraitTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up custom rules before each test
        $reflection = new \ReflectionClass(Sanitize::class);
        $property   = $reflection->getProperty('customRules');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    public function testSetSanitizationRules()
    {
        $model = new TestUserModel();

        $rules = ['name' => ['trim', 'lowercase']];
        $model->setSanitizationRules($rules);

        $this->assertSame($rules, $model->getSanitizationRules());
    }

    public function testAddSanitizationRule()
    {
        $model = new TestUserModel();

        $model->addSanitizationRule('name', 'trim');
        $this->assertSame(['name' => ['trim']], $model->getDynamicSanitizationRules());

        $model->addSanitizationRule('email', ['trim', 'lowercase']);
        $this->assertSame(['name' => ['trim'], 'email' => ['trim', 'lowercase']], $model->getDynamicSanitizationRules());
    }

    public function testSanitizeData()
    {
        $model = new TestUserModel();
        $model->setSanitizationRules(['name' => ['trim', 'capitalize']]);

        $data = ['name' => '  john doe  '];
        $result = $model->sanitizeData($data);

        $this->assertSame('John Doe', $result['name']);
    }

    public function testLoadAllRules()
    {
        $model = new TestUserModel();
        $model->setSanitizationRules(['name' => ['trim', 'capitalize']]);
        $model->addSanitizationRule('email', ['trim', 'lowercase']);

        $modelName = 'TestUserModel';
        $rules = $model->loadAllRules($modelName);

        $expected = [
            'name'  => ['trim', 'capitalize'],
            'email' => ['trim', 'lowercase'],
        ];

        $this->assertSame($expected, $rules);
    }

    public function testLoadAllRulesWithConfig()
    {
        // Set up the configuration
        $config = new \Bgeneto\Sanitize\Config\Sanitization();
        $config->rules = [
            'TestUserModel' => [
                'name'  => ['trim', 'capitalize'],
                'phone' => ['numbers_only'],
            ],
        ];
        \Config\Services::injectMock('sanitization', $config);

        $model = new TestUserModel();
        $model->setSanitizationRules(['name' => ['lowercase']]); // Override config
        $model->addSanitizationRule('email', ['trim', 'lowercase']);

        $modelName = 'TestUserModel';
        $rules = $model->loadAllRules($modelName);

        $expected = [
            'name'  => ['lowercase'],
            'phone' => ['numbers_only'],
            'email' => ['trim', 'lowercase'],
        ];

        $this->assertSame($expected, $rules);
    }

    public function testSanitizeInsert()
    {
        $model = new TestUserModel();
        $model->setSanitizationRules(['name' => ['trim', 'capitalize']]);

        $data = ['name' => '  john doe  '];
        $model->insert($data);
        $result = $model->find(1);

        $this->assertSame('John Doe', $result['name']);
    }

    public function testSanitizeUpdate()
    {
        $model = new TestUserModel();
        $model->setSanitizationRules(['name' => ['trim', 'capitalize']]);

        // Insert a record to update
        $insertData = ['name' => '  jane doe  ', 'email' => 'jane.doe@example.com'];
        $model->insert($insertData);
        $id = $model->getInsertID();

        $data = ['name' => '  john doe  '];
        $model->update($id, $data);
        $updatedRecord = $model->find($id);

        $this->assertSame('John Doe', $updatedRecord['name']);
    }

    public function testSanitizeInsertCallbacksNotAllowed()
    {
        $model = new TestUserModel();
        $model->setSanitizationRules(['name' => ['trim', 'capitalize']]);

        $data = ['name' => '  john doe  '];
        $result = $model->insert($data);
        $this->assertFalse($result);
    }

    public function testSanitizeUpdateCallbacksNotAllowed()
    {
        $model = new TestUserModel();
        $model->setSanitizationRules(['name' => ['trim', 'capitalize']]);

        // Insert a record to update
        $insertData = ['name' => '  jane doe  ', 'email' => 'jane.doe@example.com'];
        $model->insert($insertData);
        $id = $model->getInsertID();

        $data = ['name' => '  john doe  '];
        $result = $model->update($id, $data);

        $updatedRecord = $model->find($id);

        $this->assertSame('  jane doe  ', $updatedRecord['name']); // Should not be sanitized
    }

    public function testAddCallback()
    {
        $model = new TestUserModel();

        $model->addCallback('beforeInsert', 'testCallbackMethod');

        // Set sanitization rules and insert data
        $model->setSanitizationRules(['name' => ['trim']]);
        $data = ['name' => '  john doe  '];
        $model->insert($data);
        $result = $model->find(1);

        // Check if the callback was executed
        $this->assertSame('Test Callback: John Doe', $result['name']);
    }

    public function testAddCallbackInvalidCallback()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid callback: invalidCallback');

        $model = new TestUserModel();

        $model->addCallback('invalidCallback', 'testMethod');
    }

    public function testRegisterCustomSanitizationRule()
    {
        $ruleName = 'test_rule';
        $callback = function ($value) {
            return 'test_' . $value;
        };

        TestUserModel::registerCustomSanitizationRule($ruleName, $callback);

        $sanitize = new Sanitize();
        $data = ['name' => 'John'];
        $rules = ['name' => [$ruleName]];
        $result = $sanitize->sanitize('TestUserModel', $data, $rules);

        $this->assertSame('test_John', $result['name']);
    }
}
