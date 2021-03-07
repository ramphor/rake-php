<?php
namespace Ramphor\Rake\Parsers\CSV;

use Ramphor\Rake\Abstracts\Parser as AbstractParser;
use Ramphor\Rake\DataSource\FeedItem;

class Parser extends AbstractParser
{
    protected $delimeter = ',';
    protected $enclosure = '"';
    protected $escape    = '\\';
    protected $hasHeader = false;
    protected $header    = [];

    protected $contentSize;
    protected $currentRow;
    protected $feedBuilder;

    public function setDelimeter(string $delimeter = ',')
    {
        $this->delimeter = $delimeter;
    }

    public function setEnclosure(string $enclosure = '"')
    {
        $this->enclosure = $enclosure;
    }

    public function setEscape($escape = '\\')
    {
        $this->escape = $escape;
    }

    public function setHeader($headerFields = true)
    {
        if (is_array($headerFields)) {
            $this->hasHeader = true;
            $this->header = $headerFields;
        } else {
            $this->hasHeader = (bool) $headerFields;
        }
    }

    public function key()
    {
        return $this->rowCounter;
    }

    public function current(): FeedItem
    {
        $this->feedBuilder->newItem($this->currentRow);
        $this->feedBuilder->build();

        return $this->feedBuilder->getFeedItem();
    }

    public function next()
    {
        $row = fgetcsv(
            $this->data,
            $this->contentSize,
            $this->delimeter,
            $this->enclosure,
            $this->escape
        );

        if ($row) {
            if (!is_array($this->header) || count($this->header) <= 0) {
                $this->currentRow = $row;
            } else {
                $this->currentRow = array_combine($this->header, $row);
            }
        } else {
            $this->currentRow = false;
        }
    }

    public function rewind()
    {
        rewind($this->data);

        if ($this->hasHeader) {
            $header = fgetcsv(
                $this->data,
                $this->contentSize,
                $this->delimeter,
                $this->enclosure,
                $this->escape
            );

            if (empty($this->header)) {
                $this->header = $header;
            }
        }

        $this->rowCounter  = 0;
        $this->contentSize = fstat($this->data)['size'];
    }

    public function valid()
    {
        return $this->currentRow !== false;
    }
}
