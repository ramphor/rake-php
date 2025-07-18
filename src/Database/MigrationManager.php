<?php

namespace Rake\Database;

use Rake\Contracts\MigrationInterface;

/**
 * MigrationManager handles running, rolling back, and tracking database migrations.
 */
class MigrationManager
{
    /**
     * @var mixed Database adapter instance
     */
    protected $adapter;

    /**
     * @var SchemaGenerator
     */
    protected $schema;

    /**
     * @var string Migration table name
     */
    protected $migrationTable = 'rake_migrations';

    /**
     * Constructor
     *
     * @param mixed $adapter
     */
    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->schema = new SchemaGenerator($adapter);
        $this->ensureMigrationTable();
    }

    /**
     * Ensure the migration tracking table exists.
     *
     * @return void
     */
    protected function ensureMigrationTable()
    {
        // Tạo bảng nếu chưa có
        // $this->schema->create($this->migrationTable, ...)
    }

    /**
     * Run all pending migrations.
     *
     * @param array $migrations List of MigrationInterface instances
     * @return void
     */
    public function migrate(array $migrations)
    {
        // Lặp qua các migration chưa chạy và gọi up()
    }

    /**
     * Rollback the last batch of migrations.
     *
     * @param array $migrations List of MigrationInterface instances
     * @return void
     */
    public function rollback(array $migrations)
    {
        // Lấy batch gần nhất và gọi down() cho các migration đó
    }

    /**
     * Reset all migrations (rollback all).
     *
     * @param array $migrations List of MigrationInterface instances
     * @return void
     */
    public function reset(array $migrations)
    {
        // Gọi down() cho tất cả migration đã chạy
    }

    /**
     * Get migration status.
     *
     * @return array
     */
    public function status(): array
    {
        // Trả về danh sách migration đã chạy và chưa chạy
        return [];
    }

    /**
     * Get current database schema (tables, columns, types, indexes, ...)
     * Only supports MySQL for now.
     *
     * @return array
     */
    public function getDatabaseSchema(): array
    {
        $schema = [];
        // Lấy danh sách bảng
        $tables = $this->adapter->query('SHOW TABLES');
        if (!$tables) return $schema;
        $tableKey = array_keys($tables[0])[0];
        foreach ($tables as $row) {
            $table = $row[$tableKey];
            $schema[$table] = [
                'columns' => [],
                'indexes' => [],
            ];
            // Lấy thông tin cột
            $columns = $this->adapter->query("SHOW COLUMNS FROM `$table`");
            foreach ($columns as $col) {
                $schema[$table]['columns'][$col['Field']] = [
                    'type' => $col['Type'],
                    'nullable' => ($col['Null'] === 'YES'),
                    'default' => $col['Default'],
                    'key' => $col['Key'],
                    'extra' => $col['Extra'],
                ];
            }
            // Lấy thông tin index
            $indexes = $this->adapter->query("SHOW INDEX FROM `$table`");
            foreach ($indexes as $idx) {
                $schema[$table]['indexes'][] = [
                    'name' => $idx['Key_name'],
                    'column' => $idx['Column_name'],
                    'unique' => ($idx['Non_unique'] == 0),
                ];
            }
        }
        return $schema;
    }

    /**
     * Compare schema definition with actual database schema.
     *
     * @param array $definitions Array of schema definitions [table => def]
     * @param array $actual Array of actual database schema [table => ...]
     * @return array List of changes (add_table, drop_table, add_column, modify_column, drop_column, add_index, drop_index)
     */
    public function compareSchemas(array $definitions, array $actual): array
    {
        $changes = [];
        // 1. Add or modify tables
        foreach ($definitions as $def) {
            $table = $def['table'] ?? null;
            if (!$table) continue;
            if (!isset($actual[$table])) {
                $changes[] = ['action' => 'add_table', 'table' => $table, 'definition' => $def];
                continue;
            }
            // So sánh cột
            $defCols = $def['columns'] ?? [];
            $actCols = $actual[$table]['columns'] ?? [];
            foreach ($defCols as $col => $colDef) {
                if (!isset($actCols[$col])) {
                    $changes[] = ['action' => 'add_column', 'table' => $table, 'column' => $col, 'definition' => $colDef];
                } else {
                    // So sánh kỹ các thuộc tính thông dụng
                    $diff = false;
                    $attrs = [
                        'type', 'length', 'default', 'nullable', 'comment',
                        'unsigned', 'auto_increment', 'primary', 'unique',
                        'charset', 'collation', 'zerofill', 'on_update'
                    ];
                    foreach ($attrs as $attr) {
                        $defVal = $colDef[$attr] ?? null;
                        $actVal = $actCols[$col][$attr] ?? null;
                        // Đối với type, so sánh không phân biệt hoa thường
                        if ($attr === 'type') {
                            $defVal = strtolower($defVal);
                            $actVal = strtolower($actVal);
                        }
                        if ($defVal !== $actVal) {
                            $diff = true;
                            break;
                        }
                    }
                    if ($diff) {
                        $changes[] = ['action' => 'modify_column', 'table' => $table, 'column' => $col, 'definition' => $colDef];
                    }
                }
            }
            // Cột bị xóa
            foreach ($actCols as $col => $colDef) {
                if (!isset($defCols[$col])) {
                    $changes[] = ['action' => 'drop_column', 'table' => $table, 'column' => $col];
                }
            }
            // So sánh index (đơn giản, chỉ so sánh unique index)
            $defIndexes = $def['indexes'] ?? [];
            $actIndexes = $actual[$table]['indexes'] ?? [];
            $defIndexSet = [];
            foreach ($defIndexes as $idx) {
                $defIndexSet[$idx['columns'][0]] = $idx;
            }
            $actIndexSet = [];
            foreach ($actIndexes as $idx) {
                $actIndexSet[$idx['column']] = $idx;
            }
            foreach ($defIndexSet as $col => $idx) {
                if (!isset($actIndexSet[$col])) {
                    $changes[] = ['action' => 'add_index', 'table' => $table, 'index' => $idx];
                }
            }
            foreach ($actIndexSet as $col => $idx) {
                if (!isset($defIndexSet[$col])) {
                    $changes[] = ['action' => 'drop_index', 'table' => $table, 'index' => $idx];
                }
            }
        }
        // 2. Drop tables không còn trong định nghĩa
        foreach ($actual as $table => $info) {
            $found = false;
            foreach ($definitions as $def) {
                if (($def['table'] ?? null) === $table) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $changes[] = ['action' => 'drop_table', 'table' => $table];
            }
        }
        return $changes;
    }

    /**
     * Load all schema definitions from the schema_definitions directory.
     *
     * @param string $dir Path to schema_definitions directory
     * @return array Array of schema definitions [filename => definition]
     */
    public static function loadAllSchemaDefinitions(string $dir = __DIR__ . '/../../schema_definitions') : array
    {
        $schemas = [];
        if (!is_dir($dir)) {
            return $schemas;
        }
        foreach (glob($dir . '/*.php') as $file) {
            $name = basename($file, '.php');
            $schemas[$name] = include $file;
        }
        return $schemas;
    }

    /**
     * Generate SQL statements for migration from list of changes.
     * Only supports MySQL for now.
     *
     * @param array $changes
     * @return array List of SQL statements
     */
    public function generateMigrationSQL(array $changes): array
    {
        $sqls = [];
        foreach ($changes as $change) {
            switch ($change['action']) {
                case 'add_table':
                    $def = $change['definition'];
                    $cols = [];
                    foreach ($def['columns'] as $col => $colDef) {
                        $colSql = "`$col` " . $colDef['type'];
                        if (!empty($colDef['auto_increment'])) $colSql .= ' AUTO_INCREMENT';
                        if (!empty($colDef['primary'])) $colSql .= ' PRIMARY KEY';
                        if (!empty($colDef['nullable']) === false) $colSql .= ' NOT NULL';
                        if (isset($colDef['default'])) $colSql .= " DEFAULT '" . $colDef['default'] . "'";
                        if (!empty($colDef['comment'])) $colSql .= " COMMENT '" . addslashes($colDef['comment']) . "'";
                        $cols[] = $colSql;
                    }
                    $tableComment = !empty($def['comment']) ? " COMMENT='" . addslashes($def['comment']) . "'" : '';
                    $tableSql = "CREATE TABLE IF NOT EXISTS `{$def['table']}` (" . implode(", ", $cols) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4{$tableComment};";
                    $sqls[] = $tableSql;
                    // Indexes
                    if (!empty($def['indexes'])) {
                        foreach ($def['indexes'] as $idx) {
                            $unique = !empty($idx['unique']) ? 'UNIQUE ' : '';
                            $cols = implode(',', array_map(function($c) { return "`$c`"; }, $idx['columns']));
                            $sqls[] = "CREATE {$unique}INDEX `idx_{$def['table']}_{$idx['columns'][0]}` ON `{$def['table']}` ($cols);";
                        }
                    }
                    break;
                case 'drop_table':
                    $sqls[] = "DROP TABLE IF EXISTS `{$change['table']}`;";
                    break;
                case 'add_column':
                    $col = $change['column'];
                    $def = $change['definition'];
                    $colSql = "ALTER TABLE `{$change['table']}` ADD COLUMN `$col` {$def['type']}";
                    if (!empty($def['nullable']) === false) $colSql .= ' NOT NULL';
                    if (isset($def['default'])) $colSql .= " DEFAULT '" . $def['default'] . "'";
                    if (!empty($def['comment'])) $colSql .= " COMMENT '" . addslashes($def['comment']) . "'";
                    $sqls[] = $colSql . ';';
                    break;
                case 'modify_column':
                    $col = $change['column'];
                    $def = $change['definition'];
                    $colSql = "ALTER TABLE `{$change['table']}` MODIFY COLUMN `$col` {$def['type']}";
                    if (!empty($def['nullable']) === false) $colSql .= ' NOT NULL';
                    if (isset($def['default'])) $colSql .= " DEFAULT '" . $def['default'] . "'";
                    if (!empty($def['comment'])) $colSql .= " COMMENT '" . addslashes($def['comment']) . "'";
                    $sqls[] = $colSql . ';';
                    break;
                case 'drop_column':
                    $sqls[] = "ALTER TABLE `{$change['table']}` DROP COLUMN `{$change['column']}`;";
                    break;
                case 'add_index':
                    $idx = $change['index'];
                    $unique = !empty($idx['unique']) ? 'UNIQUE ' : '';
                    $cols = implode(',', array_map(function($c) { return "`$c`"; }, $idx['columns']));
                    $sqls[] = "CREATE {$unique}INDEX `idx_{$change['table']}_{$idx['index']['columns'][0]}` ON `{$change['table']}` ($cols);";
                    break;
                case 'drop_index':
                    $idx = $change['index'];
                    $sqls[] = "DROP INDEX `{$idx['name']}` ON `{$change['table']}`;";
                    break;
            }
        }
        return $sqls;
    }
}