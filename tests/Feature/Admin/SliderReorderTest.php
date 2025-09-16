<?php

namespace Tests\Feature\Admin;

use App\Models\Slider;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SliderReorderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create regular user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function admin_can_reorder_sliders_successfully()
    {
        Sanctum::actingAs($this->admin);

        // Create test sliders
        $slider1 = Slider::factory()->create(['position' => 1, 'title' => 'First Slider']);
        $slider2 = Slider::factory()->create(['position' => 2, 'title' => 'Second Slider']);
        $slider3 = Slider::factory()->create(['position' => 3, 'title' => 'Third Slider']);

        $reorderData = [
            'slider_ids' => [$slider3->id, $slider1->id, $slider2->id]
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);

        // Verify the new order
        $this->assertDatabaseHas('sliders', ['id' => $slider3->id, 'position' => 1]);
        $this->assertDatabaseHas('sliders', ['id' => $slider1->id, 'position' => 2]);
        $this->assertDatabaseHas('sliders', ['id' => $slider2->id, 'position' => 3]);
    }

    /** @test */
    public function admin_can_reorder_sliders_with_partial_list()
    {
        Sanctum::actingAs($this->admin);

        // Create test sliders
        $slider1 = Slider::factory()->create(['position' => 1, 'title' => 'First Slider']);
        $slider2 = Slider::factory()->create(['position' => 2, 'title' => 'Second Slider']);
        $slider3 = Slider::factory()->create(['position' => 3, 'title' => 'Third Slider']);
        $slider4 = Slider::factory()->create(['position' => 4, 'title' => 'Fourth Slider']);

        // Only reorder first 3 sliders
        $reorderData = [
            'slider_ids' => [$slider2->id, $slider1->id, $slider3->id]
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);

        // Verify the reordered sliders
        $this->assertDatabaseHas('sliders', ['id' => $slider2->id, 'position' => 1]);
        $this->assertDatabaseHas('sliders', ['id' => $slider1->id, 'position' => 2]);
        $this->assertDatabaseHas('sliders', ['id' => $slider3->id, 'position' => 3]);
        
        // Fourth slider should remain unchanged
        $this->assertDatabaseHas('sliders', ['id' => $slider4->id, 'position' => 4]);
    }

    /** @test */
    public function reorder_handles_empty_slider_ids_array()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/sliders/reorder', []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);
    }

    /** @test */
    public function reorder_handles_missing_slider_ids_field()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/sliders/reorder', []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);
    }

    /** @test */
    public function reorder_validation_requires_slider_ids_to_be_array()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/sliders/reorder', [
            'slider_ids' => 'not-an-array'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slider_ids']);
    }

    /** @test */
    public function reorder_validation_requires_slider_ids_to_be_integers()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/sliders/reorder', [
            'slider_ids' => ['not', 'integers']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slider_ids.0', 'slider_ids.1']);
    }

    /** @test */
    public function reorder_validation_requires_slider_ids_to_exist()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/sliders/reorder', [
            'slider_ids' => [999, 998, 997] // Non-existent IDs
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slider_ids.0', 'slider_ids.1', 'slider_ids.2']);
    }

    /** @test */
    public function reorder_validation_requires_mixed_existing_and_non_existing_ids()
    {
        Sanctum::actingAs($this->admin);

        $slider = Slider::factory()->create();

        $response = $this->postJson('/api/admin/sliders/reorder', [
            'slider_ids' => [$slider->id, 999, 998] // Mix of existing and non-existing
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slider_ids.1', 'slider_ids.2']);
    }

    /** @test */
    public function regular_user_cannot_reorder_sliders()
    {
        Sanctum::actingAs($this->user);

        $slider1 = Slider::factory()->create(['position' => 1]);
        $slider2 = Slider::factory()->create(['position' => 2]);

        $reorderData = [
            'slider_ids' => [$slider2->id, $slider1->id]
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_reorder_sliders()
    {
        $slider1 = Slider::factory()->create(['position' => 1]);
        $slider2 = Slider::factory()->create(['position' => 2]);

        $reorderData = [
            'slider_ids' => [$slider2->id, $slider1->id]
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(401);
    }


    /** @test */
    public function reorder_handles_single_slider()
    {
        Sanctum::actingAs($this->admin);

        $slider = Slider::factory()->create(['position' => 5, 'title' => 'Single Slider']);

        $reorderData = [
            'slider_ids' => [$slider->id]
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);

        // Should be positioned at 1
        $this->assertDatabaseHas('sliders', ['id' => $slider->id, 'position' => 1]);
    }

    /** @test */
    public function reorder_handles_duplicate_slider_ids()
    {
        Sanctum::actingAs($this->admin);

        $slider1 = Slider::factory()->create(['position' => 1, 'title' => 'First Slider']);
        $slider2 = Slider::factory()->create(['position' => 2, 'title' => 'Second Slider']);

        $reorderData = [
            'slider_ids' => [$slider1->id, $slider2->id, $slider1->id] // Duplicate ID
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);

        // The duplicate should be handled gracefully
        $this->assertDatabaseHas('sliders', ['id' => $slider1->id, 'position' => 1]);
        $this->assertDatabaseHas('sliders', ['id' => $slider2->id, 'position' => 2]);
    }

    /** @test */
    public function reorder_maintains_other_sliders_positions()
    {
        Sanctum::actingAs($this->admin);

        // Create sliders with gaps in positions
        $slider1 = Slider::factory()->create(['position' => 1, 'title' => 'First']);
        $slider2 = Slider::factory()->create(['position' => 3, 'title' => 'Third']);
        $slider3 = Slider::factory()->create(['position' => 5, 'title' => 'Fifth']);
        $slider4 = Slider::factory()->create(['position' => 7, 'title' => 'Seventh']);

        // Only reorder first 3 sliders
        $reorderData = [
            'slider_ids' => [$slider3->id, $slider1->id, $slider2->id]
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(200);

        // Verify reordered sliders
        $this->assertDatabaseHas('sliders', ['id' => $slider3->id, 'position' => 1]);
        $this->assertDatabaseHas('sliders', ['id' => $slider1->id, 'position' => 2]);
        $this->assertDatabaseHas('sliders', ['id' => $slider2->id, 'position' => 3]);
        
        // Fourth slider should remain unchanged
        $this->assertDatabaseHas('sliders', ['id' => $slider4->id, 'position' => 7]);
    }

    /** @test */
    public function reorder_works_with_large_number_of_sliders()
    {
        Sanctum::actingAs($this->admin);

        // Create 10 sliders
        $sliders = [];
        for ($i = 1; $i <= 10; $i++) {
            $sliders[] = Slider::factory()->create(['position' => $i, 'title' => "Slider $i"]);
        }

        // Reverse the order
        $reorderData = [
            'slider_ids' => array_reverse(array_column($sliders, 'id'))
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);

        // Verify the reversed order
        for ($i = 0; $i < 10; $i++) {
            $expectedPosition = $i + 1;
            $sliderId = $sliders[9 - $i]->id; // Reversed order
            $this->assertDatabaseHas('sliders', ['id' => $sliderId, 'position' => $expectedPosition]);
        }
    }

    /** @test */
    public function reorder_response_has_correct_structure()
    {
        Sanctum::actingAs($this->admin);

        $slider1 = Slider::factory()->create(['position' => 1]);
        $slider2 = Slider::factory()->create(['position' => 2]);

        $reorderData = [
            'slider_ids' => [$slider2->id, $slider1->id]
        ];

        $response = $this->postJson('/api/admin/sliders/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Sliders reordered successfully'
            ]);
    }
}