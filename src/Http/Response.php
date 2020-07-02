<?php
namespace Ramphor\Rake\Http;

use Ramphor\Rake\Constracts\Response as ResponseConstract;

final class Response implements ResponseConstract
{
    const TYPE_STREAM = 'stream';
    const TYPE_ARRAY  = 'array';

    protected $type = 'stream';
    protected $body;

    public function __construct($responeType = null)
    {
        if (!is_null($responeType)) {
            $this->setType($responeType);
        }
    }

    public function setType($responeType)
    {
        $this->type = $responeType;
    }

    public function append($guid, $body, $url = null)
    {
        $this->body[] = [
            'guid' => $guid,
            'body' => $body,
            'url' => $url,
        ];
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function body()
    {
        return $this->body;
    }
}
