<div>
  <table class="table table-sm table-hover" style="border-radius: 10px;">
    <thead class=" mr-bold bordered">
    <tr class="">
      @foreach($data['header'] as $h_data)
        @foreach($h_data as $h_key => $name)
          <td>{{$name}}</td>
        @endforeach
      @endforeach
    </tr>
    </thead>
    <tbody class="bordered">
    @foreach($data['body'] as $b_data)
      <tr>
        @foreach($b_data as $b_key => $name)
          <td>
            @if(is_array($name))
              @foreach($name as $j)
                {!! $j !!}
              @endforeach
            @else
              {!! $name !!}
            @endif
          </td>
        @endforeach
      </tr>
    @endforeach
    </tbody>
  </table>
</div>