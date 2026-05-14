<nav class="navbar navbar-expand-lg blur border-radius-lg top-0 z-index-3 shadow position-absolute mt-4 py-2 start-0 end-0 mx-4">
  <div class="container-fluid ps-2 pe-0">
   <a class="navbar-brand font-weight-bolder ms-lg-0 ms-3" href="{{ route('dashboard') }}">
     Material Dashboard 3
   </a>

  <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>


    <button class="navbar-toggler shadow-none ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#navigation">
      <span class="navbar-toggler-icon mt-2">
        <span class="navbar-toggler-bar bar1"></span>
        <span class="navbar-toggler-bar bar2"></span>
        <span class="navbar-toggler-bar bar3"></span>
      </span>
    </button>

    <div class="collapse navbar-collapse" id="navigation">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="{{ url('/') }}">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Sign Up</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Sign In</a></li>
      </ul>
    </div>
  </div>
</nav>
