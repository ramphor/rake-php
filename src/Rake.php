<?php
namespace Ramphor\Rake;

use Ramphor\Rake\App;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Facades\Facade;
use Ramphor\Rake\Facades\DB;

use Ramphor\Rake\Abstracts\Driver;
use Psr\Http\Client\ClientInterface;

use Ramphor\Rake\Exceptions\RuntimeException;

class Rake
{
    protected static $app;

    protected $id;
    protected $teeth;

    public function __construct(string $rakeId, Driver $driver = null, ClientInterface $client = null)
    {
        static::$app = App::instance();

        $this->setId($rakeId);
        if (!is_null($driver)) {
            static::$app->bind('db', $driver);
        }
        if (!is_null($client)) {
            static::$app->bind('http', $client);
        }

        Facade::setFacadeApplication(static::$app);
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addTooth(Tooth $tooth)
    {
        if (isset($this->teeth[$tooth->getId()])) {
            throw new \Exception(sprintf('Tooth "%s" is already exists', $tooth->getId()));
        }
        $this->teeth[$tooth->getId()] = $tooth;
    }

    public function execute()
    {
        if (empty($this->teeth)) {
            throw new RuntimeException('Rake framework do not has any teeth');
        }

        $results = [];
        foreach ($this->teeth as $tooth) {
            // Crawl data from the feeds of tooth
            $tooth->execute();

            $feedItems = $tooth->getItems();
            $processor = $tooth->getProcessor();

            foreach ($feedItems as $feedItem) {
                if (!$feedItem->isValid()) {
                    $result = ProcessResult::createErrorResult(
                        sprintf('The feed item "%s" is invalid', $feedItem->guid),
                        $feedItem->isSkipped()
                    );
                } else {
                    $processor->setFeedItem($feedItem);
                    $result = $processor->execute();
                }

                // Store all results
                if ($feedItem->urlDbId) {
                    $result->setUrlDbId($feedItem->urlDbId);
                }
                array_push($results, $result);
            }
        }

        $this->sync($results);
    }

    public function sync($results)
    {
        foreach ($results as $result) {
            if (!($result instanceof ProcessResult)) {
                continue;
            }

            if (!$result->getUrlDbId()) {
                // Processing later
                continue;
            }

            $query = sql()->update(DB::table('rake_crawled_urls'));
            if ($result->isSkipped()) {
                $query = $query->set(['skipped' => 1, '@updated_at' => 'NOW()']);
            } elseif ($result->isSuccess()) {
                $query = $query->set(['crawled' => 1, '@updated_at' => 'NOW()']);
            } else {
                $query = $query->set(['@retry' => 'retry + 1', '@updated_at' => 'NOW()']);
            }
            $query = $query->where('ID=?', $result->getUrlDbId());

            DB::query($query);
        }
    }
}
