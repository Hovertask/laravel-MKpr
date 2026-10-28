<?php

test('composer autoload hooks do not run database migrations', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

    $hooks = $composer['scripts']['post-autoload-dump'] ?? [];
    $commands = implode("\n", $hooks);

    expect($commands)->not->toContain('artisan migrate');
});

test('the example environment contains editable postgres defaults', function () {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)->toContain('DB_CONNECTION=pgsql')
        ->toContain('DB_HOST=127.0.0.1')
        ->toContain('DB_PORT=5432')
        ->not->toContain('\$\{\{Postgres.');
});
