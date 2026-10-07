<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunityDashboardController extends Controller
{
    public function admin(Request $request, DashboardRegistry $registry): View
    {
        abort_unless($registry->modulesFor($request->user())->contains('key', 'community_admin'), 403);

        return view('community.dashboard', ['adminMode' => true]);
    }

    public function explore(): View
    {
        return view('community.dashboard', ['adminMode' => false]);
    }
}
