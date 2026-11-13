<div class="superadmin-catalog-heading">
<header class="superadmin-native-section-heading">
  <div>
    <h2>{{ $headingTitle }}</h2>
    <p>{{ $headingDescription }}</p>
  </div>
  @if (!empty($headingActions))
    <div class="superadmin-native-heading-actions">
      @foreach ($headingActions as $action)
        <a href="{{ $action['href'] }}">{{ $action['label'] }}</a>
      @endforeach
    </div>
  @endif
</header>
</div>
