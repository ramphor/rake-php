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

use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;

use Ramphor\Rake\Exceptions\ToothFormatException;

abstract class Tooth implements ToothConstract
{
    public const FORMAT_CSV  = 'csv';
    public const FORMAT_HTML = 'html';

    protected $feeds              = [];
    protected $mappingFields      = [];
    protected $acceptToothFormats = [self::FORMAT_CSV, self::FORMAT_HTML];
    protected $skipCheckTooth     = false;

    protected $id;
    protected $rake;
    protected $toothFormat;
    protected $parser;
    protected $processor;

    public function __construct(Rake $rake, string $toothId)
    {
        $this->id = $toothId;
        $this->rake    = $rake;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRake():Rake
    {
        return $this->rake;
    }

    public function skipCheckToothWhenCrawl($skip = false)
    {
        $this->skipCheckTooth = (bool)$skip;
    }

    public function isSkipCheckTooth()
    {
        return $this->skipCheckTooth;
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

        return new $parsers[$this->toothFormat]($resource, $parserOptions);
    }

    public function getParsers()
    {
        if (empty($this->toothFormat) || empty($this->mappingFields)) {
            return [];
        }

        $parsers = [];
        foreach ($this->getResponses() as $response) {
            if (!($response instanceof Response)) {
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
            return;
        }
        foreach ($this->getFeeds() as $feed) {
            if (!$feed->valid()) {
                continue;
            }

            if ($feed->hasResponse()) {
                $response = new Response(Response::TYPE_STREAM);
                $response->setBody($feed->execute());

                array_push($this->responses, $response);
            } else {
                $feed->execute();
            }

            // Update feed to get next page, next run
            $feed->next();
        }
    }

    public function parserOptions()
    {
    }
}
