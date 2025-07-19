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
     * @var array
     */
    private $executedMigrations = [];

    /**
     * MigrationManager constructor.
     *
     * @param DatabaseAdapterInterface $adapter
     */
    public function __construct(DatabaseAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Run migration flow: load schema_definitions, compare and update schema if there are differences.
     *
     * @param SchemaGenerator $schemaGenerator
     * @param string $schemaDir
     * @return array List of SQL statements to migrate
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
        $actual = $schemaGenerator->getDatabaseSchema();
        $sqls = [];
        foreach ($definitions as $table => $def) {
            $actualTable = $actual[$table] ?? ['fields' => [], 'indexes' => []];
            // Compare each field
            foreach ($def['fields'] as $col => $colDef) {
                $actCol = $actualTable['fields'][$col] ?? null;
                if (!$actCol) {
                    // New column
                    $sqls[] = $schemaGenerator->generateAddColumnSQL($table, $col, $colDef);
                } else if ($schemaGenerator->compareColumn($colDef, $actCol)) {
                    // Column has differences
                    $sqls[] = $schemaGenerator->generateModifyColumnSQL($table, $col, $colDef);
                }
            }
            // Dropped columns
            foreach ($actualTable['fields'] as $col => $actCol) {
                if (!isset($def['fields'][$col])) {
                    $sqls[] = $schemaGenerator->generateDropColumnSQL($table, $col);
                }
            }
            // Could add index comparison, table comparison, etc.
        }
        return array_filter($sqls);
    }

    /**
     * Execute migration SQL statements.
     *
     * @param array $sqls
     * @return bool
     */
    public function executeMigration(array $sqls): bool
    {
        if (empty($sqls)) {
            return true;
        }

        try {
            $driver = $this->adapter->getDriver();
            $driver->beginTransaction();

            foreach ($sqls as $sql) {
                if (!empty($sql)) {
                    $result = $driver->execute($sql);
                    if ($result === false) {
                        throw new \Exception("Failed to execute SQL: " . $sql);
                    }
                }
            }

            $driver->commit();
            return true;
        } catch (\Exception $e) {
            $driver->rollback();
            error_log("Migration execution failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rollback migration by executing reverse SQL statements.
     *
     * @param array $reverseSqls
     * @return bool
     */
    public function rollbackMigration(array $reverseSqls): bool
    {
        if (empty($reverseSqls)) {
            return true;
        }

        try {
            $driver = $this->adapter->getDriver();
            $driver->beginTransaction();

            foreach ($reverseSqls as $sql) {
                if (!empty($sql)) {
                    $result = $driver->execute($sql);
                    if ($result === false) {
                        throw new \Exception("Failed to execute rollback SQL: " . $sql);
                    }
                }
            }

            $driver->commit();
            return true;
        } catch (\Exception $e) {
            $driver->rollback();
            error_log("Migration rollback failed: " . $e->getMessage());
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
        $sqls = $this->migrateFromDefinitions($schemaGenerator, $schemaDir);
        return $this->executeMigration($sqls);
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
}
