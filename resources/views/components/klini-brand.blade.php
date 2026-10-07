@props(['label' => null, 'inverse' => false])
<span {{ $attributes->class(['klini-brand']) }}>
  <img src="{{ asset('brand/klini/'.($inverse ? 'klini-white.svg' : 'klini.svg')) }}" alt="Klini" width="120" height="56">
  @if ($label)<span class="klini-brand-label">{{ $label }}</span>@endif
</span>
