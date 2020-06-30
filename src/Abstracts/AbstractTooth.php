<?php
namespace Ramphor\Rake\Abstracts;

use Iterator;
use TypeError;
use Ramphor\Rake\Constracts\Tooth;
use Ramphor\Rake\Abstracts\AbstractPreprocessor;
use Ramphor\Rake\Abstracts\AbstractHttpClient;

use Ramphor\Rake\Parsers\HTML\Parser as HtmlParser;
use Ramphor\Rake\Parsers\CSV\Parser as CsvParser;
use Ramphor\Rake\Parsers\XML\Parser as XmlParser;
use Ramphor\Rake\Parsers\JSON\Parser as JsonParser;

use Ramphor\Rake\Exceptions\ToothFormatException;

abstract class AbstractTooth implements Tooth
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
    protected $preprocessors = [];

    protected $teetchId;
    protected $toothId;
    protected $toothFormat;
    protected $parser;
    protected $httpClient;

    public function __construct(string $rakeID, string $toothId = null)
    {
        $this->rakeId = $teetchId;

        $this->setId($toothId);
    }

    public function setId(string $toothId)
    {
        $this->feedId = $toothId;
    }

    public function getId()
    {
        return $this->feedId;
    }

    public function setToothFormat($format)
    {
        if (!in_array($format, $this->acceptToothFormats)) {
            throw new ToothFormatException();
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

    public function setHttpClient(AbstractHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
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
