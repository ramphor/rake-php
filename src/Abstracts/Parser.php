<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\Parser as ParserConstract;
use Ramphor\Rake\DataSource\FeedItemBuilder;
use Psr\Http\Message\StreamInterface;
use Ramphor\Rake\Response;

abstract class Parser implements ParserConstract
{
    protected $data;
    protected $feedBuilder;

    public function __construct($response, $parserOptions = null)
    {
        if ($response->getType() === Response::TYPE_STREAM) {
            $body = $response->getBody();
            $this->data = is_resource($body)
                ? $body
                : $this->createStreamFronString(
                    ($body instanceof StreamInterface) ? $body->getContents() : $body
                );
        } else {
            // This case data type is array or other
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

    public function setFeedItemBuilder(FeedItemBuilder $feedBuilder): self
    {
        $this->feedBuilder = $feedBuilder;
        return $this;
    }

    protected function createStreamFronString($response)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response);

        return $stream;
    }
}
