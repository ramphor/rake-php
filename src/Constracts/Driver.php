<?php
namespace Ramphor\Rake\Constracts;

interface Driver
{
    public function dbQuery($sql);

    public function createDbTable($tableName, $syntaxContent);
}
