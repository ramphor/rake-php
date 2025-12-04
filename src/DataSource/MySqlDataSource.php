<?php

namespace Rake\DataSource;

/**
 * MySQL Data Source
 * Fetches data from MySQL database
 */
class MySqlDataSource extends AbstractDataSource
{
    /**
     * Fetch data from MySQL
     * 
     * @return array
     */
    public function fetch(): array
    {
        $host = $this->getConfigValue('host', 'localhost');
        $port = $this->getConfigValue('port', '3306');
        $user = $this->getConfigValue('user', 'root');
        $password = $this->getConfigValue('password', '');
        $database = $this->getConfigValue('database');
        $query = $this->getConfigValue('query');
        
        if (empty($database) || empty($query)) {
            throw new \RuntimeException('Database name and query are required');
        }

        $this->log('Fetching from MySQL', ['database' => $database]);

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            $stmt = $pdo->query($query);
            $data = $stmt->fetchAll();
            
            $this->log('MySQL data fetched', ['rows' => count($data)]);
            
            return $data;

        } catch (\PDOException $e) {
            $this->logError('MySQL fetch failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('MySQL error: ' . $e->getMessage());
        }
    }

    /**
     * Get source type
     */
    public function getType(): string
    {
        return 'mysql';
    }

    /**
     * Get configuration fields for UI
     * 
     * @return array
     */
    public static function getDataSourceConfigFields(): array
    {
        return [
            [
                'name' => 'host',
                'type' => 'text',
                'label' => 'Host',
                'description' => 'MySQL server hostname',
                'required' => true,
                'default' => 'localhost',
            ],
            [
                'name' => 'port',
                'type' => 'number',
                'label' => 'Port',
                'description' => 'MySQL server port',
                'required' => false,
                'default' => 3306,
            ],
            [
                'name' => 'user',
                'type' => 'text',
                'label' => 'Username',
                'description' => 'Database username',
                'required' => true,
                'default' => 'root',
            ],
            [
                'name' => 'password',
                'type' => 'password',
                'label' => 'Password',
                'description' => 'Database password',
                'required' => false,
                'default' => '',
            ],
            [
                'name' => 'database',
                'type' => 'text',
                'label' => 'Database',
                'description' => 'Database name',
                'required' => true,
                'default' => '',
            ],
            [
                'name' => 'query',
                'type' => 'textarea',
                'label' => 'SQL Query',
                'description' => 'SELECT query to fetch data',
                'required' => true,
                'default' => 'SELECT * FROM table_name LIMIT 100',
            ],
        ];
    }

    /**
     * Validate configuration
     * 
     * @return bool
     */
    public function validate(): bool
    {
        $database = $this->getConfigValue('database');
        $query = $this->getConfigValue('query');
        
        if (empty($database)) {
            throw new \RuntimeException('Database name is required');
        }

        if (empty($query)) {
            throw new \RuntimeException('SQL query is required');
        }

        return true;
    }
}

