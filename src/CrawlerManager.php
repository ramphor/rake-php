<?php
namespace Ramphor\Rake;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Facades\DB;

class CrawlerManager
{
    public function syncFromResult(ProcessResult $result)
    {
        $feedItem = $result->getFeedItem();
        if (empty($feedItem->urlDbId)) {
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
}
