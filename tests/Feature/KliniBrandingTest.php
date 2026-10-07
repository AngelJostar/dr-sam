<?php

namespace Tests\Feature;

use Tests\TestCase;

class KliniBrandingTest extends TestCase
{
    public function test_public_home_precedes_the_existing_access_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Muévete.')
            ->assertSee('Conecta.')
            ->assertSee('Sé parte.')
            ->assertSee('data-access-url="'.route('login').'"', false)
            ->assertSee('klini-landing.js')
            ->assertDontSee('demo-login-form')
            ->assertDontSee('Dr. Sam');

        $this->assertSame('/acceso', parse_url(route('login'), PHP_URL_PATH));
        $this->assertSame('Klini', config('app.name'));
    }

    public function test_landing_assets_are_available_locally(): void
    {
        $response = $this->get('/')->assertOk();
        preg_match_all('/(?:src|href)="([^"#]+)"/', $response->getContent(), $matches);

        foreach ($matches[1] as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            if (str_starts_with($path, '/brand/') || str_starts_with($path, '/css/') || str_starts_with($path, '/js/')) {
                $this->assertFileExists(public_path(ltrim($path, '/')));
            }
        }
    }
}
