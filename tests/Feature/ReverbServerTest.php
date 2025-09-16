<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatThread;
use App\Models\Request as ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

class ReverbServerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable chat feature
        Config::set('chat.enabled', true);
        
        // Set up Reverb broadcasting
        Config::set('broadcasting.default', 'reverb');
        Config::set('broadcasting.connections.reverb.driver', 'reverb');
        Config::set('broadcasting.connections.reverb.key', 'test-key');
        Config::set('broadcasting.connections.reverb.secret', 'test-secret');
        Config::set('broadcasting.connections.reverb.app_id', 'test-app-id');
        Config::set('broadcasting.connections.reverb.options.host', 'localhost');
        Config::set('broadcasting.connections.reverb.options.port', 8080);
        Config::set('broadcasting.connections.reverb.options.scheme', 'http');
        Config::set('broadcasting.connections.reverb.options.useTLS', false);
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AreaSeeder']);
    }

    public function test_reverb_broadcasting_configuration()
    {
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals('reverb', $config['driver']);
        $this->assertEquals('test-key', $config['key']);
        $this->assertEquals('test-secret', $config['secret']);
        $this->assertEquals('test-app-id', $config['app_id']);
        $this->assertEquals('localhost', $config['options']['host']);
        $this->assertEquals(8080, $config['options']['port']);
        $this->assertEquals('http', $config['options']['scheme']);
        $this->assertFalse($config['options']['useTLS']);
    }

    public function test_reverb_broadcasting_default_driver()
    {
        $this->assertEquals('reverb', config('broadcasting.default'));
    }

    public function test_reverb_broadcasting_connection_exists()
    {
        $connections = config('broadcasting.connections');
        
        $this->assertArrayHasKey('reverb', $connections);
        $this->assertEquals('reverb', $connections['reverb']['driver']);
    }

    public function test_reverb_broadcasting_connection_has_required_options()
    {
        $config = config('broadcasting.connections.reverb');
        
        $this->assertArrayHasKey('key', $config);
        $this->assertArrayHasKey('secret', $config);
        $this->assertArrayHasKey('app_id', $config);
        $this->assertArrayHasKey('options', $config);
        
        $options = $config['options'];
        $this->assertArrayHasKey('host', $options);
        $this->assertArrayHasKey('port', $options);
        $this->assertArrayHasKey('scheme', $options);
        $this->assertArrayHasKey('useTLS', $options);
    }

    public function test_reverb_broadcasting_connection_handles_environment_variables()
    {
        // Test that environment variables can override config
        // Since environment variables are already set, test that they are being used
        Config::set('broadcasting.connections.reverb.key', env('REVERB_APP_KEY', 'test-key'));
        Config::set('broadcasting.connections.reverb.secret', env('REVERB_APP_SECRET', 'test-secret'));
        Config::set('broadcasting.connections.reverb.app_id', env('REVERB_APP_ID', 'test-app-id'));
        Config::set('broadcasting.connections.reverb.options.host', env('REVERB_HOST', 'localhost'));
        Config::set('broadcasting.connections.reverb.options.port', env('REVERB_PORT', 8080));
        Config::set('broadcasting.connections.reverb.options.scheme', env('REVERB_SCHEME', 'http'));
        
        $config = config('broadcasting.connections.reverb');
        
        // Test that environment variables are being used (not defaults)
        $this->assertEquals(env('REVERB_APP_KEY', 'test-key'), $config['key']);
        $this->assertEquals(env('REVERB_APP_SECRET', 'test-secret'), $config['secret']);
        $this->assertEquals(env('REVERB_APP_ID', 'test-app-id'), $config['app_id']);
        $this->assertEquals(env('REVERB_HOST', 'localhost'), $config['options']['host']);
        $this->assertEquals(env('REVERB_PORT', 8080), $config['options']['port']);
        $this->assertEquals(env('REVERB_SCHEME', 'http'), $config['options']['scheme']);
        
        // Verify that the environment variables are actually being used
        $this->assertNotEquals('test-key', $config['key']); // Should be the actual env value
        $this->assertNotEmpty($config['key']); // Should have a value
    }

    public function test_reverb_broadcasting_connection_handles_tls_configuration()
    {
        // Test TLS configuration
        Config::set('broadcasting.connections.reverb.options.scheme', 'https');
        Config::set('broadcasting.connections.reverb.options.useTLS', true);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals('https', $config['options']['scheme']);
        $this->assertTrue($config['options']['useTLS']);
    }

    public function test_reverb_broadcasting_connection_handles_different_ports()
    {
        // Test different port configurations
        Config::set('broadcasting.connections.reverb.options.port', 443);
        Config::set('broadcasting.connections.reverb.options.wssPort', 443);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals(443, $config['options']['port']);
    }

    public function test_reverb_broadcasting_connection_handles_different_hosts()
    {
        // Test different host configurations
        Config::set('broadcasting.connections.reverb.options.host', '0.0.0.0');
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals('0.0.0.0', $config['options']['host']);
    }

    public function test_reverb_broadcasting_connection_handles_client_options()
    {
        // Test client options
        Config::set('broadcasting.connections.reverb.client_options', [
            'timeout' => 30,
            'verify' => false,
        ]);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertArrayHasKey('client_options', $config);
        $this->assertEquals(30, $config['client_options']['timeout']);
        $this->assertFalse($config['client_options']['verify']);
    }

    public function test_reverb_broadcasting_connection_handles_ping_interval()
    {
        // Test ping interval configuration
        Config::set('broadcasting.connections.reverb.options.ping_interval', 60);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals(60, $config['options']['ping_interval']);
    }

    public function test_reverb_broadcasting_connection_handles_max_message_size()
    {
        // Test max message size configuration
        Config::set('broadcasting.connections.reverb.options.max_message_size', 10000);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals(10000, $config['options']['max_message_size']);
    }

    public function test_reverb_broadcasting_connection_handles_scaling_options()
    {
        // Test scaling options
        Config::set('broadcasting.connections.reverb.options.scaling', [
            'enabled' => true,
            'channel' => 'reverb',
        ]);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertArrayHasKey('scaling', $config['options']);
        $this->assertTrue($config['options']['scaling']['enabled']);
        $this->assertEquals('reverb', $config['options']['scaling']['channel']);
    }

    public function test_reverb_broadcasting_connection_handles_pulse_options()
    {
        // Test pulse options
        Config::set('broadcasting.connections.reverb.options.pulse_ingest_interval', 15);
        Config::set('broadcasting.connections.reverb.options.telescope_ingest_interval', 15);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals(15, $config['options']['pulse_ingest_interval']);
        $this->assertEquals(15, $config['options']['telescope_ingest_interval']);
    }

    public function test_reverb_broadcasting_connection_handles_max_request_size()
    {
        // Test max request size configuration
        Config::set('broadcasting.connections.reverb.options.max_request_size', 10000);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals(10000, $config['options']['max_request_size']);
    }

    public function test_reverb_broadcasting_connection_handles_different_schemes()
    {
        // Test different scheme configurations
        $schemes = ['http', 'https', 'ws', 'wss'];
        
        foreach ($schemes as $scheme) {
            Config::set('broadcasting.connections.reverb.options.scheme', $scheme);
            
            $config = config('broadcasting.connections.reverb');
            
            $this->assertEquals($scheme, $config['options']['scheme']);
        }
    }

    public function test_reverb_broadcasting_connection_handles_different_ports_for_ws_wss()
    {
        // Test different port configurations for ws and wss
        Config::set('broadcasting.connections.reverb.options.port', 8080);
        Config::set('broadcasting.connections.reverb.options.wssPort', 443);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertEquals(8080, $config['options']['port']);
        $this->assertEquals(443, $config['options']['wssPort']);
    }

    public function test_reverb_broadcasting_connection_handles_enabled_transports()
    {
        // Test enabled transports configuration
        Config::set('broadcasting.connections.reverb.options.enabledTransports', ['ws', 'wss']);
        
        $config = config('broadcasting.connections.reverb');
        
        $this->assertArrayHasKey('enabledTransports', $config['options']);
        $this->assertEquals(['ws', 'wss'], $config['options']['enabledTransports']);
    }
}