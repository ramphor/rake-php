<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Sql as SqlBuilder;

interface Driver
{
    public function name();

    public function prefix();

    public function query(SqlBuilder $query);

    public function var(SqlBuilder $query);
}
