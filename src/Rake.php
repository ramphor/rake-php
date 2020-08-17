<?php
/**
 * @package Rake
 * @author Puleeno Nguyen <puleeno@gmail.com>
 * @copyright 2020 Ramphor Premium.
 * @license MIT
 * @link https://puleeno.com
 */

namespace Ramphor\Rake;

use Iterator;
use Psr\Log\LoggerInterface;
use Ramphor\Rake\App;
use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Abstracts\ResourceManager;
use Ramphor\Rake\Facades\Facade;
use Ramphor\Rake\Facades\Crawler;
use Ramphor\Rake\Facades\Resources;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Facades\Option;
use Ramphor\Rake\Managers\InstanceManager;
use Ramphor\Rake\Managers\CrawlerManager;
use Ramphor\Rake\Managers\RequestManager;
use Ramphor\Rake\Managers\OptionManager;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Exceptions\RuntimeException;

class Rake
{
    protected static $app;

    protected $id;
    protected $teeth;

    protected $options = [];

    public function __construct(string $rakeId, Driver $driver = null, LoggerInterface $logger = null)
    {
        static::$app = App::instance();
        $this->id    = $rakeId;

        if (!is_null($driver)) {
            static::$app->bind('db', $driver);
        }
        if (!is_null($logger)) {
            static::$app->bind('logger', $logger);
        }
        static::$app->bind('crawler', function () {
            return new CrawlerManager();
        });
        static::$app->bind('option', function () {
            return new OptionManager();
        });

        static::$app->bind('request', RequestManager::createRequest());
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
            Logger::info('The %s rake doesn\'t have any tooth to execute');
            return;
        }
        Logger::debug(sprintf('The rake "%s" has %d tooths will be executed', $this->getId(), count($this->teeth)), $this->teeth);

        // Load all options
        Option::loadAllOptions();

        foreach ($this->teeth as $tooth) {
            $results = [];
            // Crawl data from the feeds of tooth
            Logger::debug(sprintf('Execute the %s tooth with %d feed(s)', $tooth->getId(), count($this->teeth)));
            $tooth->execute();

            $processor = $tooth->getProcessor();
            $parsers   = $tooth->getParsers();

            foreach ($parsers as $feedItems) {
                if (!($feedItems instanceof Iterator)) {
                    Logger::warning(sprintf('The Rake parser is not instance of %s', Iterator::class), (array)$feedItem);
                    continue;
                }

                if (iterator_count($feedItems)) {
                    foreach ($feedItems as $feedItem) {
                        if (!($feedItem instanceof FeedItem)) {
                            Logger::warning(
                                sprintf('The %s tooth has feed item is not instance of %s', $tooth->getId(), FeedItem::class),
                                (array)$feedItem
                            );
                            continue;
                        }

                        if (!$feedItem->isValid()) {
                            Logger::warning(
                                'The feed item is invalid then create a error ProcessResult',
                                is_wp_error($feedItem) ? $feedItem->get_error_messages() : (array) $feedItem
                            );

                            $result = ProcessResult::createErrorResult(
                                sprintf('The feed item "%s" is invalid', $feedItem->guid),
                                $feedItem->errorType
                            );
                        } else {
                            $processor->setFeedItem($feedItem);
                            // Execute processor
                            $result      = $processor->execute();
                            $logFeedItem = var_export([
                                'guid' => $feedItem->guid,
                                'title' => $feedItem->title,
                                'urlDbId' => $feedItem->urlDbId,
                            ], true);

                            if ($result->isSuccess()) {
                                Logger::debug(sprintf(
                                    'Process feed item %s is successful with new GUID is %s - %s',
                                    $result->getGuid(),
                                    $result->getNewGuid(),
                                    $logFeedItem
                                ));
                            } else {
                                Logger::warning(sprintf(
                                    'The result is %s with message: %s - %s',
                                    $result->isSkipped() ? 'skipped' : 'error',
                                    $result->getErrorMessage(),
                                    $logFeedItem
                                ));
                            }
                        }
                        $result->setFeedItem($feedItem);
                        $result->setProcessingTooth($tooth);

                        // Store all results
                        array_push($results, $result);
                    }
                } else {
                    $notifiedOptionKey = sprintf('tooth_%s_notified', $tooth->getId());
                    if (!Option::get($notifiedOptionKey, false)) {
                        Logger::notice(sprintf(
                            'The parser doesn\'t found any feed item. It means maybe the tooth %s run completed.',
                            $tooth->getId()
                        ));
                        Option::update($notifiedOptionKey, true);
                    }
                }
            }

            // Sync results to database
            $this->sync($tooth, $results);
        }
    }

    public function sync($tooth, $results)
    {
        foreach ($results as $result) {
            if (!($result instanceof ProcessResult)) {
                Logger::warning(sprintf('The process result is not instance of %s', ProcessResult::class));
                continue;
            }
            // Sync the crawl URL from ProcessResult
            Crawler::syncFromResult($result);

            if ($result->isSuccess()) {
                // Import resources
                $resources = Resources::createFromResult($result, $tooth);
                $resources->import(true);
                $resources->importCrawlUrls();

                // Transfer the resources are fetched from the feed
                if ($tooth->isTransferResources()) {
                    Logger::debug('Transfer files after process the feed');
                    $resources->transferFiles();
                }
            } else {
                Logger::debug('The rake doesn\'t sync result to database when it is error', ['GUID' => $result->getGuid()]);
            }
        }

        // Transfer the resources are not imported from Database
        if (Option::isAutoTransferFiles()) {
            $resources = Resources::getFilesFromDatabase($tooth);

            Logger::debug(sprintf(
                'Transfer %d files from resources in database',
                $resources->getTotalResources()
            ));
            $resources->transferFiles();
        }
    }
}
