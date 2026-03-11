<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_req_valid_email_format(){
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
    }

    public function test_protected_routes_need_auth(){
        $response = $this->getJson('/api/user'); //NO AUTH/TOKEN AT ALL :D
        $response->assertStatus(401);
    }

    public function test_role_check_is_case_insensitive(){
        $user = User::factory()->create(['role' => ' ADMIN',]);

        $response = $this->actingAs($user)->getJson('/api/user'); // TODO CHANGE THIS ROUTE FOR ADMIN PROTECTED ROUTE

        $response->assertStatus(200);
    }

    public function test_user_with_insufficient_role_gets_403(){
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->getJson('/api/user'); // TODO CHANGE THIS ROUTE FOR ADMIN PROTECTED ROUTE

        $response->assertStatus(403);
    }

}
