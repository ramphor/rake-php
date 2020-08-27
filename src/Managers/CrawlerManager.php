<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Facades\Resources;
use Ramphor\Rake\Abstracts\Tooth;

class CrawlerManager
{
    public function syncFromResult(ProcessResult $result)
    {
        $feedItem = $result->getFeedItem();
        if ((int)$feedItem->urlDbId <= 0) {
            // Processing later
            return;
        }

        $query = sql()->update(DB::table('rake_crawled_urls'));
        if ($result->isSkipped()) {
            $query = $query->set(['skipped' => 1, '@updated_at' => 'NOW()']);
            Resources::skipLinkByUrl($result->getGuid());
        } elseif ($result->isSuccess()) {
            Logger::debug(sprintf('The URL %s is crawled successfully', $result->getGuid()));
            $query = $query->set(['crawled' => 1, '@updated_at' => 'NOW()']);
        } else {
            Logger::debug(sprintf('The URL %s is crawled failed. It will be retry to re-crawl later', $result->getGuid()));
            $query = $query->set(['@retry' => 'retry + 1', '@updated_at' => 'NOW()']);
        }
        $query = $query->where('ID=?', $feedItem->urlDbId);

        return DB::query($query);
    }

    public function checkIsExists($url, Tooth &$tooth)
    {
        $rake = $tooth->getRake();
        $query = sql()->select('ID')->from(DB::table('rake_crawled_urls'));
        if ($tooth->isSkipCheckTooth()) {
            $query = $query->where(
                'url=? AND rake_id=? AND tooth_id IS NULL AND crawled=?',
                $url,
                $rake->getId(),
                0
            );
        } else {
            $query = $query->where(
                'url=? AND rake_id=? AND tooth_id=? AND crawled=?',
                $url,
                $rake->getId(),
                $tooth->getId(),
                0
            );
        }
        $query->limit(1);

        return (int) DB::var($query);
    }

    public function import($url, Tooth &$tooth)
    {
        $rake = $tooth->getRake();
        $existingId = $this->checkIsExists($resource->guid, $resource->tooth);

        /**
         * When in table has URLs is not imported yet
         * Use this URL ID to create a relationship
         */
        if ($existingId > 0) {
            Logger::debug(sprintf('The URL %s already exists in database', $resource->guid));
            return $existingId;
        }

        /**
         * Insert new crawled URL
         */
        $query = sql()->insertInto(
            DB::table('rake_crawled_urls'),
            ['url', 'rake_id', 'tooth_id', 'crawled', 'skipped', 'retry', 'created_at', 'updated_at']
        )->values(
            '?, ?, ?, ?, ?, ?, @, @',
            $url,
            $rake->getId(),
            $tooth->isSkipCheckTooth() ? null : $tooth->getId(),
            0,
            0,
            0,
            'NOW()',
            'NOW()'
        );
        Logger::debug(sprintf('Import crawl URL %s to database', $url));
        return DB::insert($query);
    }

    public function importFromResource($resource)
    {
        if (!is_a($tooth, Tooth::class)) {
            Logger::warning(sprintf('The Tooth must an instanceof %s', Tooth::class));
        }

        return $this->import($resource->guid, $resource->tooth);
    }
}
