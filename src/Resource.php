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

    protected $maps = [];

    public static function create($guid, $type, $rakeId, $toothId): self
    {
        return new static($guid, $type, $rakeId, $toothId);
    }

    public function __construct($guid, $resourceType, $rakeId, $toothId)
    {
        $this->guid = $guid;
        $this->type = $resourceType;
        $this->rakeId = $rakeId;
        $this->toothId = $toothId;
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

    public function imported()
    {
        $this->imported = true;
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

    public function mapWith(Resource &$resource, $type = 'child')
    {
        if (!in_array($type, ['parent', 'child', 'cover', 'gallery'])) {
            return;
        }

        if (!isset($this->maps[$type])) {
            $this->maps[$type] = [];
        }
        array_push($this->maps[$type], $resource);
    }
}
