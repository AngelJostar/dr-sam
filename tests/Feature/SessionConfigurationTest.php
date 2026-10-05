<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\TokenMismatchException;

class SessionConfigurationTest extends TestCase
{
    public function test_expired_browser_form_redirects_to_a_fresh_login(): void
    {
        Route::post('/test-expired-form', fn () => throw new TokenMismatchException());
        $this->post('/test-expired-form')->assertRedirect(route('login', ['session_expired' => 1]));
    }

    public function test_expired_json_request_keeps_419_status(): void
    {
        Route::post('/test-expired-json', fn () => throw new TokenMismatchException());
        $this->postJson('/test-expired-json')->assertStatus(419);
    }

    public function test_session_cookie_name_is_php_cookie_safe(): void
    {
        $this->assertSame('dr_sam_session', config('session.cookie'));
        $this->assertDoesNotMatchRegularExpression('/[^A-Za-z0-9_]/', config('session.cookie'));
    }
}
