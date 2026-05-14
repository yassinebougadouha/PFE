<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

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
                <h4>Forgot Password</h4>
                <p class="text-sm mb-0">Enter your email and we will send you a reset link.</p>
              </div>

              <div class="card-body">
                @if (session('status'))
                  <div class="alert alert-success text-white" role="alert">
                    {{ session('status') }}
                  </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                  @csrf

                  <div class="input-group input-group-outline my-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required autofocus>
                  </div>

                  @error('email')
                    <p class="text-danger text-sm">{{ $message }}</p>
                  @enderror

                  <div class="text-center">
                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">
                      Email Password Reset Link
                    </button>
                  </div>

                  <p class="mt-4 text-sm text-center">
                    <a href="{{ route('login') }}" class="text-primary text-gradient font-weight-bold">Back to login</a>
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
