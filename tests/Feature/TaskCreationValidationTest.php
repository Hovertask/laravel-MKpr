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

it('returns a created task response after validating a complete request', function () {
    \Illuminate\Support\Facades\Schema::dropIfExists('users');
    \Illuminate\Support\Facades\Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
        $table->id();
    });
    \Illuminate\Support\Facades\DB::table('users')->insert(['id' => 42]);

    $task = new \App\Models\Task();
    $task->id = 19;
    $task->title = 'Verified task';

    $repository = Mockery::mock(ITaskRepository::class);
    $repository->shouldReceive('create')->once()->with(Mockery::on(function ($data) {
        return ($data['user_id'] ?? null) === 42
            && ($data['title'] ?? null) === 'Verified task'
            && ($data['category'] ?? null) === 'social_media';
    }))->andReturn($task);

    $response = (new TaskController($repository))->createTask(Request::create('/api/task', 'POST', [
        'user_id' => 42,
        'title' => 'Verified task',
        'description' => 'Complete a social media task',
        'status' => 'open',
        'priority' => 'medium',
        'task_type' => 1,
        'category' => 'social_media',
    ]));

    expect($response->getStatusCode())->toBe(201);
    $body = $response->getData(true);
    expect($body['status'])->toBeTrue();
    expect($body['message'])->toBe('Task created successfully');
    expect($body['data']['title'])->toBe('Verified task');
});
