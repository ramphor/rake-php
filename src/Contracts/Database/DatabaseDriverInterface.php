<?php

namespace Rake\Contracts\Database;

/**
 * Interface for low-level database driver operations.
 */
interface DatabaseDriverInterface
{
    /**
     * Establish a database connection.
     *
     * @return void
     */
    public function connect();

    /**
     * Execute a query and return results.
     *
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public function query(string $query, array $params = []);

    /**
     * Execute a statement (insert, update, delete).
     *
     * @param string $query
     * @param array $params
     * @return int Number of affected rows
     */
    public function execute(string $query, array $params = []): int;

    /**
     * Begin a transaction.
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the current transaction.
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback the current transaction.
     *
     * @return void
     */
    public function rollback();

    /**
     * Close the database connection.
     *
     * @return void
     */
    public function close();
}
