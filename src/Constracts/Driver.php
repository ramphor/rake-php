<?php
namespace Ramphor\Rake\Constracts;

interface Driver
{
    public function getName();

    public function getPrefix();

    public function query(SQLQueryBuilder $query);
}
