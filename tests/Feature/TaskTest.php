<?php

use App\Models\Project;
use App\Models\Task;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('cannot access without authentication', function () {
    postJson('/api/task/store')->assertStatus(401);
});

it('can get task authenticated user project list', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id
    ]);

    Task::factory()->count(3)->create([
        'project_id' => $project->id
    ]);

    actingAsApi($user)->getJson("/api/task/lists/{$project->id}")
        ->assertStatus(200)
        ->assertExactJsonStructure([
            'message',
            'data'
        ]);
});

it('only returns tasks by project id', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id
    ]);

    $otherProject = Project::factory()->create([
        'user_id' => $user->id
    ]);

    collect(range(1, 5))->each(function () use ($project) {
        Task::factory()->create([
            'project_id' => $project->id
        ]);
    });

    Task::factory()->create([
        'project_id' => $otherProject->id
    ]);

    $response = actingAsApi($user)
        ->getJson("/api/task/lists/{$project->id}")
        ->assertStatus(200);

    $task = $response->json('data.tasks');

    expect($task)->toHaveCount(5);
});

it('cannot access owned task by another user', function () {
    $user = createUser();
    $otherUser = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id
    ]);

    actingAsApi($otherUser)
        ->getJson("/api/task/{$task->id}")
        ->assertStatus(403);
});
