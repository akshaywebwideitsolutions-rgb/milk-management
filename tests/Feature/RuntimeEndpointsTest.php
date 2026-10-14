<?php

namespace Tests\Feature;

use Tests\TestCase;

class RuntimeEndpointsTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_web_uses_its_own_api_instead_of_desktop_backend(): void
    {
        config(['app.api_base_url' => null, 'app.desktop_api_base_url' => 'https://dairy.example/api']);
        $this->get('/login')->assertOk()
            ->assertSee('content="http://localhost/api"', false)
            ->assertDontSee('https://dairy.example/api', false);
    }

    public function test_desktop_uses_configured_shared_web_backend(): void
    {
        config(['nativephp-internal.running' => true, 'app.desktop_api_base_url' => 'https://dairy.example/api']);
        $this->get('/login')->assertOk()->assertSee('content="https://dairy.example/api"', false);
    }

    public function test_desktop_can_preflight_authenticated_api_calls(): void
    {
        $this->withHeaders([
            'Origin' => 'http://127.0.0.1:8100',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('/api/login')->assertSuccessful()
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:8100');
    }

    public function test_web_logout_revokes_bearer_token_and_session(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('desktop-app');
        $this->actingAs($user, 'web')->withHeader('Origin', 'http://localhost')->withToken($token->plainTextToken)
            ->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertGuest('web');
    }
}
