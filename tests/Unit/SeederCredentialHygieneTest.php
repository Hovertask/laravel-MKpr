<?php

it('never hardcodes predictable Laratrust seed passwords', function () {
    $source = file_get_contents(dirname(__DIR__, 2) . '/database/seeders/LaratrustSeeder.php');
    expect($source)->toContain("env('SEED_ADMIN_PASSWORD')");
    expect($source)->toContain('Str::random(32)');
    expect($source)->not->toContain("$" . "plainPassword = 'hovertask'");
    expect($source)->not->toContain("$" . "plainPassword = 'password'");
});
