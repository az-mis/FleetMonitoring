<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * The database traits drop or truncate every table. Refuse to run them against anything other
     * than the in-memory SQLite database or a dedicated *_test MySQL database, so a misconfigured
     * environment can never wipe the live FleetDB.
     */
    protected function setUpTraits()
    {
        $uses = class_uses_recursive(static::class);

        if (array_intersect([RefreshDatabase::class, DatabaseMigrations::class, DatabaseTruncation::class], $uses)) {
            $connection = config('database.default');
            $database = (string) config("database.connections.{$connection}.database");

            if ($database !== ':memory:' && ! str_ends_with(strtolower($database), '_test')) {
                throw new RuntimeException("Refusing to reset database [{$connection}:{$database}]. Tests may only use :memory: or a *_test database.");
            }
        }

        return parent::setUpTraits();
    }
}
