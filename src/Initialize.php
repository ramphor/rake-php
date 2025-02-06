<?php

namespace Ramphor\Rake;

use Ramphor\Rake\App;
use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Facades\Facade;
use Ramphor\Rake\Facades\DB;

class Initialize
{
    protected static $app;

    public function __construct(Driver $driver)
    {
        static::$app = App::instance();

        if (!is_null($driver)) {
            static::$app->bind('db', $driver);
        }
        Facade::setFacadeApplication(static::$app);
    }

    public function setUpDb()
    {
        $tables = [
            'rake_crawled_urls' => "`id` bigint(20) NOT NULL AUTO_INCREMENT,
                `url` text NOT NULL,
                `rake_id` VARCHAR(255) NOT NULL,
                `tooth_id` VARCHAR(255) NULL,
                `crawled` tinyint(4) NOT NULL DEFAULT 0 ,
                `skipped` tinyint(4) NOT NULL DEFAULT 0 ,
                `retry` tinyint(4) NOT NULL DEFAULT 0,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)",
            'rake_relations' => "`id` INT NOT NULL AUTO_INCREMENT,
                `resource_id` bigint(20) NOT NULL,
                `parent_id` bigint(20) NOT NULL COMMENT 'The resource #ID contain resource_id',
                PRIMARY KEY (`id`)",
            'rake_resources' => "`id` bigint(20) NOT NULL AUTO_INCREMENT,
                `rake_id` VARCHAR(255) NOT NULL,
                `tooth_id` VARCHAR(255) NULL,
                `guid` text NOT NULL,
                `resource_type` VARCHAR(255) NOT NULL,
                `original_content` LONGTEXT NULL COMMENT 'The original content of the resource',
                `content_text` LONGTEXT NULL,
                `init_hash` varchar(64) NULL,
                `new_guid` text,
                `new_type` varchar(255) DEFAULT NULL,
                `imported` tinyint(4) NOT NULL DEFAULT 0,
                `skipped` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Use when resource is errors',
                `retry` int(11) NOT NULL DEFAULT 0,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)",
            'rake_hash_maps' => '`id` bigint(20) NOT NULL AUTO_INCREMENT,
                `sha256` varchar(64) NOT NULL,
                `resource_id` bigint(20) NOT NULL,
                `new_guid` text NOT NULL,
                `new_type` varchar(255) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)',
            'rake_options' => "`id` int(11) NOT NULL AUTO_INCREMENT,
                `option_name` VARCHAR(255) NULL,
                `option_value` longtext NOT NULL,
                `autoload` tinyint(4) NOT NULL DEFAULT 0 ,
                PRIMARY KEY (`id`)"
        ];

        foreach ($tables as $table => $syntax) {
            $query = sprintf('CREATE TABLE IF NOT EXISTS %s (%s)', DB::table($table), $syntax);
            // Create table
            DB::raw_query($query);
        }
    }

    public function removeDbTables()
    {
    }
}
