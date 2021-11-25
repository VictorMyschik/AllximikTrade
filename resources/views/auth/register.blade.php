@extends('layouts.app')
@section('content')
  <div class="mr-main-div">
    @include('layouts.mr_nav')
    <div class="container m-t-10">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="">
            <div class="card-body">
              <h5 class="mr-bold"> Регистрация <span class="fa-pull-right"><i
                      class="fa fa-info-circle mr-color-green"></i> Все поля обязательны для заполнения</span></h5>
              <hr>
              <h5 class="mr-bold">{{ $text??null }}</h5>
              <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group row">
                  <label for="office"
                         class="col-md-4 col-form-label text-md-right">Виртуальный офис</label>
                  <div class="col-md-6">
                    <input id="office" type="text" class="form-control @error('office') is-invalid @enderror"
                           name="office" value="{{ old('office') }}" required autocomplete="office" autofocus>
                    @error('office')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                  </div>
                </div>

                <div class="form-group row">
                  <label for="name" class="col-md-4 col-form-label text-md-right">Логин</label>

                  <div class="col-md-6">
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                           value="{{ old('name') }}" required autocomplete="name" autofocus>
                    @error('name')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                  </div>
                </div>

                <div class="form-group row">
                  <label for="email" class="col-md-4 col-form-label text-md-right">Email</label>
                  <div class="col-md-6">
                    <input id="email" type="email"
                           {{ isset($email)?'readonly':null }} class="form-control @error('email') is-invalid @enderror"
                           name="email"
                           value="{{ old('email')??$email??null }}" required autocomplete="email">

                    @error('email')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                  </div>
                </div>

                <div class="form-group row">
                  <label for="password" class="col-md-4 col-form-label text-md-right">Пароль</label>

                  <div class="col-md-6">
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                           name="password" required autocomplete="new-password">

                    @error('password')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                  </div>
                </div>

                <div class="form-group row">
                  <label for="password-confirm"
                         class="col-md-4 col-form-label text-md-right">Повтор пароля</label>
                  <div class="col-md-6">
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation"
                           required autocomplete="new-password">
                  </div>
                </div>

                <div class="form-group row">
                  <div class="col-md-4 col-form-label text-md-right">
                    <input required id="policy" type="checkbox" name="policy">
                  </div>

                  <div class="col-md-6">
                    <label for="policy">Я согласен с <a href="/policy" target="_blank">Политикой
                        приватности</a>,
                      а также даю согласие на обработку персональных данных</label>
                  </div>
                </div>

                <div class="form-group row mb-0">
                  <div class="col-md-6 offset-md-4">
                    <button type="submit" class="btn btn-sm btn-primary">Register</button>
                  </div>
                </div>
                <hr>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
