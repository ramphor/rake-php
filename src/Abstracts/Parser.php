<?php
namespace Ramphor\Rake\Abstracts;

use TypeError;
use Ramphor\Rake\Constracts\Parser as ParserConstract;

abstract class Parser implements ParserConstract
{
    protected $data;

    public function __construct($response, $parserOptions = null)
    {
        if ($response->getType() === $response::TYPE_STREAM) {
            $this->data = new $this->createStreamFronString($response->getBody());
        } else {
            $this->data = $response->getBody();
        }

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

    protected function createStreamFronString($response)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response);

        return $stream;
    }
}
