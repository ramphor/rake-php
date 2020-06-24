<?php
namespace Ramphor\Rake;

class Resource
{
    public $id;
    public $guid;
    public $url;
    public $text;

    public $newURL;
    public $newGuid;

    public $resourceType;

    public $isDownloaded;

    public function getRelations()
    {
    }
}
