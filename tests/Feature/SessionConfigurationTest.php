<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionConfigurationTest extends TestCase
{
    public function test_session_cookie_name_is_php_cookie_safe(): void
    {
        $this->assertSame('dr_sam_session', config('session.cookie'));
        $this->assertDoesNotMatchRegularExpression('/[^A-Za-z0-9_]/', config('session.cookie'));
    }
}
