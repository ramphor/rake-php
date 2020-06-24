<?php
namespace Ramphor\Rake\Parsers\CSV;

use Ramphor\Rake\Abstracts\AbstractParser;

abstract class AbstractCsv extends AbstractParser
{
    protected $delimeter = ',';

    protected $enclosure = '"';

    protected $escape = '\\';

    protected $document;

    public static function createFromString()
    {
        $document = fopen('php://temp', 'r+');
        fwrite($document, $csvDocumentContent);
        // $this->document = $document;
    }
}
