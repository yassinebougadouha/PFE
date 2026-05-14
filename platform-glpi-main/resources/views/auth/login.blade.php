@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="page-header align-items-start min-vh-100"
     style="background-image: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;">
  <span class="mask bg-gradient-dark opacity-6"></span>

  <div class="container my-auto">
    <div class="row">
      <div class="col-lg-4 col-md-8 col-12 mx-auto">
        <div class="card z-index-0 fadeIn3 fadeInBottom">

          {{-- Header dark floating --}}
          <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
            <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
              <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Sign in</h4>
            </div>
          </div>

          {{-- Form --}}
          <div class="card-body">

            @if(session('status'))
              <div class="alert alert-success text-sm mt-2">{{ session('status') }}</div>
            @endif

            @error('email')
              <div class="alert alert-danger text-sm mt-2">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('login') }}" class="text-start">
              @csrf

             <div class="input-group input-group-outline mb-3">
  <input type="email"
         name="email"
         class="form-control"
         placeholder="Email"
         value="{{ old('email') }}">
</div>


    <div class="input-group input-group-outline mb-3" style="position: relative;">
    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Password" required style="padding-right: 45px;">
    <span onclick="togglePassword()" 
          style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; z-index:10; color:#aaa;">
        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        </svg>
    </span>
</div>

              <div class="form-check form-switch d-flex align-items-center mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                <label class="form-check-label mb-0 ms-3" for="rememberMe">Remember me</label>
              </div>

              @if (Route::has('password.request'))
                <div class="text-end mb-2">
                  <a class="text-sm text-body" href="{{ route('password.request') }}">
                    Forgot your password?
                  </a>
                </div>
              @endif

              <div class="text-center">
                <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">Sign in</button>
              </div>

              <p class="mt-4 text-sm text-center">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-primary text-gradient font-weight-bold">Sign up</a>
              </p>

            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    } else {
        input.type = 'password';
        icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    }
}
</script>
@endsection