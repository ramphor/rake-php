<?php

namespace Ramphor\Rake\Parsers\HTML;

use Ramphor\Rake\Abstracts\Parser as AbstractParser;
use Ramphor\Rake\DataSource\FeedItem;

class Parser extends AbstractParser
{
    protected $data;
    protected $feedBuilder;
    protected $currentIndex = 0;


    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->currentIndex;
    }

    public function current(): FeedItem
    {
        $dataItem = $this->data[$this->currentIndex];

        $this->feedBuilder->newItem($dataItem);
        $this->feedBuilder->build();

        return $this->feedBuilder->getFeedItem();
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->currentIndex = 0;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->currentIndex++;
    }


    #[\ReturnTypeWillChange]
    public function valid()
    {
        return is_array($this->data) && isset($this->data[$this->currentIndex]);
    }
}
