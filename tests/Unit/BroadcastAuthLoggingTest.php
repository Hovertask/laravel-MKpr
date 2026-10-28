<?php

it('does not log broadcasting auth headers or request payloads', function () {
    $routes = file_get_contents(dirname(__DIR__, 2) . '/routes/api.php');
    $start = strpos($routes, "Route::post('/broadcasting/auth'");
    expect($start)->not->toBeFalse();

    $end = strpos($routes, "->middleware('auth:sanctum');", $start);
    expect($end)->not->toBeFalse();

    $route = substr($routes, $start, $end - $start);
    expect($route)->not->toContain('headers->all()');
    expect($route)->not->toContain('request->all()');
    expect($route)->toContain('Broadcast::auth($request)');
});
