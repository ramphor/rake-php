<?php

namespace Ramphor\Rake\Constracts;

use Ramphor\Sql as SqlBuilder;

interface Driver
{
    public function name();

    public function prefix();

    public function query(SqlBuilder $query);

    public function get(SqlBuilder $query, $classToMap = null);

    public function var(SqlBuilder $query);

    public function row(SqlBuilder $query);

    public function exists(SqlBuilder $query);

    public function insert(SqlBuilder $query);

    public function raw_query($sql);
}
