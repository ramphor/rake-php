<?php

namespace Rake\Manager\Database;

use Rake\Contracts\Database\Adapter\DatabaseAdapterInterface;
use Rake\Database\SchemaGenerator;

/**
 * MigrationManager handles running, rolling back, and tracking database migrations.
 */
class MigrationManager
{
    /**
     * @var DatabaseAdapterInterface
     */
    private $adapter;

    /**
     * @var \Rake\Config\DatabaseConfig|null
     */
    private $databaseConfig;

    /**
     * MigrationManager constructor.
     *
     * @param DatabaseAdapterInterface $adapter
     * @param \Rake\Config\DatabaseConfig|null $databaseConfig
     */
    public function __construct(DatabaseAdapterInterface $adapter, ?\Rake\Config\DatabaseConfig $databaseConfig = null)
    {
        $this->adapter = $adapter;
        $this->databaseConfig = $databaseConfig;
    }

    /**
     * Run migration flow: load schema_definitions, compare and update schema if there are differences.
     *
     * @param SchemaGenerator $schemaGenerator
     * @param string $schemaDir
     * @return array List of SQL statements to migrate with version tracking
     */
    public function migrateFromDefinitions(SchemaGenerator $schemaGenerator, string $schemaDir = __DIR__ . '/../../../schema_definitions') : array
    {
        $definitions = [];
        if (!is_dir($schemaDir)) {
            return [];
        }
        foreach (glob($schemaDir . '/*.php') as $file) {
            $def = include $file;
            if (!isset($def['table']) || !isset($def['fields'])) continue;
            $definitions[$def['table']] = $def;
        }

        // Kiểm tra trạng thái migration
        $migrated = $this->checkMigrationStatus();

        // Sort definitions by dependencies
        $sortedDefinitions = $this->sortByDependencies($definitions);

        $actual = $schemaGenerator->getDatabaseSchema();
        $sqls = [];
        $foreignKeySQLs = []; // Separate foreign key operations
        $versionUpdates = []; // Track version updates
        $migrationHistory = []; // Track migration history

        foreach ($sortedDefinitions as $table => $def) {
            $actualTable = $actual[$table] ?? ['fields' => [], 'indexes' => []];
            $currentVersion = $migrated ? $this->getTableVersion($table) : '0.0.0';
            $targetVersion = $def['version'] ?? '1.0.0';

            // Track changes for history
            $changes = $this->trackChanges($table, $def, $actualTable, $currentVersion, $targetVersion);

            // Check if table exists
            if (empty($actualTable['fields'])) {
                // Table doesn't exist, create it
                $sqls[] = $schemaGenerator->generateCreateTableSQL($table, $def['fields'], [
                    'engine' => $def['engine'] ?? 'InnoDB',
                    'collation' => $def['collation'] ?? 'utf8mb4_unicode_ci',
                    'comment' => $def['comment'] ?? null
                ]);

                // Add indexes after table creation
                if (isset($def['indexes'])) {
                    foreach ($def['indexes'] as $indexDef) {
                        $sqls[] = $schemaGenerator->generateAddIndexSQL($table, $indexDef);
                    }
                }

                // Store foreign keys for later execution
                if (isset($def['foreign_keys'])) {
                    foreach ($def['foreign_keys'] as $fkDef) {
                        $foreignKeySQLs[] = $schemaGenerator->generateAddForeignKeySQL($table, $fkDef);
                    }
                }

                // Track version update and history
                $versionUpdates[$table] = $targetVersion;
                $migrationHistory[$table] = [
                    'from_version' => '0.0.0',
                    'to_version' => $targetVersion,
                    'changes' => $changes
                ];

                continue; // Skip field comparison for new tables
            }

            // Version comparison logic
            if ($this->compareVersions($targetVersion, $currentVersion) > 0) {
                // Target version is newer, apply upgrades
                $upgradeSQLs = $this->generateUpgradeSQL($schemaGenerator, $table, $def, $actualTable, $currentVersion, $targetVersion);
                $sqls = array_merge($sqls, $upgradeSQLs);
                // Track version update and history
                $versionUpdates[$table] = $targetVersion;
                $migrationHistory[$table] = [
                    'from_version' => $currentVersion,
                    'to_version' => $targetVersion,
                    'changes' => $changes
                ];
            } elseif ($this->compareVersions($targetVersion, $currentVersion) < 0) {
                // Target version is older, apply downgrades
                $downgradeSQLs = $this->generateDowngradeSQL($schemaGenerator, $table, $def, $actualTable, $currentVersion, $targetVersion);
                $sqls = array_merge($sqls, $downgradeSQLs);
                // Track version update and history
                $versionUpdates[$table] = $targetVersion;
                $migrationHistory[$table] = [
                    'from_version' => $currentVersion,
                    'to_version' => $targetVersion,
                    'changes' => $changes
                ];
            }
            // If versions are equal, no changes needed
        }

        // Add foreign key operations at the end
        $sqls = array_merge($sqls, $foreignKeySQLs);

        return [
            'sqls' => array_filter($sqls),
            'version_updates' => $versionUpdates,
            'migration_history' => $migrationHistory
        ];
    }

    /**
     * Sort definitions by dependencies to ensure proper migration order.
     *
     * @param array $definitions
     * @return array
     */
    private function sortByDependencies(array $definitions): array
    {
        $sorted = [];
        $visited = [];
        $temp = [];

        // Priority tables that should be created first
        $priorityTables = ['rake_configs', 'rake_tooths'];

        // Add priority tables first
        foreach ($priorityTables as $priorityTable) {
            if (isset($definitions[$priorityTable])) {
                $sorted[$priorityTable] = $definitions[$priorityTable];
                $visited[$priorityTable] = true;
            }
        }

        // Sort remaining tables by dependencies
        foreach ($definitions as $table => $def) {
            if (!isset($visited[$table])) {
                $this->topologicalSort($table, $definitions, $visited, $temp, $sorted);
            }
        }

        return $sorted;
    }

    /**
     * Topological sort for dependency resolution.
     *
     * @param string $table
     * @param array $definitions
     * @param array $visited
     * @param array $temp
     * @param array $sorted
     */
    private function topologicalSort(string $table, array $definitions, array &$visited, array &$temp, array &$sorted): void
    {
        if (isset($temp[$table])) {
            throw new \Exception("Circular dependency detected for table: $table");
        }

        if (isset($visited[$table])) {
            return;
        }

        $temp[$table] = true;

        // Get dependencies from foreign keys
        if (isset($definitions[$table]['foreign_keys'])) {
            foreach ($definitions[$table]['foreign_keys'] as $fk) {
                $refTable = $fk['references']['table'];
                // Bỏ qua nếu bảng tham chiếu chính nó
                if ($refTable === $table) {
                    continue;
                }
                if (isset($definitions[$refTable])) {
                    $this->topologicalSort($refTable, $definitions, $visited, $temp, $sorted);
                }
            }
        }

        unset($temp[$table]);
        $visited[$table] = true;
        $sorted[$table] = $definitions[$table];
    }

    /**
     * Generate upgrade SQL for version changes.
     *
     * @param SchemaGenerator $schemaGenerator
     * @param string $table
     * @param array $def
     * @param array $actualTable
     * @param string $currentVersion
     * @param string $targetVersion
     * @return array
     */
    private function generateUpgradeSQL(SchemaGenerator $schemaGenerator, string $table, array $def, array $actualTable, string $currentVersion, string $targetVersion): array
    {
        $sqls = [];

            // Compare each field
            foreach ($def['fields'] as $col => $colDef) {
                $actCol = $actualTable['fields'][$col] ?? null;
                if (!$actCol) {
                    // New column
                    $sqls[] = $schemaGenerator->generateAddColumnSQL($table, $col, $colDef);
                } else if ($schemaGenerator->compareColumn($colDef, $actCol)) {
                // Column has differences - upgrade
                    $sqls[] = $schemaGenerator->generateModifyColumnSQL($table, $col, $colDef);
                }
            }

        // Compare indexes
        if (isset($def['indexes'])) {
            foreach ($def['indexes'] as $indexDef) {
                $indexName = $indexDef['name'] ?? "idx_{$table}_{$indexDef['fields'][0]}";
                $actIndex = $actualTable['indexes'][$indexName] ?? null;

                if (!$actIndex) {
                    // New index
                    $sqls[] = $schemaGenerator->generateAddIndexSQL($table, $indexDef);
                } else if ($schemaGenerator->compareIndex($indexDef, $actIndex)) {
                    // Index has differences - drop and recreate
                    $sqls[] = $schemaGenerator->generateDropIndexSQL($table, $actIndex);
                    $sqls[] = $schemaGenerator->generateAddIndexSQL($table, $indexDef);
                }
            }
        }

        return $sqls;
    }

    /**
     * Generate downgrade SQL for version changes.
     *
     * @param SchemaGenerator $schemaGenerator
     * @param string $table
     * @param array $def
     * @param array $actualTable
     * @param string $currentVersion
     * @param string $targetVersion
     * @return array
     */
    private function generateDowngradeSQL(SchemaGenerator $schemaGenerator, string $table, array $def, array $actualTable, string $currentVersion, string $targetVersion): array
    {
        $sqls = [];

        // Remove fields that don't exist in target version
            foreach ($actualTable['fields'] as $col => $actCol) {
                if (!isset($def['fields'][$col])) {
                    $sqls[] = $schemaGenerator->generateDropColumnSQL($table, $col);
                }
            }

        // Remove indexes that don't exist in target version
        foreach ($actualTable['indexes'] as $indexName => $actIndex) {
            $indexExists = false;
            if (isset($def['indexes'])) {
                foreach ($def['indexes'] as $indexDef) {
                    $defIndexName = $indexDef['name'] ?? "idx_{$table}_{$indexDef['fields'][0]}";
                    if ($defIndexName === $indexName) {
                        $indexExists = true;
                        break;
                    }
                }
            }

            if (!$indexExists) {
                $sqls[] = $schemaGenerator->generateDropIndexSQL($table, $actIndex);
            }
        }

        return $sqls;
    }

    /**
     * Get prefixed table name
     *
     * @param string $tableName
     * @return string
     */
    private function getPrefixedTableName(string $tableName): string
    {
        if ($this->databaseConfig) {
            return $this->databaseConfig->getTableName($tableName);
        }
        return $tableName;
    }

    /**
     * Get current version of a table.
     *
     * @param string $table
     * @return string
     */
    private function getTableVersion(string $table): string
    {
        // Kiểm tra sự tồn tại của bảng rake_configs
        $configTable = $this->getPrefixedTableName('rake_configs');
        if (!$this->adapter->tableExists($configTable)) {
            // Tạo bảng rake_configs nếu chưa tồn tại
            $this->adapter->execute("CREATE TABLE IF NOT EXISTS `$configTable` (
                `config_key` VARCHAR(128) PRIMARY KEY,
                `config_value` TEXT,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        }

        // Try to read from rake_configs table
        try {
            $result = $this->adapter->select($configTable, ['config_value'], ['config_key' => "table_version_{$table}"]);
            if (!empty($result)) {
                return $result[0]['config_value'] ?? '1.0.0';
            }
        } catch (\Exception $e) {
            // Table doesn't exist or other error, return default version
        }

        return '1.0.0';
    }

    /**
     * Update table version in rake_configs.
     *
     * @param string $table
     * @param string $version
     * @return bool
     */
    private function updateTableVersion(string $table, string $version): bool
    {
        try {
            $configTable = $this->getPrefixedTableName('rake_configs');
            $this->adapter->update($configTable,
                ['config_value' => $version, 'updated_at' => date('Y-m-d H:i:s')],
                ['config_key' => "table_version_{$table}"]
            );
            return true;
        } catch (\Exception $e) {
            // If update fails, try insert
            try {
                $this->adapter->insert($configTable, [
                    'config_key' => "table_version_{$table}",
                    'config_value' => $version,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return true;
            } catch (\Exception $e2) {
                error_log("Failed to update table version: " . $e2->getMessage());
                return false;
            }
        }
    }

    /**
     * Log migration history.
     *
     * @param string $table
     * @param string $fromVersion
     * @param string $toVersion
     * @param array $changes
     * @param string $status
     * @return bool
     */
    private function logMigrationHistory(string $table, string $fromVersion, string $toVersion, array $changes, string $status = 'success'): bool
    {
        try {
            $configTable = $this->getPrefixedTableName('rake_configs');
            $this->adapter->insert($configTable, [
                'config_key' => "migration_history_{$table}_" . time(),
                'config_value' => json_encode([
                    'table' => $table,
                    'from_version' => $fromVersion,
                    'to_version' => $toVersion,
                    'changes' => $changes,
                    'status' => $status,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'migration_id' => uniqid('mig_', true)
                ]),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to log migration history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get migration history for a table.
     *
     * @param string $table
     * @param int $limit
     * @return array
     */
    public function getMigrationHistory(string $table, int $limit = 10): array
    {
        try {
            $configTable = $this->getPrefixedTableName('rake_configs');
            $pattern = "migration_history_{$table}_%";
            $result = $this->adapter->select($configTable, ['config_value'], ['config_key' => $pattern], $limit, ['updated_at' => 'DESC']);

            $history = [];
            foreach ($result as $row) {
                $data = json_decode($row['config_value'], true);
                if ($data) {
                    $history[] = $data;
                }
            }

            return $history;
        } catch (\Exception $e) {
            error_log("Failed to get migration history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all migration history.
     *
     * @param int $limit
     * @return array
     */
    public function getAllMigrationHistory(int $limit = 50): array
    {
        try {
            $configTable = $this->getPrefixedTableName('rake_configs');
            $result = $this->adapter->select($configTable, ['config_value'], ['config_key' => 'migration_history_%'], $limit, ['updated_at' => 'DESC']);

            $history = [];
            foreach ($result as $row) {
                $data = json_decode($row['config_value'], true);
                if ($data) {
                    $history[] = $data;
                }
            }

            return $history;
        } catch (\Exception $e) {
            error_log("Failed to get all migration history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Track changes for migration history.
     *
     * @param string $table
     * @param array $def
     * @param array $actualTable
     * @param string $currentVersion
     * @param string $targetVersion
     * @return array
     */
    private function trackChanges(string $table, array $def, array $actualTable, string $currentVersion, string $targetVersion): array
    {
        $changes = [
            'added_fields' => [],
            'modified_fields' => [],
            'dropped_fields' => [],
            'added_indexes' => [],
            'modified_indexes' => [],
            'dropped_indexes' => [],
            'added_foreign_keys' => [],
            'dropped_foreign_keys' => []
        ];

        // Track field changes
        foreach ($def['fields'] as $col => $colDef) {
            $actCol = $actualTable['fields'][$col] ?? null;
            if (!$actCol) {
                $changes['added_fields'][] = $col;
            } elseif ($this->compareColumn($colDef, $actCol)) {
                $changes['modified_fields'][] = [
                    'field' => $col,
                    'old' => $actCol,
                    'new' => $colDef
                ];
            }
        }

        // Track dropped fields
        foreach ($actualTable['fields'] as $col => $actCol) {
            if (!isset($def['fields'][$col])) {
                $changes['dropped_fields'][] = $col;
            }
        }

        // Track index changes
        if (isset($def['indexes'])) {
            foreach ($def['indexes'] as $indexDef) {
                $indexName = $indexDef['name'] ?? "idx_{$table}_{$indexDef['fields'][0]}";
                $actIndex = $actualTable['indexes'][$indexName] ?? null;

                if (!$actIndex) {
                    $changes['added_indexes'][] = $indexName;
                } elseif ($this->compareIndex($indexDef, $actIndex)) {
                    $changes['modified_indexes'][] = [
                        'index' => $indexName,
                        'old' => $actIndex,
                        'new' => $indexDef
                    ];
                }
            }
        }

        // Track dropped indexes
        foreach ($actualTable['indexes'] as $indexName => $actIndex) {
            $indexExists = false;
            if (isset($def['indexes'])) {
                foreach ($def['indexes'] as $indexDef) {
                    $defIndexName = $indexDef['name'] ?? "idx_{$table}_{$indexDef['fields'][0]}";
                    if ($defIndexName === $indexName) {
                        $indexExists = true;
                        break;
                    }
                }
            }

            if (!$indexExists) {
                $changes['dropped_indexes'][] = $indexName;
            }
        }

        return $changes;
    }

    /**
     * Compare two version strings.
     *
     * @param string $version1
     * @param string $version2
     * @return int
     */
    private function compareVersions(string $version1, string $version2): int
    {
        $v1 = explode('.', $version1);
        $v2 = explode('.', $version2);

        for ($i = 0; $i < max(count($v1), count($v2)); $i++) {
            $num1 = isset($v1[$i]) ? (int)$v1[$i] : 0;
            $num2 = isset($v2[$i]) ? (int)$v2[$i] : 0;

            if ($num1 > $num2) return 1;
            if ($num1 < $num2) return -1;
        }

        return 0; // Versions are equal
    }

    /**
     * Execute migration SQL statements and update versions.
     *
     * @param array $migrationData
     * @return bool
     */
    public function executeMigration(array $migrationData): bool
    {
        $sqls = $migrationData['sqls'] ?? $migrationData;
        $versionUpdates = $migrationData['version_updates'] ?? [];
        $migrationHistory = $migrationData['migration_history'] ?? [];

        if (empty($sqls)) {
            return true;
        }

        try {
            $this->adapter->beginTransaction();

            foreach ($sqls as $sql) {
                if (!empty($sql)) {
                    $result = $this->adapter->execute($sql);
                    if ($result === false) {
                        // Check if it's a duplicate key/constraint error or index error
                        $error = $this->adapter->getLastError();
                        if ($error && (
                            strpos($error, 'Duplicate key name') !== false ||
                            strpos($error, 'Duplicate foreign key constraint name') !== false ||
                            strpos($error, 'Failed to open the referenced table') !== false ||
                            strpos($error, 'Incorrect prefix key') !== false ||
                            strpos($error, 'BLOB/TEXT column') !== false ||
                            strpos($error, 'Specified key was too long') !== false ||
                            strpos($error, 'doesn\'t exist') !== false
                        )) {
                            // Log the error but continue
                            error_log("Migration warning (ignored): " . $error);
                            continue;
                        }
                        throw new \Exception("Failed to execute SQL: " . $sql . " - Error: " . $error);
                    }
                }
            }

            // Update versions after successful migration
            foreach ($versionUpdates as $table => $version) {
                $this->updateTableVersion($table, $version);
            }

            // Log migration history
            foreach ($migrationHistory as $table => $history) {
                $this->logMigrationHistory($table, $history['from_version'], $history['to_version'], $history['changes']);
            }

            $this->adapter->commit();
            return true;
        } catch (\Exception $e) {
            $this->adapter->rollback();
            error_log("Migration execution failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Run complete migration flow: load definitions, compare, generate SQL, execute.
     *
     * @param SchemaGenerator $schemaGenerator
     * @param string $schemaDir
     * @return bool
     */
    public function runMigration(SchemaGenerator $schemaGenerator, string $schemaDir = __DIR__ . '/../../../schema_definitions'): bool
    {
        $migrationData = $this->migrateFromDefinitions($schemaGenerator, $schemaDir);
        return $this->executeMigration($migrationData);
    }

    /**
     * Get the database adapter.
     *
     * @return DatabaseAdapterInterface
     */
    public function getAdapter(): DatabaseAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Set the database adapter.
     *
     * @param DatabaseAdapterInterface $adapter
     * @return void
     */
    public function setAdapter(DatabaseAdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * Check migration status
     *
     * @return bool
     */
    private function checkMigrationStatus(): bool
    {
        $configTable = $this->getPrefixedTableName('rake_configs');
        return $this->adapter->tableExists($configTable);
    }
}
