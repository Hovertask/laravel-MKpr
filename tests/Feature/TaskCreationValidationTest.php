<?php

use App\Http\Controllers\Api\V1\TaskController;
use App\Repository\ITaskRepository;
use Illuminate\Http\Request;

it('rejects task creation without mandatory fields before calling the repository', function () {
    $repository = Mockery::mock(ITaskRepository::class);
    $repository->shouldNotReceive('create');

    $response = (new TaskController($repository))->createTask(Request::create('/api/task', 'POST', []));

    expect($response->getStatusCode())->toBe(422);
    $body = $response->getData(true);
    expect($body['status'])->toBeFalse();
    expect($body['message'])->toBe('Validation failed');
    expect($body['errors'])->toHaveKeys(['user_id', 'title', 'description', 'priority', 'task_type', 'category']);
});

it('rejects invalid priority and category before persisting a task', function () {
    $repository = Mockery::mock(ITaskRepository::class);
    $repository->shouldNotReceive('create');

    $response = (new TaskController($repository))->createTask(Request::create('/api/task', 'POST', [
        'title' => 'Sample task',
        'description' => 'Task description',
        'status' => 'open',
        'priority' => 'urgent',
        'task_type' => 1,
        'category' => 'unknown-category',
    ]));

    expect($response->getStatusCode())->toBe(422);
    $errors = $response->getData(true)['errors'];
    expect($errors)->toHaveKeys(['priority', 'category']);
});
