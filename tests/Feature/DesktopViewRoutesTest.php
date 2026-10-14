<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesktopViewRoutesTest extends TestCase
{
    public function test_authenticated_desktop_views_render(): void
    {
        $user = User::factory()->make(['id' => 1]);

        foreach ([
            '/dashboard',
            '/farmers',
            '/farmers/create',
            '/farmers/1/edit',
            '/milk-collection',
            '/milk-rates',
            '/bill-cycles',
            '/deductions',
            '/invoices',
            '/invoices/1',
            '/reports',
            '/farmer-history',
            '/settings',
        ] as $path) {
            $this->actingAs($user)->get($path)->assertOk();
        }
    }
}
