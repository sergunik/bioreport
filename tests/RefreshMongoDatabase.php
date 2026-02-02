<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * MongoDB-compatible alternative to RefreshDatabase.
 * Runs migrations once, then truncates app collections before each test
 * (no transactions — standalone MongoDB does not support them).
 */
trait RefreshMongoDatabase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    public function refreshDatabase(): void
    {
        $this->beforeRefreshingDatabase();

        if ($this->usingInMemoryDatabases()) {
            $this->restoreInMemoryDatabase();
        }

        $this->refreshTestDatabase();

        $this->afterRefreshingDatabase();

        // Do not call beginDatabaseTransaction() — MongoDB standalone does not support it.
        $this->truncateMongoCollections();
    }

    protected function beginDatabaseTransaction(): void
    {
        // No-op for MongoDB; avoid "Transaction numbers are only allowed on a replica set" etc.
    }

    protected function truncateMongoCollections(): void
    {
        $connection = DB::connection('mongodb');

        foreach (['users', 'refresh_tokens', 'password_reset_tokens', 'accounts'] as $collection) {
            $connection->table($collection)->truncate();
        }
    }
}
