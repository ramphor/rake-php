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
        $prefixedTable = $this->getPrefixedTableName($table);
        $sql = "ALTER TABLE `$prefixedTable` ADD COLUMN `$column` " . $this->convertType($definition);
        if (!empty($definition['nullable']) === false) $sql .= ' NOT NULL';
        if (isset($definition['default'])) $sql .= " DEFAULT '" . $definition['default'] . "'";
        if (!empty($definition['comment'])) $sql .= " COMMENT '" . addslashes($definition['comment']) . "'";
        return $sql . ';';
    }

    /**
     * Generate SQL to modify a column in a table.
     *
     * @param string $table
     * @param string $column
     * @param array $definition
     * @return string
     */
    public function generateModifyColumnSQL(string $table, string $column, array $definition): string
    {
        $prefixedTable = $this->getPrefixedTableName($table);
        $sql = "ALTER TABLE `$prefixedTable` MODIFY COLUMN `$column` " . $this->convertType($definition);
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
        $prefixedTable = $this->getPrefixedTableName($table);
        return "ALTER TABLE `$prefixedTable` DROP COLUMN `$column`;";
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
        // Add prefix to table name
        $prefixedTable = $this->getPrefixedTableName($table);

        $cols = [];
        $primaryKeys = [];

        foreach ($fields as $col => $colDef) {
            $colSql = "`$col` " . $this->convertType($colDef);
            if (!empty($colDef['auto_increment'])) $colSql .= ' AUTO_INCREMENT';
            if (!empty($colDef['primary'])) {
                $primaryKeys[] = "`$col`";
            }
            if (!empty($colDef['nullable']) === false) $colSql .= ' NOT NULL';
            if (isset($colDef['default'])) {
                $default = $colDef['default'];
                // Don't quote CURRENT_TIMESTAMP for datetime fields
                if ($default === 'CURRENT_TIMESTAMP') {
                    $colSql .= " DEFAULT $default";
                } else {
                    $colSql .= " DEFAULT '$default'";
                }
            }
            if (!empty($colDef['comment'])) $colSql .= " COMMENT '" . addslashes($colDef['comment']) . "'";
            $cols[] = $colSql;
        }

        // Add PRIMARY KEY constraint if needed
        if (!empty($primaryKeys)) {
            $cols[] = "PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
        }

        $engine = $options['engine'] ?? 'InnoDB';
        $collation = $options['collation'] ?? 'utf8mb4_unicode_ci';
        $charset = explode('_', $collation)[0] ?? 'utf8mb4';
        $tableComment = !empty($options['comment']) ? " COMMENT='" . addslashes($options['comment']) . "'" : '';
        $sql = "CREATE TABLE IF NOT EXISTS `$prefixedTable` (" . implode(", ", $cols) . ") ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collation}{$tableComment};";
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
        $prefixedTable = $this->getPrefixedTableName($table);
        $refTable = $this->getPrefixedTableName($fkDef['references']['table']);
        $refColumns = implode(',', array_map(function($c) { return "`$c`"; }, $fkDef['references']['columns']));
        $onDelete = isset($fkDef['on_delete']) ? " ON DELETE {$fkDef['on_delete']}" : '';
        $onUpdate = isset($fkDef['on_update']) ? " ON UPDATE {$fkDef['on_update']}" : '';

        return "ALTER TABLE `$prefixedTable` ADD CONSTRAINT `$fkName` FOREIGN KEY ($columns) REFERENCES `$refTable` ($refColumns){$onDelete}{$onUpdate};";
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
        $cols = [];

        foreach ($index['fields'] as $field) {
            // Thêm key length chỉ cho TEXT/BLOB columns
            // Vì không thể biết kiểu cột từ đây, nên thêm key length cho tất cả để an toàn
            $cols[] = "`$field`";
        }

        $cols = implode(',', $cols);
        $indexName = $index['name'] ?? "idx_{$table}_{$index['fields'][0]}";
        $prefixedTable = $this->getPrefixedTableName($table);
        return "CREATE {$unique}INDEX `$indexName` ON `$prefixedTable` ($cols);";
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
        $prefixedTable = $this->getPrefixedTableName($table);
        return "DROP INDEX `$indexName` ON `$prefixedTable`;";
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
     * Convert schema type to SQL type
     *
     * @param array $definition
     * @return string
     */
    private function convertType(array $definition): string
    {
        $type = $definition['type'];
        $length = $definition['length'] ?? null;

        switch ($type) {
            case 'string':
                return $length ? "VARCHAR($length)" : "VARCHAR(255)";
            case 'text':
                return "TEXT";
            case 'longtext':
                return "LONGTEXT";
            case 'int':
                return "INT";
            case 'bigint':
                return "BIGINT";
            case 'tinyint':
                return "TINYINT";
            case 'datetime':
                return "DATETIME";
            case 'timestamp':
                return "TIMESTAMP";
            case 'boolean':
                return "TINYINT(1)";
            default:
                return $type; // Return as-is if not recognized
        }
    }

    /**
     * Get the prefixed table name.
     *
     * @param string $tableName
     * @return string
     */
    private function getPrefixedTableName(string $tableName): string
    {
        // Get prefix from adapter if available
        if (method_exists($this->adapter, 'getTablePrefix')) {
            return $this->adapter->getTablePrefix() . $tableName;
        }

        // For WordPress adapter, try to get prefix from wpdb
        if (method_exists($this->adapter, 'getDriver')) {
            $driver = $this->adapter->getDriver();
            if (method_exists($driver, 'getWpdb')) {
                $wpdb = $driver->getWpdb();
                return $wpdb->prefix . $tableName;
            }
        }

        // Fallback to table name without prefix
        return $tableName;
    }
}