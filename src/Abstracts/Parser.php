<?php
namespace Ramphor\Rake\Abstracts;

use TypeError;
use Ramphor\Rake\Constracts\Parser as ParserConstract;

abstract class Parser implements ParserConstract
{
    protected $stream;

    public function __construct($stream, $parserOptions = null)
    {
        if (!is_resource($stream)) {
            throw new TypeError(sprintf('Argument passed must be a stream resource, %s given', gettype($stream)));
        }
        $this->stream = $stream;

        if (is_array($parserOptions) && count($parserOptions) > 0) {
            foreach ($parserOptions as $option => $value) {
                $callback = [
                    $this,
                    sprintf('set%s', ucfirst($option))
                ];
                if (!is_callable($callback)) {
                    continue;
                }
                call_user_func($callback, $value);
            }
        }
    }
}
