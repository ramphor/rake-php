<?php
namespace Ramphor\Rake;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Ramphor\Rake\App;
use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Abstracts\ResourceManager;
use Ramphor\Rake\Facades\Facade;
use Ramphor\Rake\Facades\Crawler;
use Ramphor\Rake\Facades\Resources;
use Ramphor\Rake\Managers\InstanceManager;
use Ramphor\Rake\Managers\CrawlerManager;
use Ramphor\Rake\Exceptions\RuntimeException;

class Rake
{
    protected static $app;

    protected $id;
    protected $teeth;

    protected $options = [];

    public function __construct(string $rakeId, Driver $driver = null, ClientInterface $client = null, LoggerInterface $logger = null)
    {
        static::$app = App::instance();
        $this->id    = $rakeId;

        if (!is_null($driver)) {
            static::$app->bind('db', $driver);
        }
        if (!is_null($client)) {
            static::$app->bind('http', $client);
        }
        if (!is_null($logger)) {
            static::$app->bind('logger', $logger);
        }
        static::$app->bind('crawler', function () {
            return new CrawlerManager();
        });

        static::$app->bind('instances', new InstanceManager());
        static::$app->resolve('instances')->add($this);
        Facade::setFacadeApplication(static::$app);
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

    public function findTooth($id)
    {
        if (isset($this->teeth[$id])) {
            return $this->teeth[$id];
        }
    }

    public function registerLogger(LoggerInterface $logger)
    {
        static::$app->bind('logger', $logger);
    }

    public function registerResourceManager(ResourceManager $manager)
    {
        static::$app->bind('resources', $manager);
    }

    public function execute()
    {
        if (empty($this->teeth)) {
            // Add the log warning later
            return;
        }

        $results = [];
        foreach ($this->teeth as $tooth) {
            // Crawl data from the feeds of tooth
            $tooth->execute();

            $processor = $tooth->getProcessor();
            $parsers   = $tooth->getParsers();

            foreach ($parsers as $feedItems) {
                if (!($feedItems instanceof \Iterator)) {
                    continue;
                }

                // Reset the Iterator
                $feedItems->rewind();
                foreach ($feedItems as $feedItem) {
                    if (!$feedItem->isValid()) {
                        $result = ProcessResult::createErrorResult(
                            sprintf('The feed item "%s" is invalid', $feedItem->guid),
                            $feedItem->errorType
                        );
                    } else {
                        $processor->setFeedItem($feedItem);
                        $result = $processor->execute();
                    }
                    $result->setFeedItem($feedItem);
                    $result->setProcessingTooth($tooth);

                    // Store all results
                    array_push($results, $result);
                }
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
            // Sync the crawl URL from ProcessResult
            Crawler::syncFromResult($result);

            // Import resources
            $resources = Resources::createFromResult($result);
            $resources->import();
            $resources->importCrawlUrls();
        }

        if (Option::isAutoTransferFiles()) {
            $resources = Resources::getFilesFromDatabase();
            $resources->transferFiles();
        }
    }
}
