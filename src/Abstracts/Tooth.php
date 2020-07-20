<?php
namespace Ramphor\Rake\Abstracts;

use Iterator;
use TypeError;
use Ramphor\Rake\Rake;
use Ramphor\Rake\Constracts\Tooth as ToothConstract;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\DataSource\FeedItemBuilder;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Abstracts\Processor;

use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;

use Ramphor\Rake\Exceptions\ToothFormatException;

abstract class Tooth implements ToothConstract
{
    public const FORMAT_CSV  = 'csv';
    public const FORMAT_HTML = 'html';

    protected $acceptToothFormats = [self::FORMAT_CSV, self::FORMAT_HTML];
    protected $feeds              = [];
    protected $mappingFields      = [];
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

    public function getItems(): Iterator
    {
        if (empty($this->toothFormat)) {
            throw new ToothFormatException();
        }

        $response = $this->getResponse);
        $parser   = $this->createParser(
            $response,
            $this->parserOptions()
        );

        if (empty($this->mappingFields)) {
            return $parser;
        }

        $feedItemBuilder = new FeedItemBuilder(
            $this->mappingFields,
            $this->toothFormat
        );
        return $parser->setFeedItemBuilder($feedItemBuilder);
    }

    public function execute()
    {
        // Run feeds before get feed items
        $feeds = $this->getFeeds();
        if (count($feeds) > 0) {
            foreach ($feeds as $feed) {
                $executedTimes = $feed->getOption('executed_times', 0);
                if ($feed->getLifeCycle() <= $executedTimes) {
                    continue;
                }

                $feed->execute();
                $feed->updateOption('executed_times', $executedTimes + 1);
            }
        }
    }
}
