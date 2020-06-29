<?php
namespace Ramphor\Rake\Drivers;

use Ramphor\Rake\Abstracts\AbstractDriver;

class WordPress extends AbstractDriver
{
    protected $wpdb;

    public function __construct()
    {
        $this->wpdb = $GLOBALS['wpdb'];
    }

    public function dbQuery($sql)
    {
        return $this->wpdb->query($sql);
    }

    public function createDbTable($tableName, $syntaxContent)
    {
        // Generate create table SQL syntax
        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s%s (%s)',
            $this->wpdb->prefix,
            $tableName,
            $syntaxContent
        );

        // Execute SQL query to create DB table
        $this->dbQuery($sql);
    }
}
