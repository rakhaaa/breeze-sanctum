<?php

use App\Models\Todo;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create();
});

test('user can retrieve their todos', function () {
    Sanctum::actingAs($this->user, ['*']);
    Todo::factory(5)->create(['user_id' => $this->user->id]);

    $response = getJson('/api/todos');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data');
});

test('user can create a todo', function () {
    Sanctum::actingAs($this->user, ['*']);

    $response = postJson('/api/todos', ['title' => 'New Todo']);

    $response->assertStatus(201)
        ->assertJson(['data' => ['title' => 'New Todo']]);
});

test('user can update their own todo', function () {
    Sanctum::actingAs($this->user, ['*']);
    $todo = Todo::factory()->create(['user_id' => $this->user->id]);

    $response = putJson("/api/todos/{$todo->id}", ['title' => 'Updated Todo']);

    $response->assertStatus(200)
        ->assertJson(['data' => ['title' => 'Updated Todo']]);
});

test('admin can update any todo', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $todo = Todo::factory()->create(['user_id' => $this->user->id]);

    $response = putJson("/api/todos/{$todo->id}", ['title' => 'Admin Updated Todo']);

    $response->assertStatus(200)
        ->assertJson(['data' => ['title' => 'Admin Updated Todo']]);
});

test('user cannot delete another user\'s todo', function () {
    Sanctum::actingAs($this->user, ['*']);
    $todo = Todo::factory()->create();

    $response = deleteJson("/api/todos/{$todo->id}");

    $response->assertStatus(403);
});

test('admin can delete any todo', function () {
    Sanctum::actingAs($this->admin, ['*']);
    $todo = Todo::factory()->create(['user_id' => $this->user->id]);

    $response = deleteJson("/api/todos/{$todo->id}");

    $response->assertStatus(204);
});
