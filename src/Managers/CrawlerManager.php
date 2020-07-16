<?php
namespace Ramphor\Rake\Managers;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Resource;
use Ramphor\Rake\Facades\DB;
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
        } elseif ($result->isSuccess()) {
            $query = $query->set(['crawled' => 1, '@updated_at' => 'NOW()']);
        } else {
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
                'url=? AND rake_id=? AND tooth_id IS NULL',
                $url,
                $rake->getId()
            );
        } else {
            $query = $query->where(
                'url=? AND rake_id=? AND tooth_id=?',
                $url,
                $rake->getId(),
                $tooth->getId()
            );
        }

        return DB::var($query) > 0;
    }

    public function import($url, Tooth &$tooth)
    {
        $rake = $tooth->getRake();
        $query = sql()->insertInto(
            DB::table('rake_crawled_urls'),
            ['url', 'rake_id', 'tooth_id', 'crawled', 'skip', 'retry', 'created_at', 'updated_at']
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

        return DB::insert($query);
    }

    public function importFromResource(Resource $resource)
    {
        if ($this->checkIsExists($resource->guid, $resource->tooth)) {
            return;
        }

        return $this->import($resource->guid, $resource->tooth);
    }
}
