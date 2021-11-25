@extends('layouts.app')

@section('content')
  <div class="mr-main-div">
    @include('layouts.mr_nav')
    <div class="container m-t-10">
      <table class="table table-sm table-hover">
        <thead>
        <tr>
          <td>id</td>
          <td>Kind</td>
          <td>log</td>
          <td>Date</td>
        </tr>
        </thead>

        <tbody>
        @foreach($list as $row)
          <tr>
            <td>{{$row->id}}</td>
            <td>{{$row->Kind === 1 ? 'buy':'sell'}}</td>
            <td>{{$row->Message}}</td>
            <td>{{$row->WriteDate}}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection