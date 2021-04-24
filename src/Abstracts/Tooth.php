<?php
namespace Ramphor\Rake\Abstracts;

use TypeError;
use Ramphor\Rake\Rake;
use Ramphor\Rake\Response;
use Ramphor\Rake\Constracts\Tooth as ToothConstract;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Abstracts\Processor;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\DataSource\FeedItemBuilder;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;
use Ramphor\Rake\Exceptions\ToothFormatException;

abstract class Tooth implements ToothConstract
{
    public const FORMAT_CSV  = 'csv';
    public const FORMAT_HTML = 'html';

    protected $feeds              = [];
    protected $responses          = [];
    protected $mappingFields      = [];
    protected $acceptToothFormats = [self::FORMAT_CSV, self::FORMAT_HTML];
    protected $skipCheckTooth     = false;
    protected $transferResources  = false;
    protected $limitQueryResource = 10;

    protected $id;
    protected $rake;
    protected $toothFormat;
    protected $parser;
    protected $processor;

    public function __construct(string $toothId, Rake $rake = null)
    {
        $this->id = $toothId;
        if (!is_null($rake)) {
            $this->setRake($rake);
        }
    }

    public function setRake(Rake $rake)
    {
        $this->rake = $rake;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRake():Rake
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

    public function createParser($resource, $parserOptions = null):Parser
    {
        $parsers = [
            self::FORMAT_CSV => CsvParser::class,
            self::FORMAT_HTML => HtmlParser::class,
        ];

        Logger::debug(sprintf('Create the %s parser to build feed items', strtoupper($this->toothFormat)));
        return new $parsers[$this->toothFormat]($resource, $parserOptions);
    }

    public function getParsers()
    {
        if (empty($this->toothFormat)) {
            Logger::warning(sprintf('The %s tooth doesn\'t specific data format to processing'));
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

    public function execute()
    {
        if (count($this->getFeeds()) <= 0) {
            Logger::info(sprintf(
                'The %s tooth doesn\'t have tooth to execute',
                $this->getId()
            ));
            return;
        }
        foreach ($this->getFeeds() as $feed) {
            if (!$feed->valid()) {
                Logger::debug(sprintf(
                    'The feed(%s) is invalid. It means this feed is crawled, finished or error',
                    $feed->getId()
                ));
                continue;
            }

            if ($feed->hasResponse()) {
                Logger::debug(sprintf('The feed(%s) has direct response', $feed->getId()));
                $response = new Response(Response::TYPE_STREAM);
                $response->setBody($feed->execute());

                array_push($this->responses, $response);
            } else {
                Logger::debug(sprintf(
                    'The feed(%s) doesn\'t have direct response. It will be continued processing from Database',
                    $feed->getId()
                ));
                $feed->execute();
            }

            // Update feed to get next page, next run
            Logger::debug('Create the flag to get next page of the feed');
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

    public function limitQueryResource()
    {
        return $this->limitQueryResource;
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
}
