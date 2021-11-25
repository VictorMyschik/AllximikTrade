@extends('layouts.app')
@section('content')
  <div class="mr-main-div">
    @include('layouts.mr_nav')
    <div class="container m-t-10">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card-body">
            <h5>Войти</h5>
            <hr>
            <form method="POST" action="{{ route('login') }}">
              @csrf
              <div class="form-group row">
                <label for="identity" class="col-sm-4 col-form-label text-md-right mr-bold">Логин или эл. почта</label>
                <div class="col-md-6">
                  <input id="identity" class="form-control{{ $errors->has('identity') ? ' is-invalid' : '' }}"
                         name="identity"
                         value="{{ old('identity') }}" required autofocus>
                  @if ($errors->has('name') || $errors->has('email'))
                    <span><strong>{{ $errors->first('name') }}</strong></span>
                    <span><strong>{{ $errors->first('email') }}</strong></span>
                  @endif
                </div>
              </div>
              <div class="form-group row">
                <label for="password"
                       class="col-md-4 col-form-label text-md-right mr-bold">Пароль</label>
                <div class="col-md-6">
                  <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                         name="password" required autocomplete="current-password">
                  @error('password')
                  <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                  @enderror
                </div>
              </div>
              <div class="form-group row">
                <div class="col-md-6 offset-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember"
                           id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Запомнить меня</label>
                  </div>
                </div>
              </div>
              <div class="form-group row mb-0">
                <div class="col-md-8 offset-md-4">
                  <button type="submit" class="btn btn-sm btn-primary">Вход</button>
                  @if (Route::has('password.request'))
                    <a class="btn btn-link" href="{{ route('password.request') }}">Забыли пароль?</a>
                  @endif
                </div>
              </div>
              <hr>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
