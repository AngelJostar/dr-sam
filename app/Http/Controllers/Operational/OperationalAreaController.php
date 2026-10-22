<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Hospitalization;
use App\Models\ProviderRequest;
use App\Support\OperationalUnitContext;
use App\Support\UnitAreaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OperationalAreaController extends Controller
{
    public function show(Request $request, string $type): View
    {
        $area = UnitAreaCatalog::definitions()[$type] ?? null;
        abort_unless($area, 404);
        $unit = OperationalUnitContext::resolve($request);
        abort_unless($unit, 403, 'Selecciona una unidad autorizada.');
        $areaStatus = in_array($request->input('area_status'), ['active', 'inactive'], true) ? $request->input('area_status') : 'all';

        $sections = match ($type) {
            'operating' => ['calendar' => 'Calendario de cirugías', 'catalog' => $area['label']],
            'pediatric-icu' => ['catalog' => $area['label'], 'entry' => 'Registro de entrada', 'cribs' => 'Cunas', 'nutrition' => 'Central de mezclas (Nutrición Parenteral)'],
            'uci', 'uti', 'recovery' => ['catalog' => $area['label'], 'entry' => 'Registro de entrada', 'beds' => 'Camas', 'nutrition' => 'Central de mezclas (Nutrición Parenteral)'],
            default => ['catalog' => $area['label']],
        };
        $requestedSection = $request->input('section', 'catalog');
        $areaSection = is_string($requestedSection) && array_key_exists($requestedSection, $sections) ? $requestedSection : 'catalog';
        $sectionUrl = fn (string $section, array $query = []) => route('operational.areas.show', ['type' => $type, 'unit' => $unit->id, 'section' => $section] + $query);
        $carouselItems = collect($sections)->map(fn ($label, $section) => [
            'label' => $label, 'url' => $sectionUrl($section), 'active' => $areaSection === $section,
            'icon' => $section === 'calendar' ? 'calendar' : 'catalog',
        ])->values()->all();

        $calendarMonth = now()->startOfMonth();
        $calendarDays = collect();
        $appointments = collect();
        if ($areaSection === 'calendar') {
            $month = $request->input('month');
            if (is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) && (int) substr($month, 0, 4) >= 1900) {
                $calendarMonth = Carbon::createFromFormat('!Y-m', $month);
            }
            $firstDay = $calendarMonth->copy()->startOfWeek(Carbon::SUNDAY);
            $lastDay = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
            $appointments = Appointment::query()->with(['patient', 'doctor', 'procedureArea'])
                ->where('medical_unit_id', $unit->id)
                ->whereHas('procedureArea', fn ($query) => $query->where('medical_unit_id', $unit->id)->where('type', 'operating'))
                ->whereBetween('starts_at', [$firstDay, $lastDay])->orderBy('starts_at')->get();
            for ($day = $firstDay->copy(); $day->lte($lastDay); $day->addDay()) {
                $calendarDays->push($day->copy());
            }
        }
        $nutritionRequests = $areaSection === 'nutrition'
            ? ProviderRequest::query()->with('patient')->where('medical_unit_id', $unit->id)
                ->whereIn('request_type', ['npt', 'nutrition'])->latest('requested_at')->get()
            : collect();
        $entries = $areaSection === 'entry'
            ? Hospitalization::query()->with(['patient', 'doctor'])
                ->where('metadata->medical_unit_id', $unit->id)
                ->where('metadata->area_type', $type)
                ->whereHas('patient')->latest('admitted_at')->get()
            : collect();

        return view('operational.areas', [
            'unit' => $unit, 'area' => $area, 'type' => $type,
            'areaStatus' => $areaStatus,
            'areaSection' => $areaSection, 'sectionLabel' => $sections[$areaSection],
            'carouselItems' => $carouselItems, 'sectionUrl' => $sectionUrl,
            'calendarMonth' => $calendarMonth, 'calendarDays' => $calendarDays,
            'appointments' => $appointments, 'nutritionRequests' => $nutritionRequests, 'entries' => $entries,
            'areas' => UnitAreaCatalog::records($unit)->where('type', $type)->when($areaStatus !== 'all', fn ($areas) => $areas->filter(fn ($record) => ($record['status'] ?? 'active') === $areaStatus)),
        ]);
    }
}
