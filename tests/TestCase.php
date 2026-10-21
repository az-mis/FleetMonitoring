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

    /**
     * A real sign-in starts a new session. Tests switch users inside one session, so forget the previous user's
     * password fingerprint (auth.session would otherwise sign the new user out as if their password had changed).
     */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if ($this->app->bound('session.store') && auth()->guard($guard)->id() !== $user->getAuthIdentifier()) {
            $this->app['session.store']->forget('password_hash_'.($guard ?? config('auth.defaults.guard')));
        }

        return parent::actingAs($user, $guard);
    }
}
