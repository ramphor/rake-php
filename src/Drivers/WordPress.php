<?php
namespace Ramphor\Rake\Drivers;

use Ramphor\Rake\Rake;
use Ramphor\Rake\Link;
use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Abstracts\Tooth;

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

    public function updateFeedOptions(Feed $feed, $options = null)
    {
        $tooth = $feed->getTooth();
        $rake  = $tooth->getRake();

        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT ID FROM ". $this->wpdb->prefix . "rake_feeds
                WHERE rake_id=%s
                    AND tooth_id=%s
                    AND feed_id=%s",
            $rake->getId(),
            $tooth->getId(),
            $feed->getId()
        ));

        $data = [
            'rake_id' => $rake->getId(),
            'tooth_id' => $tooth->getId(),
            'feed_id' => $feed->getId(),
            'options' => serialize($options),
            'last_execute' => current_time('mysql')
        ];
        $db_table = $this->wpdb->prefix . 'rake_feeds';

        if (is_null($exists)) {
            $this->wpdb->insert($db_table, $data);
        } else {
            $this->wpdb->update($db_table, $data, [
                'ID' => $exists
            ]);
        }
    }

    public function getFeedOptions(Feed $feed)
    {
        $tooth = $feed->getTooth();
        $rake  = $tooth->getRake();

        $sql = $this->wpdb->prepare(
            "SELECT options
            FROM {$this->wpdb->prefix}rake_feeds
            WHERE rake_id=%s
                AND feed_id=%s
                AND tooth_id=%s",
            $rake->getId(),
            $feed->getId(),
            $tooth->getId()
        );
        $options = $this->wpdb->get_var($sql);

        if (empty($options)) {
            return [];
        }
        return unserialize($options);
    }


    public function crawlUrlIsExists(Link $url, Rake $rake, Tooth $tooth = null)
    {
        if (is_null($tooth)) {
            $sql = $this->wpdb->prepare(
                "SELECT ID FROM {$this->wpdb->prefix}rake_crawled_urls WHERE url=%s AND rake_id=%s",
                (string)$url,
                $rake->getId()
            );
        } else {
            $sql = $this->wpdb->prepare(
                "SELECT ID
                FROM {$this->wpdb->prefix}rake_crawled_urls
                WHERE url=%s
                    AND rake_id=%s
                    AND tooth_id=%s",
                (string)$url,
                $rake->getId(),
                $tooth->getId()
            );
        }

        return $this->wpdb->get_var($sql) != null;
    }

    public function insertCrawlUrl(Link $url, Rake $rake, Tooth $tooth = null)
    {
        $data = [
            'url'        => (string)$url,
            'rake_id'   => $rake->getId(),
            'crawled'    => false,
            'retry'      => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        $formats = ['%s', '%s', '%d', '%d', '%s', '%s'];

        if (!is_null($tooth)) {
            $data['tooth_id'] = $tooth->getId();
            array_push($formats, '%s');
        }

        return $this->wpdb->insert($this->wpdb->prefix . 'rake_crawled_urls', $data, $formats);
    }

    public function getCrawlUrls(Rake $rake, Tooth $tooth = null, $options = [])
    {
        list($crawled, $retry, $limit) = $this->parseCrawlURLOptions($options);

        if (is_null($tooth)) {
            $sql = $this->wpdb->prepare(
                "SELECT *
                FROM {$this->wpdb->prefix}rake_crawled_urls
                WHERE rake_id=%s
                    AND crawled=%d
                ORDER BY retry {$retry}, updated_at ASC, ID ASC
                LIMIT %d",
                $rake->getId(),
                $crawled,
                $limit
            );
        } else {
            $sql = $this->wpdb->prepare(
                "SELECT *
                FROM {$this->wpdb->prefix}rake_crawled_urls
                WHERE rake_id=%s
                    AND tooth_id=%s
                    AND crawled=%d
                ORDER BY retry {$retry}, updated_at ASC, ID ASC
                LIMIT %d",
                $rake->getId(),
                $tooth->getId(),
                $crawled,
                $limit
            );
        }

        return $this->wpdb->get_results($sql);
    }
}
