<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create();
});

test('admin can retrieve all users', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $response = getJson('/api/users');

    $response->assertStatus(200)
             ->assertJsonCount(User::count(), 'data');
});

test('admin can create a new user', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $newUserData = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'role' => 'user',
    ];

    $response = postJson('/api/users', $newUserData);

    $response->assertStatus(201)
             ->assertJson(['data' => ['name' => 'New User']]);
});

test('admin can update an existing user', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $updateUserData = [
        'name' => 'Updated User',
        'email' => 'updated@example.com',
    ];

    $response = putJson("/api/users/{$this->user->id}", $updateUserData);

    $response->assertStatus(200)
             ->assertJson(['data' => ['name' => 'Updated User']]);
});

test('admin can delete a user', function () {
    Sanctum::actingAs($this->admin, ['*']);

    $response = deleteJson("/api/users/{$this->user->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
});