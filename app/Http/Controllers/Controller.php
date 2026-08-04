<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Check if the authenticated user is an admin.
     */
    protected function isAdmin(): bool
    {
        $user = auth()->user();
        return $user && $user->role === 'admin';
    }

    /**
     * Get the database driver specific LIKE operator.
     * Use 'ilike' for case-insensitive search in PostgreSQL, and 'like' for MySQL.
     */
    protected function likeOperator(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /**
     * Get a database-agnostic SQL expression that extracts the month number
     * from a date column. Used by aggregate/report queries grouped per month.
     */
    protected function monthExpression(string $column = 'tanggal'): string
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "EXTRACT(MONTH FROM {$column})";
        }

        if ($driver === 'sqlite') {
            return "CAST(strftime('%m', {$column}) AS INTEGER)";
        }

        return "MONTH({$column})";
    }
}
