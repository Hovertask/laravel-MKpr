<?php

it('does not log broadcasting auth headers or request payloads', function () {
    $routes = file_get_contents(dirname(__DIR__, 2) . '/routes/api.php');
    preg_match(
        "~Route::post\\('/broadcasting/auth', function \\(Request \\$request\\) \\{(.*?)\\}\\)->middleware\\('auth:sanctum'\\);~s",
        $routes,
        $matches
    );

    expect($matches)->toHaveKey(1);
    expect($matches[1])->not->toContain('headers->all()');
    expect($matches[1])->not->toContain('request->all()');
    expect($matches[1])->toContain('Broadcast::auth($request)');
});
