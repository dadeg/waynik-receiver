<?php

use Aura\Router\RouterContainer;
use Zend\Diactoros\ServerRequestFactory;
use Waynik\Repository\DependencyInjectionContainer;

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

// Set up routes
$routerContainer = new RouterContainer();
$map = $routerContainer->getMap();

$map->post('receiver', '/receiver', 'Waynik\Controllers\Receiver')
    ->allows(['PATCH', 'PUT', 'GET']);

try {
    $dependencyInjector = new DependencyInjectionContainer();

    // Set up request
    $request = ServerRequestFactory::fromGlobals(
        $_SERVER,
        $_GET,
        $_POST,
        $_COOKIE,
        $_FILES
    );

    // Authenticate
    $headers = $request->getHeaders();
    $queryParams = $request->getQueryParams();

    $userModel = $dependencyInjector->make('UserModel');
    $user = $userModel->authenticate(array_merge($headers, $queryParams));
    // Add user to Request
    $request = $request->withAttribute('user', $user);

    // Find matching route
    $matcher = $routerContainer->getMatcher();

    /**
     * @var Psr\Http\Message\ServerRequestInterface $request
     */
    $route = $matcher->match($request);

    if (!$route) {
        // No matching route
        throw new \Aura\Router\Exception("Bad Request.", 400);
    }

    // Extract attributes from route
    foreach ($route->attributes as $key => $val) {
        $request = $request->withAttribute($key, $val);
    }

    // Create handler
    $actionClass = $route->handler;

    /** @var Waynik\Controllers\ControllerInterface $controller */
    $controller = new $actionClass($dependencyInjector);

    // Dispatch to application
    $controller->handle($request);

} catch (\Exception $exception) {
    $response = new \Zend\Diactoros\Response\JsonResponse($exception->getMessage());
    echo $response->getBody();
}
