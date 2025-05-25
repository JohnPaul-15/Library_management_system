<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use App\Models\Borrower;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    protected $tables = [
        'books',
        'users',
        'borrowers'
    ];

    public function getStats(): array
    {
        DB::enableQueryLog();
        $startTime = microtime(true);

        try {
            // Verify all required tables exist
            $this->verifyTables();

            $stats = [
                'totalBooks' => $this->getCount(Book::class),
                'totalUsers' => $this->getCount(User::class),
                'availableBooks' => $this->getCount(
                    Book::where('available_copies', '>', 0)
                ),
                'borrowedBooks' => $this->getCount(
                    Borrower::whereNull('date_return')
                ),
                'meta' => [
                    'execution_time' => microtime(true) - $startTime,
                    'queries' => DB::getQueryLog(),
                    'tables_status' => $this->getTablesStatus()
                ]
            ];

            // Log the stats for debugging
            Log::debug('Dashboard stats generated', [
                'stats' => $stats,
                'tables_status' => $this->getTablesStatus()
            ]);

            return $stats;

        } catch (\Exception $e) {
            Log::error('DashboardService failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tables_status' => $this->getTablesStatus()
            ]);
            throw $e;
        }
    }

    protected function verifyTables(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                Log::error("Required table missing: {$table}");
                throw new \RuntimeException("Required table missing: {$table}");
            }
        }
    }

    protected function getTablesStatus(): array
    {
        $status = [];
        foreach ($this->tables as $table) {
            $status[$table] = [
                'exists' => Schema::hasTable($table),
                'count' => Schema::hasTable($table) ? DB::table($table)->count() : 0,
                'columns' => Schema::hasTable($table) ? Schema::getColumnListing($table) : []
            ];
        }
        return $status;
    }

    protected function getCount($query): int
    {
        if (is_string($query)) {
            $query = new $query;
        }
        
        try {
            $count = $query->count();
            Log::debug("Count query executed", [
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'count' => $count
            ]);
            return $count;
        } catch (\Exception $e) {
            Log::error("Count query failed", [
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'error' => $e->getMessage()
            ]);
            return 0; // Safe fallback for empty tables or errors
        }
    }
} 