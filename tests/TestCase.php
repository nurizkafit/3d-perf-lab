<?php

namespace Tests;

use App\Models\AssetObject;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Never let a test touch the working database (AGENT.md §9).
        $database = DB::connection()->getDatabaseName();
        if ($database !== config('lab.verify_database')) {
            throw new RuntimeException("Tests must run against render_lab_verify, not {$database}.");
        }
    }

    /** An active object for assets and upload rows; Object is required (decision D52). */
    protected function objectId(string $name = 'Test object'): int
    {
        return AssetObject::firstOrCreate(['name' => $name])->id;
    }
}
