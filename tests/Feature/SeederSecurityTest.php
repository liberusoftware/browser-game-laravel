<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GameSeeder;
use Database\Seeders\PlayerRankingSeeder;
use Database\Seeders\PlayerTrackingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeding_uses_configured_credentials_without_printing_them(): void
    {
        $this->app->instance('env', 'production');
        config(['app.seed_admin_password' => 'private-configured-seed-password']);
        ob_start();
        try {
            $exitCode = Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertSame(0, $exitCode);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('private-configured-seed-password', $admin->password));
        $this->assertStringNotContainsString('private-configured-seed-password', $output.Artisan::output());
        $this->assertStringNotContainsString('Admin password:', $output.Artisan::output());
    }

    public function test_demo_account_seeders_do_not_run_in_production(): void
    {
        $this->app->instance('env', 'production');
        foreach ([GameSeeder::class, PlayerRankingSeeder::class, PlayerTrackingDemoSeeder::class] as $seeder) {
            app($seeder)->run();
        }

        $this->assertDatabaseCount('users', 0);
    }
}
