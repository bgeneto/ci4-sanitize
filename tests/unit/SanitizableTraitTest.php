<?php

namespace Tests\Unit;

use Tests\Support\DatabaseTestCase;
use Tests\Support\Models\MockModel;

/**
 * @internal
 */
final class SanitizableTraitTest extends DatabaseTestCase
{
    public function testSanitizeData()
    {
        $model = new MockModel();
        $data  = ['name' => '  Test Name  ', 'email' => 'test@example.com'];
        $model->setSanitizationRules(['name' => ['trim']]);
        $sanitizedData = $model->sanitizeData($data);
        $this->assertSame(['name' => 'Test Name', 'email' => 'test@example.com'], $sanitizedData);
    }

    public function testGetSanitizationRules()
    {
        $model = new MockModel();
        $model->setSanitizationRules(['name' => ['trim', 'lowercase']]);
        $rules = $model->getSanitizationRules('name');
        $this->assertSame(['trim', 'lowercase'], $rules);

        $allRules = $model->getSanitizationRules();
        $this->assertSame(['name' => ['trim', 'lowercase']], $allRules);
    }

    public function testBeforeInsertCallback()
    {
        $model = new MockModel();
        $model->setSanitizationCallbacks(['beforeInsert']);
        $model->setSanitizationRules(['name' => ['trim']]);
        $data = ['name' => '  Test Name  ', 'email' => 'test@example.com'];

        $model->insert($data);
        $insertedData = $model->first();

        $this->assertSame('Test Name', $insertedData['name']);
    }

    public function testBeforeUpdateCallback()
    {
        $model = new MockModel();
        $model->setSanitizationCallbacks(['beforeInsert', 'beforeUpdate']);
        $model->setSanitizationRules(['name' => ['trim']]);

        // Insert initial data
        $initialData = ['name' => 'Initial Name', 'email' => 'test@example.com'];
        $model->insert($initialData);

        // Update data
        $updateData = ['name' => '  Updated Name  '];
        $model->update(1, $updateData);

        $updatedData = $model->first();
        $this->assertSame('Updated Name', $updatedData['name']);
    }

    public function testAddSanitizationRules()
    {
        $model = new MockModel();
        $rules = ['name' => ['trim']];
        $model->setSanitizationRules($rules);
        $this->assertSame($rules, $model->getSanitizationRules());

        $moreRules = ['email' => ['lowercase']];
        $model->setSanitizationRules($moreRules);
        $this->assertSame(['name' => ['trim'], 'email' => ['lowercase']], $model->getSanitizationRules());
    }

    public function testRegisterSanitizationRule()
    {
        MockModel::registerSanitizationRule('test_rule', static fn ($value) => $value . '_test');

        $model = new MockModel();
        $data  = ['name' => 'Value'];
        $model->setSanitizationRules(['name' => ['test_rule']]);
        $sanitizedData = $model->sanitizeData($data);
        $this->assertSame(['name' => 'Value_test'], $sanitizedData);
    }
}
