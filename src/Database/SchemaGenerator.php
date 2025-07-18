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
     * Create a new table.
     *
     * @param string $table
     * @param callable $callback
     * @return void
     */
    public function create(string $table, callable $callback)
    {
        // $callback sẽ nhận một Blueprint để định nghĩa cột
        // $this->adapter->createTable($table, $columns)
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return void
     */
    public function drop(string $table)
    {
        // $this->adapter->dropTable($table)
    }

    /**
     * Add a column to a table.
     *
     * @param string $table
     * @param string $column
     * @param string $type
     * @param array $options
     * @return void
     */
    public function addColumn(string $table, string $column, string $type, array $options = [])
    {
        // $this->adapter->addColumn($table, $column, $type, $options)
    }

    /**
     * Drop a column from a table.
     *
     * @param string $table
     * @param string $column
     * @return void
     */
    public function dropColumn(string $table, string $column)
    {
        // $this->adapter->dropColumn($table, $column)
    }

    /**
     * Rename a table.
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function renameTable(string $from, string $to)
    {
        // $this->adapter->renameTable($from, $to)
    }

    // Có thể mở rộng thêm các phương thức khác như renameColumn, modifyColumn, ...
}