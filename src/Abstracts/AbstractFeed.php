<?php
namespace Ramphor\Rake\Abstracts;

use Iterator;
use TypeError;
use Ramphor\Rake\Constracts\Feed;
use Ramphor\Rake\Abstracts\AbstractPreprocessor;

use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;
use Ramphor\Rake\Parsers\XML\Parser as XmlParser;
use Ramphor\Rake\Parsers\JSON\Parser as JsonParser;

use Ramphor\Rake\Exceptions\FeedFormatException;

abstract class AbstractFeed implements Feed
{
    public const FORMAT_CSV      = 'csv';
    public const FORMAT_XML      = 'xml';
    public const FORMAT_JSON     = 'json';
    public const FORMAT_HTML     = 'html';

    protected $acceptFeedFormats = [
        self::FORMAT_CSV,
        self::FORMAT_XML,
        self::FORMAT_JSON,
        self::FORMAT_HTML
    ];
    protected $preprocessors = [];

    protected $feedId;
    protected $feedFormat;
    protected $parser;

    public function __construct(string $feedId = null)
    {
        $this->setId($feedId);
    }

    public function setId(string $feedId)
    {
        $this->feedId = $feedId;
    }

    public function getId()
    {
        return $this->feedId;
    }

    public function setFeedFormat($format)
    {
        if (!in_array($format, $this->acceptFeedFormats)) {
            throw new FeedFormatException();
        }
        $this->feedFormat = $format;
    }

    public function addPreprocessor(AbstractPreprocessor $preprocessor)
    {
        if (!isset($this->preprocessors[$preprocessor->getId()])) {
            $this->preprocessors[$preprocessor->getId()] = $preprocessor;
        } else {
            throw new \Exception("Preprocessor [%s] is existings", $preprocessor->getId());
        }
    }

    public function getPreprocessors()
    {
        return $this->preprocessors;
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
            throw new FeedFormatException();
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
