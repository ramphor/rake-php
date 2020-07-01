<?php
namespace Ramphor\Rake\Drivers;

use Ramphor\Rake\Link;
use Ramphor\Rake\Abstracts\Driver;

class WordPress extends Driver
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

    public function crawlUrlIsExists(Link $url, string $rakeId = null)
    {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT ID FROM {$this->wpdb->prefix}rake_crawled_urls WHERE url=%s AND rake_id=%s",
                (string)$url,
                $rakeId
            )
        ) != null;
    }

    public function insertCrawlUrl(Link $url, string $rakeId = null)
    {
        return $this->wpdb->insert(
            $this->wpdb->prefix . 'rake_crawled_urls',
            [
                'url'        => (string)$url,
                'rake_id'   => $rakeId,
                'crawled'    => false,
                'retry'      => 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%d', '%s', '%s']
        );
    }

    public function updateFeedOptions(Feed $feed, $options = null)
    {
        $tooth = $this->feed->getTooth();
        $rake  = $tooth->getRake();

        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT ID FROM ". $this->wpdb->prefix . " WHERE rake_id=% AND feed_id=%s",
            $rake->getId(),
            $feed->getId()
        ));

        var_dump($exists);
        die;
    }
}
