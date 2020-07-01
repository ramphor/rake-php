<?php
namespace Ramphor\Rake\Parsers\CSV;

use Ramphor\Rake\Abstracts\Parser as AbstractParser;
use Ramphor\Rake\DataSource\FeedItem;

class Parser extends AbstractParser
{
    protected $delimeter = ',';

    protected $enclosure = '"';

    protected $escape = '\\';

    protected $header;
    protected $contentSize;

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
            $this->header = $headerFields;
        } else {
            $this->header = (bool) $headerFields;
        }
    }

    public function key()
    {
        return $this->rowCounter;
    }

    public function current(): FeedItem
    {
        $row = fgetcsv(
            $this->data,
            $this->contentSize,
            $this->delimeter,
            $this->enclosure,
            $this->escape
        );

        if (!is_array($this->header) || count($this->header) <= 0) {
            $this->currentRow = $row;
        } else {
            $this->currentRow = array_combine($this->header, $row);
        }
        return new FeedItem($this->currentRow);
    }

    public function next()
    {
        if (is_resource($this->data)) {
            return !feof($this->data);
        }
        return false;
    }

    public function rewind()
    {
        rewind($this->data);

        $header = fgets($this->data);

        if ((bool) $this->header) {
            $this->header = str_getcsv($header, $this->delimeter, $this->enclosure, $this->escape);
        }

        $this->rowCounter  = 0;
        $this->contentSize = fstat($this->data)['size'];
    }

    public function valid()
    {
        return !feof($this->data);
    }
}
