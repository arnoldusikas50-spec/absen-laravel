<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_application_boots(): void
    {
        $this->assertNotNull($this->app);
    }

    public function test_hash_make_and_check_roundtrip(): void
    {
        $hash = Hash::make('rahasia');

        $this->assertTrue(Hash::check('rahasia', $hash));
        $this->assertFalse(Hash::check('salah', $hash));
    }

    public function test_vite_manifest_resolves_app_css(): void
    {
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Jalankan npm run build lebih dulu.');
        }

        $this->assertStringContainsString('assets/app-', Blade::render("@vite(['resources/css/app.css'])"));
    }
}
