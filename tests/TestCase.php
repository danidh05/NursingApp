<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Optimize database operations for testing
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;'); // Enable foreign key support for SQLite
        }
        
        // Reduce memory usage by disabling query log
        DB::disableQueryLog();

        // Ensure no transactions are active at the start of each test
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    protected function tearDown(): void
    {
        // Clean up any remaining transactions
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        
        parent::tearDown();
    }
}