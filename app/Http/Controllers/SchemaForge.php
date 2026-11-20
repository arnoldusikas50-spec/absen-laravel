<?php

namespace App\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * addColumn/addField/addPrimaryKey/addKey/addUniqueKey/createTable/
 * modifyColumn/dropTable/dropColumn.
 */
class SchemaForge
{
    protected array $fields = [];

    protected $primaryKey = null;

    protected array $keys = [];

    protected array $uniqueKeys = [];

    public function addField(array $fields): static
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    public function addPrimaryKey($key): static
    {
        $this->primaryKey = $key;

        return $this;
    }

    public function addKey($key, bool $primary = false): static
    {
        if ($primary) {
            $this->primaryKey = $key;
        } else {
            $this->keys[] = $key;
        }

        return $this;
    }

    public function addUniqueKey($key, ?string $name = null): static
    {
        $this->uniqueKeys[] = [$key, $name];

        return $this;
    }

    public function createTable(string $table, bool $ifNotExists = false): bool
    {
        try {
            if ($ifNotExists && Schema::hasTable($table)) {
                $this->reset();

                return true;
            }
            $fields = $this->fields;
            $primaryKey = $this->primaryKey;
            $keys = $this->keys;
            $uniqueKeys = $this->uniqueKeys;
            Schema::create($table, function (Blueprint $t) use ($fields, $primaryKey, $keys, $uniqueKeys) {
                $helper = new SchemaColumnHelper;
                foreach ($fields as $name => $def) {
                    if (is_string($def)) {
                        $t->string($name, 255);

                        continue;
                    }
                    $helper->addColumnDefinition($t, $name, (array) $def);
                }
                if ($primaryKey !== null) {
                    if (is_array($primaryKey)) {
                        $t->primary($primaryKey);
                    } else {
                        $t->primary($primaryKey);
                    }
                }
                foreach ($keys as $key) {
                    $t->index(is_array($key) ? $key : [$key]);
                }
                foreach ($uniqueKeys as [$key, $name]) {
                    $t->unique(is_array($key) ? $key : [$key], $name);
                }
            });
            $this->reset();

            return true;
        } catch (\Throwable $e) {
            Log::error('[SchemaForge::createTable] '.$e->getMessage());
            $this->reset();

            return false;
        }
    }

    public function addColumn(string $table, array $fields): bool
    {
        try {
            if (! Schema::hasTable($table)) {
                return false;
            }
            $existing = Schema::getColumnListing($table);
            $toAdd = array_diff_key($fields, array_flip($existing));
            if (empty($toAdd)) {
                return true;
            }
            $helper = new SchemaColumnHelper;
            Schema::table($table, function (Blueprint $t) use ($toAdd, $helper) {
                foreach ($toAdd as $name => $def) {
                    $helper->addColumnDefinition($t, $name, (array) $def);
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('[SchemaForge::addColumn] '.$e->getMessage());

            return false;
        }
    }

    public function modifyColumn(string $table, array $fields): bool
    {
        try {
            if (! Schema::hasTable($table)) {
                return false;
            }
            $helper = new SchemaColumnHelper;
            Schema::table($table, function (Blueprint $t) use ($fields, $helper) {
                foreach ($fields as $name => $def) {
                    $helper->addColumnDefinition($t, $name, array_merge((array) $def, ['_change' => true]));
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('[SchemaForge::modifyColumn] '.$e->getMessage());

            return false;
        }
    }

    public function dropTable(string $table, bool $ifExists = false): bool
    {
        try {
            if ($ifExists) {
                Schema::dropIfExists($table);
            } else {
                Schema::drop($table);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[SchemaForge::dropTable] '.$e->getMessage());

            return false;
        }
    }

    public function dropColumn(string $table, $column): bool
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->dropColumn($column);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('[SchemaForge::dropColumn] '.$e->getMessage());

            return false;
        }
    }

    protected function reset(): void
    {
        $this->fields = [];
        $this->primaryKey = null;
        $this->keys = [];
        $this->uniqueKeys = [];
    }
}
