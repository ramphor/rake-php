<?php
namespace Ramphor\Rake\Abstracts;

use TypeError;
use Ramphor\Rake\Constracts\Feed;
use Ramphor\Rake\DataSource\FeedIterator;

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

    protected $feedFormat;

    public function setFeedFormat($format)
    {
        if (!in_array($format, $this->acceptFeedFormats)) {
            throw new FeedFormatException();
        }
        $this->feedFormat = $format;
    }

    public function createParser($resource):AbstractParser
    {
        $parsers = [
            self::FORMAT_CSV => CsvParser::class,
            self::FORMAT_XML => XmlParser::class,
            self::FORMAT_JSON => JsonParser::class,
            self::FORMAT_HTML => HtmlParser::class,
        ];
        return new $parsers[$this->feedFormat]($resource);
    }

    protected function createStreamFronString($response)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response);
        rewind($stream);

        return $stream;
    }

    public function convert($stream): FeedIterator
    {
        if (empty($this->feedFormat)) {
            throw new FeedFormatException();
        }

        $parser = $this->createParser($stream);

        return $parser->parse();
    }

    public function getItems(): FeedIterator
    {
        $response = $this->fetch();
        $stream   = $this->createStreamFronString($response);

        return $this->convert($stream);
    }
}
