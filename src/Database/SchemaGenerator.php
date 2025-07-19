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
     * Get all table names in the current database.
     *
     * @return array
     */
    public function getTables(): array
    {
        return $this->adapter->getTables();
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
     * Generate SQL to rename a column in a table.
     *
     * @param string $table
     * @param string $oldColumn
     * @param string $newColumn
     * @return string
     */
    public function generateRenameColumnSQL(string $table, string $oldColumn, string $newColumn): string
    {
        return "ALTER TABLE `$table` RENAME COLUMN `$oldColumn` TO `$newColumn`;";
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
     * @param array $columns
     * @param array $options
     * @return string
     */
    public function generateCreateTableSQL(string $table, array $columns, array $options = []): string
    {
        $cols = [];
        foreach ($columns as $col => $colDef) {
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
     * Generate SQL to drop a table.
     *
     * @param string $table
     * @return string
     */
    public function generateDropTableSQL(string $table): string
    {
        return "DROP TABLE IF EXISTS `$table`;";
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
        $cols = implode(',', array_map(function($c) { return "`$c`"; }, $index['columns']));
        $indexName = $index['name'] ?? "idx_{$table}_{$index['columns'][0]}";
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
        $indexName = $index['name'] ?? "idx_{$table}_{$index['column']}";
        return "DROP INDEX `$indexName` ON `$table`;";
    }

    // Could extend with additional methods like renameTable, modifyTable, etc.
}