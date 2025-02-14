<?php

namespace Tests\Support\Models;

use Bgeneto\Sanitize\Traits\SanitizableTrait;
use CodeIgniter\Model;

class MockModel extends Model
{
    use SanitizableTrait;

    protected $table          = 'mock_table';
    protected $primaryKey     = 'id';
    protected $allowedFields  = ['name', 'email', 'phone'];
    protected $returnType     = 'array';
    protected $useTimestamps  = false;
    protected $allowCallbacks = true;

    public function __construct()
    {
        parent::__construct();
    }

    protected function initialize()
    {
        $this->initializeSanitizer();
    }

    public static function clearSanitizationRules(): void
    {
        \Bgeneto\Sanitize\Sanitizer::resetRules();
    }
}
