<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\Driver;

class Initialize
{
    protected $driver;

    public function __construct(Driver $driver = null)
    {
        if (!is_null($driver)) {
            $this->setDriver($driver);
        }
    }

    public function setDriver(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function setUpDb()
    {
        $this->driver->createDbTable('rake_crawled_urls', "`ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `url` text NOT NULL,
            `rake_id` VARCHAR(255) NOT NULL,
            `tooth_id` VARCHAR(255) NULL,
            `crawled` tinyint(4) NOT NULL,
            `retry` tinyint(4) NOT NULL DEFAULT '0',
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`ID`)");

        $this->driver->createDbTable('rake_mapping', "`resource_id` bigint(20) NOT NULL,
            `source_id` bigint(20) NOT NULL COMMENT 'The resource #ID contain resource_id'");

        $this->driver->createDbTable('rake_resources', "`ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `rake_id` VARCHAR(255) NOT NULL,
            `tooth_id` VARCHAR(255) NULL,
            `guid` text NOT NULL,
            `resource_type` VARCHAR(255) NOT NULL,
            `sha256_hash` char(64) NOT NULL,
            `new_guid` text,
            `new_type` varchar(255) DEFAULT NULL,
            `imported` tinyint(4) NOT NULL DEFAULT '0',
            `retry` int(11) NOT NULL DEFAULT '0',
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`ID`)");

        $this->driver->createDbTable('rake_feeds', "`ID` int(11) NOT NULL,
            `rake_id` varchar(255) NOT NULL,
            `tooth_id` varchar(255) NOT NULL,
            `feed_id` varchar(255) NOT NULL,
            `options` longtext NOT NULL,
            `last_execute` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`)");
    }
}
