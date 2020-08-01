<?php
namespace Ramphor\Rake;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Facades\DB;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Facades\Logger;
use Ramphor\Rake\Facades\Resources;

class Resource
{
    protected $contentChanged = false;

    protected $id;
    protected $guid;
    protected $type;
    protected $imported;
    protected $content;

    protected $newType;
    protected $newGuid;
    protected $skipped;
    protected $tooth;

    // Parent resource
    protected $parent;

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
        $this->id = (int)$id;
    }

    public function setParent(self &$parent)
    {
        $this->parent = $parent;
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

    public function skip()
    {
        $this->skipped = true;
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
            'skipped'     => (int)$this->skipped,
            '@updated_at' => 'NOW()'
        ];
        if (!$this->imported) {
            $values['@retry'] = 'retry + 1';
        }
        if ($this->contentChanged) {
            $values['content_text'] = $this->content;
        }

        $query = sql()->update(DB::table('rake_resources'))
            ->set($values)
            ->where('ID=?', $this->id);

        return DB::query($query);
    }

    public function save($onlyCreate = false)
    {
        ($onlyCreate === false && $this->findId() > 0)
            ? $this->update()
            : $this->insert();

        if ($this->imported && $this->tooth->validateSystemResource($this->newGuid, $this->newType)) {
            if ($this->type === 'link') {
                $this->tooth->updatePostResource($this);
            }
            $parentResource = Resources::findParent($this->id);
            if (!is_null($parentResource)) {
                $this->tooth->updateSystemResource($this, $parentResource);
            }
        }
        return $this->id;
    }

    public function mapOthers($fields)
    {
        // Implement later
    }

    public function saveHash($hash, $newType, $newGuid)
    {
        $id = $this->findId();
        if ($id <= 0) {
            Logger::debug(
                'The resource is not found to save the hash',
                ['guid' => $this->guid, 'type' => $this->type]
            );
            return;
        }
        $query = sql()
            ->insertInto(DB::table('rake_hash_maps'), ['sha256', 'resource_id', 'new_guid', 'new_type', 'created_at'])
            ->values('?, ?, ?, ?, @', $hash, $id, $newGuid, $newType, 'NOW()');

        return DB::insert($query);
    }
}
