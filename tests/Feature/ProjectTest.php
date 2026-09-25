<?php

use App\Models\Project;

use function Pest\Laravel\getJson;

it('cannot access without authentication', function () {
    getJson('/api/project/list')->assertStatus(401);
});

it('can get project authenticated user project list', function () {
    $user = createUser();

    $project = Project::factory()->create([
        'user_id' => $user->id
    ]);

    actingAsApi($user)->getJson("/api/task/lists/{$project->id}")
        ->assertStatus(200)
        ->assertExactJsonStructure([
            'message',
            'data'
        ]);
});

it('only returns projects owned by authenctication user', function () {
    $user = createUser();
    $otherUser = createUser();

    collect(range(1, 3))->each(function () use ($user) {
        Project::factory()->create([
            'user_id' => $user->id
        ]);
    });

    Project::factory()->create([
        'user_id' => $otherUser->id
    ]);

    $response = actingAsApi($user)
        ->getJson('/api/project/list?per_page=10')
        ->assertStatus(200);

    $projects = $response->json('data.project');

    expect($projects)->toHaveCount(3);

    foreach ($projects as $project) {
        expect($project['user_id'])->toBe($user->id);
    }
});

it('get filter project by name', function () {
    $user = createUser();

    Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'backend'
    ]);

    Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'frontend'
    ]);

    actingAsApi($user)
        ->getJson('/api/project/list-filter?name=backend')
        ->assertStatus(200)
        ->assertJsonFragment([
            'name' => 'backend'
        ]);
});

it('create a project and auto-assign an in-progress', function () {
    $user = createUser();

    actingAsApi($user)->postJson("/api/project/store", [
        'user_id' => $user->id,
        'name' => fake()->sentence
    ])->assertStatus(201);
});

// it('cannot create a project without user_id & name', function () {
//     $user = createUser();

//     $response = actingAsApi($user)
//         ->postJson("/api/project/store", [])
//         ->assertStatus(422);

//     $response
//         ->assertJsonPath(
//             'message.name.0',
//             'The name field is required.'
//         )
//         ->assertJsonPath(
//             'message.user_id.0',
//             'The user id field is required.'
//         );
// });

it('cannot access owned project by another user', function () {
    $userA = createUser();
    $userB = createUser();

    $project = Project::factory()->create([
        'user_id' => $userA->id
    ]);

    actingAsApi($userB)->getJson("/api/project/show/{$project->id}")
        ->assertStatus(403);

    // expect($project->refresh()->name)
    //     ->not->toBe('project update');
});

it('cannot delete owned by another userr', function () {
    $userA = createUser();
    $userB = createUser();

    $project = Project::factory()->create([
        'user_id' => $userA->id
    ]);

    actingAsApi($userB)
        ->deleteJson("/api/project/destroy/{$project->id}")
        ->assertStatus(403);

    expect(Project::find($project->id))
        ->not->toBeNull();
});
