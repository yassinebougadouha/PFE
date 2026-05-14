<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Reset Password - L2T Support</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/material-dashboard.min.css') }}" rel="stylesheet" />
</head>
<body class="bg-gray-200">
<main class="main-content mt-0">
  <section>
    <div class="page-header min-vh-100" style="background-image: url('{{ asset('assets/img/bg-pricing.jpg') }}');">
      <span class="mask bg-gradient-dark opacity-6"></span>
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-4 col-md-7">
            <div class="card z-index-0 fadeIn3 fadeInBottom">
              <div class="card-header text-center pt-4">
                <h4>Reinitialiser le mot de passe</h4>
                <p class="text-sm mb-0">Choisissez un nouveau mot de passe pour votre compte.</p>
              </div>
              <div class="card-body">
                @if ($errors->any())
                  <div class="alert alert-danger text-white" role="alert">
                    @foreach ($errors->all() as $error)
                      <p class="mb-0 text-sm">{{ $error }}</p>
                    @endforeach
                  </div>
                @endif
                <form method="POST" action="{{ route('password.store') }}">
                  @csrf
                  <input type="hidden" name="token" value="{{ $request->route('token') }}">
                  <div class="input-group input-group-outline my-3">
                    <input type="email" name="email" class="form-control" placeholder="Email"
                           value="{{ old('email', $request->email) }}" required autocomplete="username">
                  </div>
                  @error('email')<p class="text-danger text-sm mt-n2">{{ $message }}</p>@enderror
                  <div class="input-group input-group-outline my-3">
                    <input type="password" name="password" class="form-control"
                           placeholder="Nouveau mot de passe" required autocomplete="new-password">
                  </div>
                  @error('password')<p class="text-danger text-sm mt-n2">{{ $message }}</p>@enderror
                  <div class="input-group input-group-outline my-3">
                    <input type="password" name="password_confirmation" class="form-control"
                           placeholder="Confirmer le mot de passe" required autocomplete="new-password">
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">
                      Reinitialiser mon mot de passe
                    </button>
                  </div>
                  <p class="mt-2 text-sm text-center">
                    <a href="{{ route('login') }}" class="text-primary text-gradient font-weight-bold">Retour a la connexion</a>
                  </p>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<script src="{{ asset('assets/js/material-dashboard.min.js') }}"></script>
</body>
</html>