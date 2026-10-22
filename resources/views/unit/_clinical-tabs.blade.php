<nav class="unit-clinical-catalog-tabs" aria-label="Médicos y Especialidades">
  @foreach (['doctors' => 'Médicos', 'specialties' => 'Especialidades'] as $clinicalSection => $clinicalLabel)
    <a href="{{ route('unit.dashboard', ['unit' => $unit->id, 'section' => $clinicalSection]) }}" @class(['is-active' => $section === $clinicalSection]) @if ($section === $clinicalSection) aria-current="page" @endif>{{ $clinicalLabel }}</a>
  @endforeach
</nav>
