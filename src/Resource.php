<?php
namespace Ramphor\Rake;

use Ramphor\Rake\ProcessResult;

class Resource
{
    protected $id;
    protected $guid;
    protected $type;
    protected $imported;
    protected $content;
    protected $contentHash;

    protected $newType;
    protected $newGuid;
    protected $rakeId;
    protected $toothId;

    public function __construct($guid, $resourceType)
    {
        $this->guid = $guid;
        $this->type = $resourceType;
    }

    public static function createFromResult(ProcessResult $result)
    {
    }

    public function __get($name)
    {
        if (property_exists(__CLASS__, $name)) {
            return $this->$name;
        }
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNewGuid($newGuid)
    {
        $this->newGuid = $newGuid;
    }

    public function setNewType($newType)
    {
        $this->newType = $newType;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function generateContentHash()
    {
    }

    public function save()
    {
    }

    public function getRelations()
    {
    }
}
