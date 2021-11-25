<a title="{{ $title }}" href="{{$url}}"
@foreach($attributes as $key=>$item)
  {{$key}}="{{$item}}"
@endforeach
class="{{ $class }}"
>{{ $text }}</a>
