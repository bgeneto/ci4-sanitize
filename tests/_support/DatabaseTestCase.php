<?php

namespace Tests\Support;

use Bgeneto\Sanitize\Config\Sanitization;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Fabricator;
use Tests\Support\Models\MockModel;

/**
 * @internal
 */
abstract class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /**
     * Should the database be refreshed before each test?
     *
     * @var bool
     */
    protected $refresh = true;

    /**
     * Our configuration
     *
     * @var Sanitization
     */
    protected $config;

    /**
     * Instance of the test model
     *
     * @var MockModel
     */
    protected $model;

    /**
     * Instance of the fabricator primed with our model
     *
     * @var Fabricator
     */
    protected $fabricator;

    protected function setUp(): void
    {
        parent::setUp();

        $config       = new Sanitization();
        $this->config = $config;

        // Prep model components
        $this->model      = new MockModel();
        $this->fabricator = new Fabricator($this->model);

        $this->model::clearSanitizationRules();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetServices();
    }
}
