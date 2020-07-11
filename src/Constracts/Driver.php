<?php
namespace Ramphor\Rake\Constracts;

interface Driver
{
    public function getName(): string;

    public function query(SQLQueryBuilder $query);
}
