<?php
namespace Ramphor\Rake;

use Ramphor\Rake\ProcessResult;
use Ramphor\Rake\Facades\DB;

class Resource
{
    protected $id;
    protected $guid;
    protected $type;
    protected $imported;
    protected $content;

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

    public function findId()
    {
        if ($this->id) {
            return $this->id;
        }

        $query = sql()->select('ID')
            ->from(DB::table('rake_resources'))
            ->where(
                'guid = ? AND resource_type = ? AND rake_id = ? AND tooth_id =?',
                $this->guid,
                $this->type,
                $this->rakeId,
                $this->toothId
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
        $this->content = $content;
    }

    public function insert()
    {
        $values = [
            '?, ?, ?, ?, ?, ?, ?, ?, @, @',
            $this->guid, $this->type, $this->rakeId, $this->toothId,
            $this->newGuid, $this->newType, (bool)$this->imported, 0, 'NOW()', 'NOW()'
        ];
        $query = sql()->insertInto(
            DB::table('rake_resources'),
            [
                'guid', 'resource_type', 'rake_id', 'tooth_id', 'new_guid', 'new_type',
                'imported', 'retry', 'created_at', 'updated_at'
            ]
        );
        $query = call_user_func_array([$query, 'values'], $values);

        return DB::insert($query);
    }

    public function update()
    {
        $query = $query->update(DB::table('rake_resources'))
            ->set([
            ])
            ->where('ID =?', $thi->id);
    }

    public function save()
    {
        return ($this->findId() > 0)
            ? $this->update()
            : $this->insert();
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
