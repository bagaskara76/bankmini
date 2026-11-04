<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeploymentEndpointTest extends TestCase
{
    public function test_it_is_unavailable_without_a_sufficiently_strong_configured_secret(): void
    {
        foreach (['', 'short'] as $secret) {
            config(['app.deploy_secret' => $secret]);

            $this->postJson('/api/deploy/migrate')
                ->assertStatus(503)
                ->assertJsonPath('status', 'error');
        }
    }

    public function test_it_rejects_an_invalid_bearer_token(): void
    {
        config(['app.deploy_secret' => str_repeat('a', 32)]);

        Artisan::shouldReceive('call')->never();

        $this->withToken('incorrect-token')
            ->postJson('/api/deploy/migrate')
            ->assertForbidden()
            ->assertJsonPath('status', 'error');
    }

    public function test_it_runs_deployment_commands_with_a_valid_bearer_token(): void
    {
        config(['app.deploy_secret' => str_repeat('a', 32)]);
        config(['filesystems.links' => []]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->once()
            ->with('storage:link', ['--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear', [])
            ->andReturn(0);

        $this->withToken(str_repeat('a', 32))
            ->postJson('/api/deploy/migrate')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }
}
