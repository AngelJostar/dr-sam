@php
  $feedbackErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all())->unique()->values();
  $feedbackMessages = collect(['status', 'success', 'error', 'warning', 'info'])
    ->map(fn ($key) => session($key))->filter(fn ($message) => is_string($message) && $message !== '')->unique()->values();
  $feedbackIsError = $feedbackErrors->isNotEmpty() || session()->has('error');
@endphp

@if ($feedbackMessages->isNotEmpty() || $feedbackErrors->isNotEmpty())
  <dialog class="superadmin-feedback-dialog {{ $feedbackIsError ? 'is-error' : '' }}" data-admin-feedback-dialog aria-labelledby="admin-feedback-title" aria-describedby="admin-feedback-content">
    <header>
      <h2 id="admin-feedback-title">{{ $feedbackIsError ? 'Revisa la información' : 'Aviso' }}</h2>
      <button type="button" data-admin-feedback-close aria-label="Cerrar mensaje" autofocus>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
      </button>
    </header>
    <div id="admin-feedback-content">
      @foreach ($feedbackMessages as $message)
        <p>{{ $message }}</p>
      @endforeach
      @if ($feedbackErrors->isNotEmpty())
        <ul>@foreach ($feedbackErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
      @endif
    </div>
  </dialog>
@endif
