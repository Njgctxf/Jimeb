<?php

namespace App\Traits;

use App\Models\Branch;
use App\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use ReflectionClass;

trait HasBranch
{
    protected static function bootHasBranch()
    {
        static::applyBranchScopeIfColumnExists();
    }

    protected static function booted()
    {
        static::applyBranchScopeIfColumnExists();
    }

    protected static function applyBranchScopeIfColumnExists(): void
    {
        $table = static::resolveModelTableName();
        static $hasBranchColumn = [];

        if (!array_key_exists($table, $hasBranchColumn)) {
            try {
                $hasBranchColumn[$table] = \App\Support\CachedSchema::hasColumn($table, 'branch_id');
            } catch (\Throwable $e) {
                $hasBranchColumn[$table] = false;
            }
        }

        if ($hasBranchColumn[$table]) {
            static::addGlobalScope(new BranchScope());
        }
    }

    /**
     * Resolve the model table without instantiating the model.
     * Instantiating during boot is disallowed in Laravel 13+.
     */
    protected static function resolveModelTableName(): string
    {
        $defaults = (new ReflectionClass(static::class))->getDefaultProperties();
        $table = $defaults['table'] ?? null;

        if (is_string($table) && $table !== '') {
            return $table;
        }

        return Str::snake(Str::pluralStudly(class_basename(static::class)));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
