<?php

namespace Waynik\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Waynik\Repository\DependencyInjectionInterface;

interface ControllerInterface
{
    public function __construct(DependencyInjectionInterface $dependencyInjector);

    public function handle(ServerRequestInterface $request);
}