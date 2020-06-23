<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Constracts\Resource as ResourceConstract;

abstract class Resource implements ResourceConstract
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
