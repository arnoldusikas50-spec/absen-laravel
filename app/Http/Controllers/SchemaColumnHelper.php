<?php

namespace App\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;

/**
 * dengan BaseController::addColumnDefinition tanpa mewarisi controller.
 */
class SchemaColumnHelper
{
    public function addColumnDefinition(Blueprint $table, string $name, array $def): void
    {
        $change = ! empty($def['_change']);
        unset($def['_change']);
        $type = strtoupper((string) ($def['type'] ?? 'VARCHAR'));
        $constraint = $def['constraint'] ?? null;
        $nullable = ! empty($def['null']);
        $default = $def['default'] ?? null;
        $hasDefault = array_key_exists('default', $def);
        $unsigned = ! empty($def['unsigned']);
        $autoIncrement = ! empty($def['auto_increment']);

        switch ($type) {
            case 'VARCHAR':
                $col = $table->string($name, is_numeric($constraint) ? (int) $constraint : 255);
                break;
            case 'CHAR':
                $col = $table->char($name, is_numeric($constraint) ? (int) $constraint : 255);
                break;
            case 'TEXT':
                $col = $table->text($name);
                break;
            case 'MEDIUMTEXT':
                $col = $table->mediumText($name);
                break;
            case 'LONGTEXT':
                $col = $table->longText($name);
                break;
            case 'INT':
            case 'INTEGER':
                $col = $autoIncrement ? $table->increments($name) : $table->integer($name);
                break;
            case 'BIGINT':
                $col = $autoIncrement ? $table->bigIncrements($name) : $table->bigInteger($name);
                break;
            case 'SMALLINT':
                $col = $table->smallInteger($name);
                break;
            case 'TINYINT':
                $col = $table->tinyInteger($name);
                break;
            case 'FLOAT':
                $col = $table->float($name);
                break;
            case 'DOUBLE':
                $col = $table->double($name);
                break;
            case 'DECIMAL':
                $bits = is_string($constraint) ? explode(',', $constraint) : [10, 2];
                $col = $table->decimal($name, (int) ($bits[0] ?? 10), (int) ($bits[1] ?? 2));
                break;
            case 'BOOLEAN':
            case 'BOOL':
                $col = $table->boolean($name);
                break;
            case 'DATETIME':
                $col = $table->dateTime($name);
                break;
            case 'DATE':
                $col = $table->date($name);
                break;
            case 'TIME':
                $col = $table->time($name);
                break;
            case 'TIMESTAMP':
                $col = $table->timestamp($name);
                break;
            case 'ENUM':
                $col = $table->enum($name, is_array($constraint) ? $constraint : [$constraint]);
                break;
            default:
                $col = $table->string($name, 255);
                break;
        }

        if ($unsigned && method_exists($col, 'unsigned')) {
            $col->unsigned();
        }
        if ($nullable) {
            $col->nullable();
        }
        if ($hasDefault && $default !== null) {
            $col->default($default);
        }
        if ($change && method_exists($col, 'change')) {
            $col->change();
        }
    }
}
