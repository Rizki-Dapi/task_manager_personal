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

it('keeps project in-progress while at least one task is not completed', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id,
    ]);

    $taskOne = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in-progress',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in-progress',
    ]);

    actingAsApi($user)
        ->putJson("/api/task/update/{$taskOne->id}", ['status' => 'completed'])
        ->assertStatus(200);

    expect($project->refresh()->status)->toBe('in-progress');
});

it('marks project as completed once all of its tasks are completed', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id,
    ]);

    $taskOne = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'completed',
    ]);

    $taskTwo = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in-progress',
    ]);

    actingAsApi($user)
        ->putJson("/api/task/update/{$taskTwo->id}", ['status' => 'completed'])
        ->assertStatus(200);

    expect($project->refresh()->status)->toBe('completed');
});

it('reverts project back to in-progress when a completed task is reopened', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'completed',
    ]);

    actingAsApi($user)
        ->putJson("/api/task/update/{$task->id}", ['status' => 'in-progress'])
        ->assertStatus(200);

    expect($project->refresh()->status)->toBe('in-progress');
});

it('reverts a completed project to in-progress when a new task is added', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    actingAsApi($user)->postJson('/api/task/store', [
        'project_id' => $project->id,
        'title' => fake()->sentence(),
        'description' => fake()->paragraph(),
    ])->assertStatus(201);

    expect($project->refresh()->status)->toBe('in-progress');
});

it('marks project as completed when the last unfinished task is deleted', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'completed',
    ]);

    $unfinishedTask = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'in-progress',
    ]);

    actingAsApi($user)
        ->deleteJson("/api/task/destroy/{$unfinishedTask->id}")
        ->assertStatus(200);

    expect($project->refresh()->status)->toBe('completed');
});
