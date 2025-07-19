<?php

namespace Rake\Database;

/**
 * SchemaGenerator provides a flexible API for building and modifying database schemas.
 * Supports multiple database adapters (MySQL, SQLite, ...).
 */
class SchemaGenerator
{
    /**
     * @var mixed Database adapter instance
     */
    protected $adapter;

    /**
     * Constructor
     *
     * @param mixed $adapter
     */
    public function __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Generate SQL to add a column to a table.
     *
     * @param string $table
     * @param string $column
     * @param array $definition
     * @return string
     */
    public function generateAddColumnSQL(string $table, string $column, array $definition): string
    {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` " . $definition['type'];
        if (!empty($definition['nullable']) === false) $sql .= ' NOT NULL';
        if (isset($definition['default'])) $sql .= " DEFAULT '" . $definition['default'] . "'";
        if (!empty($definition['comment'])) $sql .= " COMMENT '" . addslashes($definition['comment']) . "'";
        return $sql . ';';
    }

    /**
     * Generate SQL to modify a column in a table (alias for generateModifyColumnSQL).
     *
     * @param string $table
     * @param string $column
     * @param array $definition
     * @return string
     */
    public function generateModifyColumnSQL(string $table, string $column, array $definition): string
    {
        $sql = "ALTER TABLE `$table` MODIFY COLUMN `$column` " . $definition['type'];
        if (!empty($definition['nullable']) === false) $sql .= ' NOT NULL';
        if (isset($definition['default'])) $sql .= " DEFAULT '" . $definition['default'] . "'";
        if (!empty($definition['comment'])) $sql .= " COMMENT '" . addslashes($definition['comment']) . "'";
        return $sql . ';';
    }

    /**
     * Generate SQL to drop a column from a table.
     *
     * @param string $table
     * @param string $column
     * @return string
     */
    public function generateDropColumnSQL(string $table, string $column): string
    {
        return "ALTER TABLE `$table` DROP COLUMN `$column`;";
    }

    /**
     * Generate SQL to create a new table.
     *
     * @param string $table
     * @param array $fields
     * @param array $options
     * @return string
     */
    public function generateCreateTableSQL(string $table, array $fields, array $options = []): string
    {
        $cols = [];
        foreach ($fields as $col => $colDef) {
            $colSql = "`$col` " . $colDef['type'];
            if (!empty($colDef['auto_increment'])) $colSql .= ' AUTO_INCREMENT';
            if (!empty($colDef['primary'])) $colSql .= ' PRIMARY KEY';
            if (!empty($colDef['nullable']) === false) $colSql .= ' NOT NULL';
            if (isset($colDef['default'])) $colSql .= " DEFAULT '" . $colDef['default'] . "'";
            if (!empty($colDef['comment'])) $colSql .= " COMMENT '" . addslashes($colDef['comment']) . "'";
            $cols[] = $colSql;
        }
        $engine = $options['engine'] ?? 'InnoDB';
        $collation = $options['collation'] ?? 'utf8mb4_unicode_ci';
        $charset = explode('_', $collation)[0] ?? 'utf8mb4';
        $tableComment = !empty($options['comment']) ? " COMMENT='" . addslashes($options['comment']) . "'" : '';
        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(", ", $cols) . ") ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collation}{$tableComment};";
        return $sql;
    }

    /**
     * Generate SQL to add a foreign key to a table.
     *
     * @param string $table
     * @param array $fkDef
     * @return string
     */
    public function generateAddForeignKeySQL(string $table, array $fkDef): string
    {
        $fkName = $fkDef['name'] ?? "fk_{$table}_{$fkDef['columns'][0]}";
        $columns = implode(',', array_map(function($c) { return "`$c`"; }, $fkDef['columns']));
        $refTable = $fkDef['references']['table'];
        $refColumns = implode(',', array_map(function($c) { return "`$c`"; }, $fkDef['references']['columns']));
        $onDelete = isset($fkDef['on_delete']) ? " ON DELETE {$fkDef['on_delete']}" : '';
        $onUpdate = isset($fkDef['on_update']) ? " ON UPDATE {$fkDef['on_update']}" : '';

        return "ALTER TABLE `$table` ADD CONSTRAINT `$fkName` FOREIGN KEY ($columns) REFERENCES `$refTable` ($refColumns){$onDelete}{$onUpdate};";
    }

    /**
     * Generate SQL to add an index to a table.
     *
     * @param string $table
     * @param array $index
     * @return string
     */
    public function generateAddIndexSQL(string $table, array $index): string
    {
        $unique = !empty($index['unique']) ? 'UNIQUE ' : '';
        $cols = implode(',', array_map(function($c) { return "`$c`"; }, $index['fields']));
        $indexName = $index['name'] ?? "idx_{$table}_{$index['fields'][0]}";
        return "CREATE {$unique}INDEX `$indexName` ON `$table` ($cols);";
    }

    /**
     * Generate SQL to drop an index from a table.
     *
     * @param string $table
     * @param array $index
     * @return string
     */
    public function generateDropIndexSQL(string $table, array $index): string
    {
        $indexName = $index['name'] ?? "idx_{$table}_{$index['fields'][0]}";
        return "DROP INDEX `$indexName` ON `$table`;";
    }

    /**
     * Get the current database schema.
     *
     * @return array
     */
    public function getDatabaseSchema(): array
    {
        // This would need to be implemented based on the adapter
        // For now, return empty array
        return [];
    }

    /**
     * Compare two column definitions.
     *
     * @param array $definition
     * @param array $actual
     * @return bool
     */
    public function compareColumn(array $definition, array $actual): bool
    {
        // Compare type, nullable, default, comment, etc.
        $fieldsToCompare = ['type', 'nullable', 'default', 'comment'];

        foreach ($fieldsToCompare as $field) {
            if (isset($definition[$field]) && isset($actual[$field])) {
                if ($definition[$field] !== $actual[$field]) {
                    return true; // Has differences
                }
            } elseif (isset($definition[$field]) !== isset($actual[$field])) {
                return true; // One has the field, the other doesn't
            }
        }

        return false; // No differences
    }

    /**
     * Compare two index definitions.
     *
     * @param array $definition
     * @param array $actual
     * @return bool
     */
    public function compareIndex(array $definition, array $actual): bool
    {
        if (!isset($definition['fields']) || !isset($actual['fields'])) {
            return true;
        }

        return $definition['fields'] !== $actual['fields'] ||
               ($definition['unique'] ?? false) !== ($actual['unique'] ?? false);
    }

    /**
     * Generate SQL to update table version.
     *
     * @param string $table
     * @param string $version
     * @return string
     */
    public function generateUpdateVersionSQL(string $table, string $version): string
    {
        // This would update a version table or table comment
        // For now, return empty string - implementation depends on version storage strategy
        return "";
    }
}