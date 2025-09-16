<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Config;
use Mockery;
use App\Services\StreamService;

class StreamControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private StreamService $streamService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable chat feature
        Config::set('chat.enabled', true);
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AreaSeeder']);
        
        // Create test users
        $this->user = User::factory()->create(['role_id' => 2]); // User role
        $this->admin = User::factory()->create(['role_id' => 1]); // Admin role
        
        // Set up Stream.io configuration for testing
        Config::set('services.stream.api_key', 'test_api_key');
        Config::set('services.stream.api_secret', 'test_api_secret');
        Config::set('services.stream.app_id', 'test_app_id');
        Config::set('services.stream.api_base', 'https://chat.stream-io-api.com');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function authenticated_user_can_get_stream_token()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/stream/token');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'api_key',
                'user_id',
                'app_id'
            ])
            ->assertJson([
                'api_key' => 'test_api_key',
                'user_id' => $this->user->id,
                'app_id' => 'test_app_id'
            ]);

        // Verify token is a valid JWT format
        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_stream_token()
    {
        $response = $this->getJson('/api/stream/token');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    /** @test */
    public function stream_token_contains_correct_user_id()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/stream/token');

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $this->user->id
            ]);
    }

    /** @test */
    public function stream_token_has_valid_jwt_structure()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/stream/token');

        $response->assertStatus(200);
        
        $token = $response->json('token');
        $parts = explode('.', $token);
        
        // JWT should have 3 parts (header.payload.signature)
        $this->assertCount(3, $parts);
        
        // Each part should be base64 encoded
        foreach ($parts as $part) {
            $this->assertNotEmpty($part);
        }
    }

    /** @test */
    public function authenticated_user_can_create_stream_user()
    {
        Sanctum::actingAs($this->user);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'image' => 'https://example.com/avatar.jpg'
        ];

        // Mock the StreamService
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('createUser')
            ->with($this->user->id, $userData)
            ->andReturn(['id' => $this->user->id, 'name' => 'Test User']);

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/users', $userData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User created successfully in Stream.io',
                'data' => ['id' => $this->user->id, 'name' => 'Test User']
            ]);
    }

    /** @test */
    public function authenticated_user_can_update_stream_user()
    {
        Sanctum::actingAs($this->user);

        $userData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com'
        ];

        // Mock the StreamService
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('updateUser')
            ->with($this->user->id, $userData)
            ->andReturn(['id' => $this->user->id, 'name' => 'Updated User']);

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->putJson('/api/stream/users', $userData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User updated successfully in Stream.io',
                'data' => ['id' => $this->user->id, 'name' => 'Updated User']
            ]);
    }

    /** @test */
    public function authenticated_user_can_create_channel()
    {
        Sanctum::actingAs($this->user);

        $channelData = [
            'channel_type' => 'messaging',
            'channel_id' => 'test-channel-123',
            'members' => ['user1', 'user2'],
            'name' => 'Test Channel'
        ];

        // Mock the StreamService
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('createChannel')
            ->with('messaging', 'test-channel-123', ['user1', 'user2'], ['name' => 'Test Channel'])
            ->andReturn(['id' => 'messaging:test-channel-123', 'name' => 'Test Channel']);

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/channels', $channelData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Channel created successfully in Stream.io',
                'data' => ['id' => 'messaging:test-channel-123', 'name' => 'Test Channel']
            ]);
    }

    /** @test */
    public function create_channel_requires_channel_type()
    {
        Sanctum::actingAs($this->user);

        $channelData = [
            'channel_id' => 'test-channel-123',
            'members' => ['user1', 'user2']
        ];

        // Mock the StreamService to prevent actual API calls
        $streamServiceMock = Mockery::mock(StreamService::class);
        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/channels', $channelData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel_type']);
    }

    /** @test */
    public function create_channel_requires_channel_id()
    {
        Sanctum::actingAs($this->user);

        $channelData = [
            'channel_type' => 'messaging',
            'members' => ['user1', 'user2']
        ];

        // Mock the StreamService to prevent actual API calls
        $streamServiceMock = Mockery::mock(StreamService::class);
        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/channels', $channelData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel_id']);
    }

    /** @test */
    public function authenticated_user_can_add_channel_members()
    {
        Sanctum::actingAs($this->user);

        $memberData = [
            'channel_type' => 'messaging',
            'channel_id' => 'test-channel-123',
            'members' => ['user3', 'user4']
        ];

        // Mock the StreamService
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('addChannelMembers')
            ->with('messaging', 'test-channel-123', ['user3', 'user4'])
            ->andReturn(['id' => 'messaging:test-channel-123', 'members' => ['user3', 'user4']]);

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/channels/members', $memberData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Members added successfully to channel in Stream.io',
                'data' => ['id' => 'messaging:test-channel-123', 'members' => ['user3', 'user4']]
            ]);
    }

    /** @test */
    public function add_channel_members_requires_members_array()
    {
        Sanctum::actingAs($this->user);

        $memberData = [
            'channel_type' => 'messaging',
            'channel_id' => 'test-channel-123'
        ];

        // Mock the StreamService to prevent actual API calls
        $streamServiceMock = Mockery::mock(StreamService::class);
        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/channels/members', $memberData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['members']);
    }

    /** @test */
    public function authenticated_user_can_send_message()
    {
        Sanctum::actingAs($this->user);

        $messageData = [
            'channel_type' => 'messaging',
            'channel_id' => 'test-channel-123',
            'message' => 'Hello, this is a test message!'
        ];

        // Mock the StreamService
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('sendMessage')
            ->with('messaging', 'test-channel-123', 'Hello, this is a test message!', $this->user->id, [])
            ->andReturn(['id' => 'msg-123', 'text' => 'Hello, this is a test message!']);

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/messages', $messageData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Message sent successfully in Stream.io',
                'data' => ['id' => 'msg-123', 'text' => 'Hello, this is a test message!']
            ]);
    }

    /** @test */
    public function send_message_requires_message_text()
    {
        Sanctum::actingAs($this->user);

        $messageData = [
            'channel_type' => 'messaging',
            'channel_id' => 'test-channel-123'
        ];

        // Mock the StreamService to prevent actual API calls
        $streamServiceMock = Mockery::mock(StreamService::class);
        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/messages', $messageData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function send_message_requires_channel_type()
    {
        Sanctum::actingAs($this->user);

        $messageData = [
            'channel_id' => 'test-channel-123',
            'message' => 'Hello, this is a test message!'
        ];

        // Mock the StreamService to prevent actual API calls
        $streamServiceMock = Mockery::mock(StreamService::class);
        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/messages', $messageData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel_type']);
    }

    /** @test */
    public function send_message_requires_channel_id()
    {
        Sanctum::actingAs($this->user);

        $messageData = [
            'channel_type' => 'messaging',
            'message' => 'Hello, this is a test message!'
        ];

        // Mock the StreamService to prevent actual API calls
        $streamServiceMock = Mockery::mock(StreamService::class);
        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/messages', $messageData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel_id']);
    }

    /** @test */
    public function stream_service_handles_api_failures_gracefully()
    {
        Sanctum::actingAs($this->user);

        // Mock the StreamService to return false (API failure)
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('createUser')
            ->with($this->user->id, [])
            ->andReturn(false);

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/users', []);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Failed to create user in Stream.io'
            ]);
    }

    /** @test */
    public function stream_service_handles_exceptions_gracefully()
    {
        Sanctum::actingAs($this->user);

        // Mock the StreamService to throw an exception
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('generateToken')
            ->andThrow(new \Exception('JWT generation failed'));

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->getJson('/api/stream/token');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Failed to generate token',
                'message' => 'JWT generation failed'
            ]);
    }

    /** @test */
    public function admin_user_can_access_all_stream_endpoints()
    {
        Sanctum::actingAs($this->admin);

        // Mock the StreamService for all operations
        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('generateToken')
            ->andReturn('mock-jwt-token');
        $streamServiceMock->shouldReceive('getApiKey')
            ->andReturn('test_api_key');
        $streamServiceMock->shouldReceive('getAppId')
            ->andReturn('test_app_id');
        $streamServiceMock->shouldReceive('createUser')
            ->andReturn(['id' => $this->admin->id, 'name' => 'Admin User']);
        $streamServiceMock->shouldReceive('createChannel')
            ->andReturn(['id' => 'messaging:admin-channel']);
        
        $this->app->instance(StreamService::class, $streamServiceMock);

        // Test token generation
        $response = $this->getJson('/api/stream/token');
        $response->assertStatus(200);

        // Test user creation
        $response = $this->postJson('/api/stream/users', ['name' => 'Admin User']);
        $response->assertStatus(200);

        // Test channel creation
        $response = $this->postJson('/api/stream/channels', [
            'channel_type' => 'messaging',
            'channel_id' => 'admin-channel'
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function stream_endpoints_accept_additional_data()
    {
        Sanctum::actingAs($this->user);

        // Test channel creation with additional data
        $channelData = [
            'channel_type' => 'messaging',
            'channel_id' => 'test-channel-123',
            'members' => ['user1'],
            'name' => 'Test Channel',
            'description' => 'A test channel',
            'custom_data' => ['key' => 'value']
        ];

        $streamServiceMock = Mockery::mock(StreamService::class);
        $streamServiceMock->shouldReceive('createChannel')
            ->with('messaging', 'test-channel-123', ['user1'], [
                'name' => 'Test Channel',
                'description' => 'A test channel',
                'custom_data' => ['key' => 'value']
            ])
            ->andReturn(['id' => 'messaging:test-channel-123']);

        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/channels', $channelData);
        $response->assertStatus(200);
    }

    /** @test */
    public function stream_endpoints_validate_member_array_items()
    {
        Sanctum::actingAs($this->user);

        $memberData = [
            'channel_type' => 'messaging',
            'channel_id' => 'test-channel-123',
            'members' => [123, 'invalid', null] // Mixed types including invalid
        ];

        // Mock the StreamService to prevent actual API calls
        $streamServiceMock = Mockery::mock(StreamService::class);
        $this->app->instance(StreamService::class, $streamServiceMock);

        $response = $this->postJson('/api/stream/channels/members', $memberData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['members.0', 'members.2']); // Invalid array items
    }
}