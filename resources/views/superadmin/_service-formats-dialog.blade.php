@push('styles')
  <link rel="stylesheet" href="{{ asset('css/service-formats.css') }}?v={{ filemtime(public_path('css/service-formats.css')) }}">
@endpush
@pushOnce('scripts', 'service-formats')
  <script src="{{ asset('js/service-formats.js') }}?v={{ filemtime(public_path('js/service-formats.js')) }}" defer></script>
@endPushOnce

@foreach($records as $service)
  <template id="service-formats-{{ $service->id }}">
    @if($service->formats->isEmpty())
      <p class="service-formats-empty">Este servicio aún no tiene formatos asociados.</p>
    @else
      <p class="service-formats-count">{{ $service->formats->count() }} {{ $service->formats->count() === 1 ? 'formato disponible' : 'formatos disponibles' }}</p>
      <ul class="service-formats-list">
        @foreach($service->formats as $format)
          <li>
            <div><h3>{{ $format->name }}</h3><span>{{ $format->purposeLabel() }}</span></div>
            @if($format->description)<p>{{ $format->description }}</p>@endif
            @if($format->catalogSection())
              <a class="service-formats-open" href="{{ route('superadmin.services.formats.show', ['service' => $service, 'format' => $format]) }}" data-open-service-format="{{ $format->key }}" data-format-name="{{ $format->name }}" aria-haspopup="dialog" aria-controls="service-format-preview-dialog" aria-label="Abrir formato: {{ $format->name }}">Abrir formato <span aria-hidden="true">→</span></a>
            @endif
          </li>
        @endforeach
      </ul>
    @endif
  </template>
@endforeach

@foreach(collect($records->items())->flatMap(fn ($service) => $service->formats)->filter(fn ($format) => $format->catalogSection())->unique('key') as $previewFormat)
  <template id="service-format-preview-{{ $previewFormat->key }}">
    @if($previewFormat->key === 'prescription')
      @include('superadmin._prescription-format', ['hideFormatHeading' => true])
    @else
      @include('superadmin._mix_request_format', ['mixFormatPanel' => $previewFormat->mixPanel(), 'hideFormatHeading' => true])
    @endif
  </template>
@endforeach

<dialog id="service-formats-dialog" class="service-formats-dialog" aria-labelledby="service-formats-title" aria-describedby="service-formats-name">
  <header>
    <div><h2 id="service-formats-title">Formatos del servicio</h2><p id="service-formats-name"></p></div>
    <button type="button" data-close-service-formats aria-label="Cerrar formatos">×</button>
  </header>
  <div class="service-formats-content" data-service-formats-content></div>
  <footer><button type="button" data-close-service-formats>Cerrar</button></footer>
</dialog>

<dialog id="service-format-preview-dialog" class="service-format-preview-dialog" aria-labelledby="service-format-preview-title" aria-describedby="service-format-preview-service">
  <header>
    <div><h2 id="service-format-preview-title">Formato del servicio</h2><p id="service-format-preview-service"></p></div>
    <button type="button" data-close-service-format-preview aria-label="Cerrar formato" autofocus>×</button>
  </header>
  <div class="service-format-preview-content" data-service-format-preview-content></div>
  <footer><button type="button" data-close-service-format-preview>Volver a formatos</button></footer>
</dialog>
