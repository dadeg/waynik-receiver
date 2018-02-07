<?php

namespace Waynik\Repository;

interface DependencyInjectionInterface
{
    public function make($className);
}