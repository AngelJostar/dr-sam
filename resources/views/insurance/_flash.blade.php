@if(session('status'))
  <div class="notice success">{{ session('status') }}</div>
@endif

@if($errors->any())
  <div class="notice danger">
    <strong>Revisa la informacion capturada.</strong>
    <ul>
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
