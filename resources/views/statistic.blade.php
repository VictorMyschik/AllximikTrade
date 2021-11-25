@extends('layouts.app')

@section('content')
  <div class="mr-main-div">
    @include('layouts.mr_nav')
    <div class="container m-t-8">
      <table class="table table-sm table-hover">
        <thead>
        <tr>
          <td>pair</td>
          <td>middle</td>
          <td>period</td>
          <td>diff</td>
        </tr>
        </thead>

        <tbody>
        @foreach($list as $key => $row)
          <tr>
            <td>{{$key}}</td>
            <td>{{$row['middle']}}</td>
            <td>{{$row['period']}}</td>
            <td>{{$row['diff']}}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection