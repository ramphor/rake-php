<?php
namespace Ramphor\Rake\Constracts;

interface Driver
{
    public function query(SQLQueryBuilder $query);
}
