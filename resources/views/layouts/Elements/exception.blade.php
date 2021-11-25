<div>{{ $code }}</div>
<div>
  @foreach($message as $key => $item)
    <li>{{$key}} : {{$item}}</li>
  @endforeach
</div>
