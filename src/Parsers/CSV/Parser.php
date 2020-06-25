<?php
namespace Ramphor\Rake\Parsers\CSV;

use Ramphor\Rake\Abstracts\AbstractParser;

class Parser extends AbstractParser
{
    protected $delimeter = ',';

    protected $enclosure = '"';

    protected $escape = '\\';

    protected $document;
}
