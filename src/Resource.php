<?php
namespace Ramphor\Rake;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Abstracts\Tooth;

class Resource
{
    protected $contentChanged = false;
    protected $relations      = [];

    protected $id;
    protected $guid;
    protected $type;
    protected $imported;
    protected $content;

    protected $newType;
    protected $newGuid;
    protected $tooth;

    public static function create($guid, $type, Tooth &$tooth): self
    {
        return new static($guid, $type, $tooth);
    }

    public function __construct($guid, $resourceType, Tooth &$tooth)
    {
        $this->guid = $guid;
        $this->type = $resourceType;
        $this->tooth = $tooth;
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

    public function getTooth()
    {
        return $this->tooth;
    }

    public function findId()
    {
        if ($this->id) {
            return $this->id;
        }
        $rake = $this->tooth->getRake();

        $query = sql()->select('ID')
            ->from(DB::table('rake_resources'))
            ->where(
                'guid = ? AND resource_type = ? AND rake_id = ? AND tooth_id =?',
                $this->guid,
                $this->type,
                $rake->getId(),
                $this->tooth->getId()
            );

        return $this->id = (int)DB::var($query);
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
        $this->contentChanged = $content != $this->content;
        $this->content        = $content;
    }

    public function insert()
    {
        $rake   = $this->tooth->getRake();
        $values = [
            '?, ?, ?, ?, ?, ?, ?, ?, ?, @, @',
            $this->guid, $this->type, $rake->getId(), $this->tooth->getId(), $this->newGuid,
            $this->newType, (int)$this->imported, 0, $this->content, 'NOW()', 'NOW()'
        ];

        $query = sql()->insertInto(
            DB::table('rake_resources'),
            [
                'guid', 'resource_type', 'rake_id', 'tooth_id', 'new_guid', 'new_type',
                'imported', 'retry', 'content_text', 'created_at', 'updated_at'
            ]
        );
        $query = call_user_func_array([$query, 'values'], $values);

        return $this->id = DB::insert($query);
    }

    public function update()
    {
        $values = [
            'new_guid'    => $this->newGuid,
            'new_type'    => $this->newType,
            'imported'    => (int)$this->imported,
            '@updated_at' => 'NOW()'
        ];
        if (!$this->imported) {
            $values['@retry'] = 'retry + 1';
        }
        if ($this->contentChanged) {
            $values['content'] = $this->content;
        }

        $query = sql()->update(DB::table('rake_resources'))
            ->set($values)
            ->where('ID=?', $this->id);

        return DB::query($query);
    }

    public function save()
    {
        ($this->findId() > 0)
            ? $this->update()
            : $this->insert();

        if ($this->imported && $this->tooth->validateSystemResource($this->newGuid, $this->newType)) {
            if ($this->type === 'link') {
                $this->tooth->updateSystemResource($this);
            }
            $parentResource = $this->getParent();
            $childResources = $this->getChildrens();
            if (!is_null($parentResource)) {
                $this->tooth->updateParentSystemResource($this, $parentResource);
            }
            if (count($childResources) > 0) {
                $this->tooth->updateChildSystemResource($this, $childResources);
            }
        }
        return $this->id;
    }

    public function getParent()
    {
    }

    public function getChildrens()
    {
    }

    public function getRelations()
    {
        return $this->relations;
    }

    public function addRelation(Resource &$resource, $type = 'child')
    {
        if (!in_array($type, ['parent', 'child', 'cover', 'gallery'])) {
            return;
        }

        if (!isset($this->relations[$type])) {
            $this->relations[$type] = [];
        }
        array_push($this->relations[$type], $resource);
    }

    public function mapOthers($fields)
    {
        // Implement later
    }

    public function saveHash($hash, $newType, $newGuid)
    {
        $id = $this->findId();
        if ($id <= 0) {
            // Will be logging later
            return;
        }
        $query = sql()
            ->insertInto(DB::table('rake_hash_maps'), ['sha256', 'resource_id', 'new_guid', 'new_type', 'created_at'])
            ->values('?, ?, ?, ?, @', $hash, $id, $newGuid, $newType, 'NOW()');

        return DB::insert($query);
    }
}
