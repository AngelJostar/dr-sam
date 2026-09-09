<?php

namespace Tests\Unit;

use App\Support\MixtureAuthorizationPolicy;
use PHPUnit\Framework\TestCase;

class MixtureAuthorizationPolicyTest extends TestCase
{
    public function test_npt_requires_nursing_and_inpatient_pharmacy(): void
    {
        $this->assertSame(['nursing', 'pharmacy'], MixtureAuthorizationPolicy::requirements('npt'));
        $this->assertFalse(MixtureAuthorizationPolicy::allApproved([
            'authorizations' => ['nursing' => 'approved', 'pharmacy' => 'pending'],
        ], 'npt'));
        $this->assertTrue(MixtureAuthorizationPolicy::allApproved([
            'authorizations' => ['nursing' => 'approved', 'pharmacy' => 'approved'],
        ], 'npt'));
    }

    public function test_oncology_requires_oncology_center_and_inpatient_pharmacy(): void
    {
        $this->assertSame(['oncology', 'pharmacy'], MixtureAuthorizationPolicy::requirements('chemo'));
        $this->assertFalse(MixtureAuthorizationPolicy::allApproved([
            'authorizations' => ['oncology' => 'pending', 'pharmacy' => 'approved'],
        ], 'chemo'));
        $this->assertTrue(MixtureAuthorizationPolicy::allApproved([
            'authorizations' => ['oncology' => 'approved', 'pharmacy' => 'approved'],
        ], 'chemo'));
    }

    public function test_legacy_operational_authorization_is_read_as_nursing(): void
    {
        $this->assertTrue(MixtureAuthorizationPolicy::allApproved([
            'authorizations' => ['operational' => 'approved', 'pharmacy' => 'approved'],
        ], 'npt'));
    }

    public function test_cbta_approval_and_later_stages_lock_cancellation_in_dr_sam(): void
    {
        $this->assertFalse(MixtureAuthorizationPolicy::cancellationLockedByCbta(null));
        $this->assertFalse(MixtureAuthorizationPolicy::cancellationLockedByCbta('materialized'));
        $this->assertTrue(MixtureAuthorizationPolicy::cancellationLockedByCbta('authorized'));
        $this->assertTrue(MixtureAuthorizationPolicy::cancellationLockedByCbta('dispensed'));
        $this->assertTrue(MixtureAuthorizationPolicy::cancellationLockedByCbta('preparing'));
        $this->assertTrue(MixtureAuthorizationPolicy::cancellationLockedByCbta('ready'));
        $this->assertTrue(MixtureAuthorizationPolicy::cancellationLockedByCbta('in_route'));
        $this->assertTrue(MixtureAuthorizationPolicy::cancellationLockedByCbta('delivered'));
    }
}
