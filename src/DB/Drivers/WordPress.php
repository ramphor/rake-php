<?php
namespace Ramphor\Rake\DB\Drivers;

use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Constracts\SQLQueryBuilder;

class WordPress extends Driver
{
    public const DRIVER_NAME = 'wordpress';

    protected $dbInstance;

    public function __construct()
    {
        $this->dbInstance = $GLOBALS['wpdb'];
    }

    public function getName(): string
    {
        return self::DRIVER_NAME;
    }

    public function query(SQLQueryBuilder $query)
    {
        return $this->dbInstance->query((string) $query);
    }
}
