<?php
namespace Ramphor\Rake\Abstracts;

use Iterator;
use TypeError;
use Ramphor\Rake\Rake;
use Ramphor\Rake\Constracts\Tooth;
use Ramphor\Rake\DataSource\FeedItem;
use Ramphor\Rake\Abstracts\AbstractFeed;
use Ramphor\Rake\Abstracts\AbstractProcessor;

use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;
use Ramphor\Rake\Parsers\XML\Parser as XmlParser;
use Ramphor\Rake\Parsers\JSON\Parser as JsonParser;

use Ramphor\Rake\Exceptions\ToothFormatException;

abstract class AbstractTooth extends TemplateMethod implements Tooth
{
    public const FORMAT_CSV      = 'csv';
    public const FORMAT_XML      = 'xml';
    public const FORMAT_JSON     = 'json';
    public const FORMAT_HTML     = 'html';

    protected $acceptToothFormats = [
        self::FORMAT_CSV,
        self::FORMAT_XML,
        self::FORMAT_JSON,
        self::FORMAT_HTML
    ];
    protected $feeds = [];

    protected $id;
    protected $rake;
    protected $toothFormat;
    protected $parser;

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

    public function registerProcessor(string $processorClassName)
    {
        if (!class_exists($processorClassName)) {
            throw new ResourceException("Processor must be a class name");
        }
        $this->processorClassName = $processorClassName;
    }

    public function createProcessor(FeedItem $feedItem): AbstractProcessor
    {
        $processorClassName = $this->processorClassName;
        if (empty($processorClassName)) {
            throw new ResourceException("Processor must be have value");
        }

        $processor = new $processorClassName($feedItem);
        $processor->setDriver($this->driver);
        $processor->setHttpClient($this->httpClient);

        return $processor;
    }

    public function setFormat($format)
    {
        if (!in_array($format, $this->acceptToothFormats)) {
            throw new ToothFormatException();
        }
        $this->feedFormat = $format;
    }

    public function addFeed(AbstractFeed $feed)
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

    public function createParser($resource, $parserOptions = null):AbstractParser
    {
        $parsers = [
            self::FORMAT_CSV => CsvParser::class,
            self::FORMAT_XML => XmlParser::class,
            self::FORMAT_JSON => JsonParser::class,
            self::FORMAT_HTML => HtmlParser::class,
        ];

        return new $parsers[$this->feedFormat]($resource, $parserOptions);
    }

    protected function createStreamFronString($response)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response);

        return $stream;
    }

    public function getItems(): Iterator
    {
        if (empty($this->feedFormat)) {
            throw new ToothFormatException();
        }
        $response = $this->fetch();
        $stream   = $this->createStreamFronString($response);

        return $this->createParser($stream, $this->parserOptions());
    }

    public function closeStream()
    {
        @fclose($this->stream);
    }
}
