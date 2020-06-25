<?php
namespace Ramphor\Rake\Abstracts;

use TypeError;
use Ramphor\Rake\Constracts\Parser;

abstract class AbstractParser implements Parser
{
    protected $stream;

    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new TypeError(sprintf('Argument passed must be a stream resource, %s given', gettype($stream)));
        }

        $this->stream = $stream;
    }
}
