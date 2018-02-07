<?php

namespace Waynik\Repository;

interface DataConnectionInterface
{
    public function query($sqlString, array $params);
    public function create($sqlString, array $params);
}