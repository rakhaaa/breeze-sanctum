<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('authenticated user can create an API token', function () {
    // Create a user
    $user = User::factory()->create();

    // Authenticate as the user
    Sanctum::actingAs($user, ['*']);

    // Send a POST request to create a token
    $response = postJson('/tokens/create', ['token_name' => 'Test Token']);

    // Assert the response status is 200
    $response->assertStatus(200);

    // Assert the response contains a token
    $response->assertJsonStructure(['token']);
});
