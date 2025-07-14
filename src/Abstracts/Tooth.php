<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Rake;
use Ramphor\Rake\Response;
use Ramphor\Rake\Constracts\Tooth as ToothConstract;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Abstracts\Processor;
use Ramphor\Rake\DataSource\FeedItemBuilder;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;
use Ramphor\Rake\Exceptions\ToothFormatException;

abstract class Tooth implements ToothConstract
{
    public const FORMAT_CSV  = 'csv';
    public const FORMAT_HTML = 'html';

    /**
     * @var \Ramphor\Rake\Abstracts\Feed[]
     */
    protected $feeds              = [];
    protected $responses          = [];
    protected $mappingFields      = [];
    protected $acceptToothFormats = [self::FORMAT_CSV, self::FORMAT_HTML];
    protected $skipCheckTooth     = false;
    protected $transferResources  = false;
    protected $limitQueryResources = 10;
    protected $urlUseLastSplash   = false;

    protected $id;
    protected $rake;
    protected $toothFormat;
    protected $parser;
    protected $processor;

    protected $urlValidator = true;

    /**
     * When feed items is empty and the action is assign to tooth. The action will be called
     * @var callable
     */
    protected $feedItemsEmptyAction = null;

    /**
     * @var array
     */
    protected $feedItemsEmptyActionArgs = [];

    /**
     * @var callable|null
     */
    protected $duplicateAction = null;

    /**
     * @var array
     */
    protected $duplicateActionArgs = [];

    public function __construct(string $toothId, Rake $rake = null)
    {
        $this->id = $toothId;
        if (!is_null($rake)) {
            $this->setRake($rake);
        }

        // Register custom callback for feed item builder
        $this->registerFeedItemCallbacks();
    }

    public function setRake(Rake $rake)
    {
        $this->rake = $rake;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRake(): Rake
    {
        return $this->rake;
    }

    public function isSkipCheckTooth()
    {
        return (bool) $this->skipCheckTooth;
    }

    public function registerProcessor(Processor $processor)
    {
        // Register processor for this tooth
        $this->processor = $processor;
    }

    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    public function setFormat($format)
    {
        if (!in_array($format, $this->acceptToothFormats)) {
            throw new ToothFormatException("The tooth format is invalid");
        }
        $this->toothFormat = $format;
    }

    public function addFeed(Feed $feed)
    {
        if (!isset($this->feeds[$feed->getId()])) {
            $this->feeds[$feed->getId()] = $feed;
        } else {
            throw new \Exception("Feed [%s] is existings", $feed->getId());
        }
    }

    public function getFeeds()
    {
        return $this->feeds;
    }

    public function createParser($resource, $parserOptions = null): Parser
    {
        $parsers = [
            self::FORMAT_CSV => CsvParser::class,
            self::FORMAT_HTML => HtmlParser::class,
        ];

        Logger::info(sprintf('Create the %s parser to build feed items', strtoupper($this->toothFormat)));
        return new $parsers[$this->toothFormat]($resource, $parserOptions);
    }

    public function addMappingField($fieldName, $args)
    {
        if (!is_array($args)) {
            return;
        }
        $args = array_merge([
            'type' => '',
            'pattern' => '',
        ], $args);

        if ($args['type'] && (!empty($args['pattern']) || in_array($args['type'], array('guid', 'custom')))) {
            $this->mappingFields[$fieldName] = $args;
        }
    }

    public function getParsers()
    {
        if (empty($this->toothFormat)) {
            Logger::warning(sprintf('The %s tooth doesn\'t specific data format to processing', $this->toothFormat));
            return [];
        }
        if (empty($this->mappingFields)) {
            Logger::warning('The tooth doesn\'t define mapping field rules to build feed item');
            return [];
        }

        $parsers = [];
        foreach ($this->getResponses() as $response) {
            if (!($response instanceof Response)) {
                Logger::warning(sprintf('The response is not instance of %s', Response::class));
                continue;
            }
            $parser = $this->createParser(
                $response,
                $this->parserOptions()
            );

            $feedItemBuilder = new FeedItemBuilder(
                $this->mappingFields,
                $this->toothFormat
            );

            $parser->setFeedItemBuilder($feedItemBuilder);

            // Push parser item to parsers list
            array_push($parsers, $parser);
        }

        return $parsers;
    }

    public function collect()
    {
        if (count($this->getFeeds()) <= 0) {
            Logger::info(sprintf(
                'The %s tooth doesn\'t have any feed to execute',
                $this->getId()
            ));
            return;
        }

        foreach ($this->getFeeds() as $feed) {
            if (!$feed->valid()) {
                Logger::info(sprintf(
                    'The feed(%s) is invalid. It means this feed is crawled, finished or error',
                    $feed->getId()
                ));
                continue;
            }

            if ($feed->hasResponse()) {
                Logger::info(sprintf('The feed(%s) has direct response', $feed->getId()));
                $response = new Response(Response::TYPE_STREAM);
                $response->setBody($feed->fetch());

                array_push($this->responses, $response);
            } else {
                Logger::info(sprintf(
                    'The feed(%s) doesn\'t have direct response. It will be continued processing from Database',
                    $feed->getId()
                ));
                $feed->fetch();
            }

            // Update feed to get next page, next run
            Logger::info('Create the flag to get next page of the feed');
            $feed->next();
        }
    }

    public function parserOptions()
    {
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getLimitQueryResources()
    {
        return $this->limitQueryResources;
    }

    /**
     * Check the tooth is transfer resources immediate after fetching is completed
     *
     * @return boolean
     */
    public function isTransferResources()
    {
        return (bool) $this->transferResources;
    }

    public function setUrlValidator($urlValidator)
    {
        $this->urlValidator = $urlValidator;
    }

    public function getUrlValidator()
    {
        return $this->urlValidator;
    }

    public function setUrlUseLastSplash($used = false)
    {
        $this->urlUseLastSplash = boolval($used);
    }

    public function urlUseLastSplash()
    {
        return $this->urlUseLastSplash;
    }

    public function isCrawlUrlInContent()
    {
        return true;
    }

    public function isCrawlUrlInHtml()
    {
        return false;
    }

    /**
     * @param callable|\Closure $callable
     */
    public function setFeedItemsEmptyAction($callable)
    {
        if (is_callable($callable)) {
            $this->feedItemsEmptyAction = $callable;
        }
    }

     /**
     * @return callable|\Closure|null
     */
    public function getFeedItemsEmptyAction()
    {
        return $this->feedItemsEmptyAction;
    }

    public function setFeedItemsEmptyActionArgs($args)
    {
        if (is_array($args)) {
            $this->feedItemsEmptyActionArgs = $args;
        }
    }

    public function getFeedItemsEmptyActionArgs()
    {
        return $this->feedItemsEmptyActionArgs;
    }

    /**
     * @param callable $callable
     * @return static
     */
    public function setDuplicateAction($callable)
    {
        if (is_callable($callable)) {
            $this->duplicateAction = $callable;
        }
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getDuplicateAction()
    {
        return $this->duplicateAction;
    }

    /**
     * @param array $callable
     */
    public function setDuplicateActionArgs($args)
    {
        if (is_array($args)) {
            $this->duplicateActionArgs = $args;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getDuplicateActionArgs()
    {
        return $this->duplicateActionArgs;
    }

    public function registerFeedItemCallbacks()
    {
        // Register callbacks for feed item
    }
}
