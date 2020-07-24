<?php
namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Link;
use Ramphor\Rake\Constracts\Feed as FeedConstract;

abstract class Feed implements FeedConstract
{
    protected $hasResponse = false;

    protected $id;
    protected $tooth;
    protected $options;

    public function __construct(Tooth $tooth, string $feedId)
    {
        $this->setId($feedId);
        $this->setTooth($tooth);
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTooth(Tooth $tooth)
    {
        $this->tooth = $tooth;
    }

    public function getTooth(): Tooth
    {
        return $this->tooth;
    }

    public function urlExists(Link $url)
    {
        $tooth = $this->getTooth();
        $rake  = $tooth->getRake();

        $sql = sql()->select("ID")
            ->from(DB::table('rake_crawled_urls'))
            ->where(
                'url=? AND rake_id=? AND tooth_id=?',
                (string)$url,
                $rake->getId(),
                $tooth->getId()
            );

        return DB::exists($sql);
    }

    public function insertCrawlUrl(Link $url)
    {
        $tooth = $this->getTooth();
        $rake = $tooth->getRake();

        if ($tooth->isSkipCheckTooth()) {
            $toothId = null;
        } else {
            $toothId = $tooth->getId();
        }

        if (!$this->urlExists($url)) {
            $sql = sql()->insertInto(
                DB::table('rake_crawled_urls'),
                ['url', 'rake_id', 'tooth_id', 'crawled', 'retry', 'created_at', 'updated_at']
            )
            ->values('?, ?, ?, ?, ?, @, @', (string)$url, $rake->getId(), $toothId, 0, 0, 'NOW()', 'NOW()');

            return DB::query($sql);
        }
    }

    public function updateOption($option, $value)
    {
        if (is_null($this->options)) {
            $this->options = $this->getOptions();
        }
        $this->options[$option] = $value;
        $tooth = $this->getTooth();
        $rake  = $tooth->getRake();

        $exists_sql = sql()->select('ID')
            ->from(DB::table('rake_feeds'))
            ->where(
                'rake_id=? AND tooth_id=? AND feed_id=?',
                $rake->getId(),
                $tooth->getId(),
                $this->getId()
            );

        $sql = null;
        if (DB::exists($exists_sql)) {
            $sql = sql()->update(DB::table('rake_feeds'))
            ->set([
                'rake_id'       => $rake->getId(),
                'tooth_id'      => $tooth->getId(),
                'feed_id'       => $this->getId(),
                'options'       => serialize($this->options),
                '@last_execute' => 'NOW()'
            ]);
        } else {
            $sql = sql()->insertInto(DB::table('rake_feeds'), ['rake_id', 'tooth_id', 'feed_id', 'options', 'last_execute'])
                ->values(
                    '?, ?, ?, ?, @',
                    $rake->getId(),
                    $tooth->getId(),
                    $this->getId(),
                    serialize($this->options),
                    'NOW()'
                );
        }

        return DB::query($sql);
    }

    public function getOption($optionName, $defaultValue = false)
    {
        if (is_null($this->options)) {
            $this->options = $this->getOptions();
        }

        if (!isset($this->options[$optionName])) {
            return $defaultValue;
        }
        return $this->options[$optionName];
    }

    public function getOptions()
    {
        $tooth = $this->getTooth();
        $rake  = $tooth->getRake();

        $sql = sql()->select('options')
            ->from(DB::prefix() . 'rake_feeds')
            ->where(
                'rake_id = ? AND feed_id = ? AND tooth_id = ?',
                $rake->getId(),
                $this->getId(),
                $tooth->getId()
            );

        $options = DB::var($sql);

        if (empty($options)) {
            return [];
        }
        return unserialize($options);
    }

    public function hasResponse()
    {
        return (bool)$this->hasResponse;
    }
}
