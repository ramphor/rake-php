<?php
namespace Ramphor\Rake\Abstracts;

use Iterator;
use TypeError;
use Ramphor\Rake\Rake;
use Ramphor\Rake\Constracts\Tooth as ToothConstract;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Abstracts\Feed;
use Ramphor\Rake\Abstracts\Processor;

use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;

use Ramphor\Rake\Exceptions\ToothFormatException;

abstract class Tooth extends TemplateMethod implements ToothConstract
{
    public const FORMAT_CSV  = 'csv';
    public const FORMAT_HTML = 'html';

    protected $acceptToothFormats = [self::FORMAT_CSV, self::FORMAT_HTML];
    protected $feeds              = [];
    protected $mappingFields      = [];

    protected $id;
    protected $rake;
    protected $toothFormat;
    protected $parser;
    protected $processor;

    public function __construct(Rake $rake, string $toothId)
    {
        $this->setId($toothId);
        $this->setDriver($rake->getDriver());
        $this->setHttpClient($rake->getHttpClient());
        $this->setRake($rake);
    }

    public function setRake(Rake $rake)
    {
        $this->rake = $rake;
    }

    public function getRake():Rake
    {
        return $this->rake;
    }

    public function registerProcessor(Processor $processor)
    {
        $processor->setDriver($this->driver);
        $processor->setHttpClient($this->httpClient);
        $this->processor = $processor;
    }

    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    public function setFormat($format)
    {
        if (!in_array($format, $this->acceptToothFormats)) {
            throw new ToothFormatException();
        }
        $this->feedFormat = $format;
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

        return new $parsers[$this->feedFormat]($resource, $parserOptions);
    }

    public function setMappingFields(array $mappingFields)
    {
        $this->mappingFields = $mappingFields;
    }

    public function getItems(): Iterator
    {
        if (empty($this->feedFormat)) {
            throw new ToothFormatException();
        }

        $response = $this->fetch();
        return $this->createParser(
            $response,
            $this->parserOptions()
        );
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
