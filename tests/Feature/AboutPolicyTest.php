<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\About;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AboutPolicyTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_admin_can_update_about()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Assuming 1 is admin role
        $about = About::factory()->create();

        // Simulate acting as admin
        $this->assertTrue($admin->can('update', $about));
    }

    public function test_non_admin_cannot_update_about()
    {
        $user = User::factory()->create(['role_id' => 2]); // Assuming 2 is user role
        $about = About::factory()->create();

        // Simulate acting as a non-admin user
        $this->assertFalse($user->can('update', $about));
    }
}