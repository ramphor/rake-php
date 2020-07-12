<?php
namespace Ramphor\Rake\Constracts;

use Ramphor\Sql as SqlBuilder;

interface Driver
{
    public function getName();

    public function getPrefix();

    public function query(SqlBuilder $query);
}
