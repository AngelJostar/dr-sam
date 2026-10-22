@props(['id', 'title', 'context' => '', 'label' => 'Ver', 'accessibleLabel' => null, 'url' => null, 'wide' => false])
@if ($url)
  <a class="operational-view-button" href="{{ $url }}" data-operational-detail-url data-detail-title="{{ $title }}" data-detail-context="{{ $context }}" aria-haspopup="dialog" aria-controls="operational-detail-dialog" aria-label="{{ $accessibleLabel ?? $label.' '.$title }}">{{ $label }}</a>
@else
  <button type="button" class="operational-view-button" data-operational-detail-target="{{ $id }}" aria-haspopup="dialog" aria-controls="operational-detail-dialog" aria-label="{{ $accessibleLabel ?? $label.' '.$title }}">{{ $label }}</button>
  <template id="{{ $id }}" data-detail-title="{{ $title }}" data-detail-context="{{ $context }}" data-detail-wide="{{ $wide ? 'true' : 'false' }}">{{ $slot }}</template>
@endif
@once
  @push('styles')
    <link rel="stylesheet" href="{{ asset('css/operational-detail.css') }}?v={{ filemtime(public_path('css/operational-detail.css')) }}">
  @endpush
  @push('scripts')
    <dialog class="operational-detail-dialog" id="operational-detail-dialog" aria-labelledby="operational-detail-title" aria-describedby="operational-detail-context">
      <header>
        <div><h2 id="operational-detail-title"></h2><p id="operational-detail-context"></p></div>
        <button type="button" class="operational-detail-close" data-operational-detail-close autofocus><span>Cerrar</span> <span aria-hidden="true">×</span></button>
      </header>
      <div class="operational-detail-content" data-operational-detail-content></div>
    </dialog>
    <script src="{{ asset('js/operational-detail.js') }}?v={{ filemtime(public_path('js/operational-detail.js')) }}" defer></script>
  @endpush
@endonce
