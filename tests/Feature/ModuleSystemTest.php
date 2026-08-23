<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleDiscovery;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Tests\TestCase;

class ModuleSystemTest extends TestCase
{
    use RefreshDatabase;

    private function registry(): ModuleRegistry
    {
        return app(ModuleDiscovery::class)->discover([base_path('modules')]);
    }

    public function test_it_discovers_installed_modules(): void
    {
        $modules = $this->registry()->all();

        $this->assertNotEmpty($modules);
        $this->assertArrayHasKey('module-manager', $modules);
        $this->assertInstanceOf(Manifest::class, $modules['module-manager']);
    }

    public function test_it_validates_and_lists_the_composed_module_registry(): void
    {
        $this->artisan('module:validate')->assertExitCode(0);
        $this->artisan('module:list')->assertExitCode(0);
    }

    public function test_it_exposes_module_metadata_and_features(): void
    {
        $registry = $this->registry();
        $manifest = $registry->get('module-manager');

        $this->assertNotNull($manifest);
        $this->assertSame('module-manager', $manifest->name());
        $this->assertNotEmpty($manifest->features());
        $this->assertNotEmpty($registry->searchFeatures('module'));
    }

    public function test_deployment_overrides_are_external_to_the_registry(): void
    {
        $registry = $this->registry();

        $this->assertIsArray(config('modules.enabled'));
        $this->assertIsArray(config('modules.disabled'));
        $this->assertArrayHasKey('module-manager', $registry->all());
        $this->assertNull($registry->get('missing-module'));
    }
}
