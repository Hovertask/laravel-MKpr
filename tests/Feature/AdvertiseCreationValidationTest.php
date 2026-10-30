<?php

use App\Http\Controllers\Api\V1\AdvertiseController;
use App\Repository\IAdvertiseRepository;
use App\Repository\TaskRepository;
use Illuminate\Http\Request;

it('rejects an advert missing title and media before creating a campaign', function () {
    $advert = Mockery::mock(IAdvertiseRepository::class);
    $advert->shouldNotReceive('create');
    $tasks = Mockery::mock(TaskRepository::class);
    $tasks->shouldNotReceive('create');

    $request = Request::create('/api/advertise', 'POST', [
        'type' => 'advert',
        'platforms' => 'instagram',
        'estimated_cost' => 1000,
    ]);
    $response = (new AdvertiseController($advert, $tasks))->create($request);

    expect($response->getStatusCode())->toBe(400);
    $errors = $response->getData(true)['error'];
    expect($errors)->toHaveKeys(['title']);
    expect(array_key_exists('file_path', $errors) || array_key_exists('video_path', $errors))->toBeTrue();
});

it('rejects engagement campaigns with invalid participants, payment and deadline', function () {
    $advert = Mockery::mock(IAdvertiseRepository::class);
    $advert->shouldNotReceive('create');
    $tasks = Mockery::mock(TaskRepository::class);
    $tasks->shouldNotReceive('create');

    $request = Request::create('/api/advertise', 'POST', [
        'type' => 'engagement',
        'platforms' => 'instagram',
        'estimated_cost' => 1000,
        'number_of_participants' => 0,
        'payment_per_task' => 0,
        'deadline' => '2000-01-01',
    ]);
    $response = (new AdvertiseController($advert, $tasks))->create($request);

    expect($response->getStatusCode())->toBe(400);
    expect($response->getData(true)['error'])
        ->toHaveKeys(['number_of_participants', 'payment_per_task', 'deadline']);
});
