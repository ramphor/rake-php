<?php
namespace Ramphor\Rake\Parsers\HTML;

use Ramphor\Rake\Abstracts\Parser as AbstractParser;
use Ramphor\Rake\DataSource\FeedItem;

class Parser extends AbstractParser
{
    protected $currentIndex = 0;

    public function key()
    {
        return $this->currentIndex;
    }

    public function current(): FeedItem
    {
        $dataItem = $this->data[ $this->currentIndex];
        $this->feedBuilder->newItem($dataItem);
        $this->feedBuilder->build();

        return $this->feedBuilder->getFeedItem();
    }

    public function rewind()
    {
        $this->currentIndex = 0;
    }

    public function next()
    {
        $this->currentIndex++;
    }

    public function valid()
    {
        return is_array($this->data) && isset($this->data[$this->currentIndex]);
    }
}
