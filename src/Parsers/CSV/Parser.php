<?php
namespace Ramphor\Rake\Parsers\CSV;

use Ramphor\Rake\Abstracts\AbstractParser;
use Ramphor\Rake\DataSource\FeedIterator;

class Parser extends AbstractParser
{
    protected $delimeter = ',';

    protected $enclosure = '"';

    protected $escape = '\\';

    protected $document;


    public function parse():FeedIterator
    {
        var_dump($this->stream);
        die;
    }
}
