<?php

namespace Ramphor\Rake\Parsers\CSV;

use Ramphor\Rake\Abstracts\Parser as AbstractParser;
use Ramphor\Rake\DataSource\FeedItem;

class Parser extends AbstractParser
{
    protected $data;
    protected $feedBuilder;

    protected $delimeter = ',';
    protected $enclosure = '"';
    protected $escape    = '\\';
    protected $hasHeader = false;
    protected $header    = [];

    protected $contentSize;
    protected $rowCounter;

    /**
     * The current row processing
     * @var int
     */
    protected $currentRow;

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

    public function next(): void
    {
        $row = fgetcsv(
            $this->data,
            $this->contentSize,
            $this->delimeter,
            $this->enclosure,
            $this->escape
        );
        $this->rowCounter += 1;

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

    public function rewind(): void
    {
        rewind($this->data);

        if ($this->hasHeader) {
            $header = fgetcsv(
                $this->data,
                0,
                $this->delimeter,
                $this->enclosure,
                $this->escape
            );

            if (empty($this->header)) {
                $this->header = $header;
            }
        }

        $firstRow = fgetcsv(
            $this->data,
            $this->contentSize,
            $this->delimeter,
            $this->enclosure,
            $this->escape
        );

        $this->rowCounter  = 0;

        if ($firstRow) {
            $this->currentRow  = $this->header ? array_combine($this->header, $firstRow) : $firstRow;
        } else {
            $this->currentRow = false;
        }
        $this->contentSize = fstat($this->data)['size'];
    }

    public function valid(): bool
    {
        return $this->currentRow !== false;
    }
}
