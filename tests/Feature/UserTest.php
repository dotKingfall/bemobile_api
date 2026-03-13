<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    //REIMPLEMENTING ROLE TESTS TO MAKE SURE
    public function test_finance_cannot_access_user_crud()
    {
        $financeUser = User::factory()->create(['role' => 'finance']);

        $response = $this->actingAs($financeUser)->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_role_assignment_is_case_insensitive()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name'     => 'Case Test User',
            'email'    => 'casetest@example.com',
            'password' => 'password123',
            'role'     => ' MaNaGeR ' 
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'casetest@example.com',
            'role'  => 'manager' 
        ]);
    }

    public function test_authorized_roles_can_manage_users()
    {
        $authorizedRoles = ['admin', 'manager'];

        foreach ($authorizedRoles as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($actor)->postJson('/api/users', [
                'name'     => "New $role User",
                'email'    => "test_{$role}@example.com",
                'password' => 'password123',
                'role'     => 'user'
            ]);

            $response->assertStatus(201);
            $this->assertDatabaseHas('users', ['email' => "test_{$role}@example.com"]);
        }
    }

    public function test_cannot_create_user_with_invalid_role()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name'     => 'Invalid Role User',
            'email'    => 'invalid_role@example.com',
            'password' => 'password123',
            'role'     => 'super-god-mode'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role']);
    }
}
