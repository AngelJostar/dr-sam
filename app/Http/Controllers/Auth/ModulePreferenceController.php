<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Http\Request;

class ModulePreferenceController extends Controller
{
    public function store(Request $request, DashboardRegistry $registry)
    {
        abort_if(in_array($request->user()->role, ['doctor', 'patient'], true), 403);
        $data = $request->validate(['module' => ['required', 'string'], 'open_now' => ['sometimes', 'boolean']]);
        $module = $registry->modulesFor($request->user())->firstWhere('key', $data['module']);
        abort_unless($module && $module['url'] !== '#' && !in_array($module['key'], ['doctor', 'patient'], true), 403);
        $request->user()->update(['metadata' => array_merge($request->user()->metadata ?? [], ['home_module' => $module['key']])]);
        return $request->boolean('open_now') ? redirect()->to($module['url']) : back()->with('module_saved', $module['label']);
    }
}
