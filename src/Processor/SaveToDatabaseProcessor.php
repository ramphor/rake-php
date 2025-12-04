<?php

namespace Rake\Processor;

use Rake\Contracts\Entities\ParsedDataItemInterface;

/**
 * Save to Database Processor
 * Saves extracted data to external database (MySQL/PostgreSQL)
 */
class SaveToDatabaseProcessor extends AbstractProcessor
{
    /**
     * Process and save data to database
     * 
     * @param ParsedDataItemInterface $item Extracted data item
     * @return ParsedDataItemInterface
     */
    public function process(ParsedDataItemInterface $item): ParsedDataItemInterface
    {
        // Skip if already null item
        if ($item->isNull()) {
            return $item;
        }

        // Get configuration
        $connectionType = $this->getConfig('connectionType', 'mysql');
        $host = $this->getConfig('host', 'localhost');
        $port = $this->getConfig('port', '3306');
        $user = $this->getConfig('user', 'root');
        $password = $this->getConfig('password', '');
        $database = $this->getConfig('database', 'scraped_data');
        $tableName = $this->getConfig('tableName', 'results');
        $conflictStrategy = $this->getConfig('conflictStrategy', 'upsert');

        // Validate required config
        if (empty($database) || empty($tableName)) {
            return $this->createNullItem('Database name and table name are required');
        }

        $this->log('Saving to database', [
            'database' => $database,
            'table' => $tableName,
            'strategy' => $conflictStrategy,
        ]);

        try {
            // Create DSN
            $dsn = $this->buildDsn($connectionType, $host, $port, $database);
            
            // Connect to database
            $pdo = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            // Get data
            $data = $item->getData();
            
            // Apply field mappings if configured
            $data = $this->applyFieldMappings($data);
            
            // Insert or update based on strategy
            $result = $this->saveData($pdo, $tableName, $data, $conflictStrategy);

            if ($result) {
                $this->log('Data saved successfully', ['rows_affected' => $result]);
                return $item->set('db_saved', true)->set('rows_affected', $result);
            } else {
                return $this->createNullItem('Failed to save data to database');
            }

        } catch (\PDOException $e) {
            $this->logError('Database error', ['error' => $e->getMessage()]);
            return $this->createNullItem('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logError('Processing error', ['error' => $e->getMessage()]);
            return $this->createNullItem('Error: ' . $e->getMessage());
        }
    }

    /**
     * Build DSN string
     */
    private function buildDsn(string $type, string $host, string $port, string $database): string
    {
        if ($type === 'postgresql') {
            return "pgsql:host={$host};port={$port};dbname={$database}";
        }
        
        // Default MySQL
        return "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    }

    /**
     * Save data to database
     */
    private function saveData(\PDO $pdo, string $table, array $data, string $strategy): int
    {
        if (empty($data)) {
            return 0;
        }

        // Prepare columns and values
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        if ($strategy === 'upsert') {
            // INSERT ... ON DUPLICATE KEY UPDATE (MySQL)
            $updates = array_map(fn($col) => "{$col} = VALUES({$col})", $columns);
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders),
                implode(', ', $updates)
            );
        } elseif ($strategy === 'skip') {
            // INSERT IGNORE (MySQL)
            $sql = sprintf(
                "INSERT IGNORE INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );
        } else {
            // Regular INSERT
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );
        }

        $stmt = $pdo->prepare($sql);
        
        // Bind values
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        
        return $stmt->rowCount();
    }

    /**
     * Apply field mappings to data
     * Maps extracted fields to database columns
     * 
     * @param array $data Original data
     * @return array Mapped data
     */
    private function applyFieldMappings(array $data): array
    {
        $autoMapFields = $this->getConfig('autoMapFields', false);
        $fieldMappings = $this->getConfig('fieldMappings', []);

        // If auto-map enabled or no mappings, return as-is
        if ($autoMapFields || empty($fieldMappings)) {
            return $data;
        }

        // Apply custom mappings
        $mappedData = [];
        foreach ($fieldMappings as $extractedField => $targetField) {
            if (isset($data[$extractedField])) {
                $mappedData[$targetField] = $data[$extractedField];
            }
        }

        return $mappedData;
    }
}

