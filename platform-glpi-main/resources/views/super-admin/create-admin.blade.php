@extends('layouts.dashboard')
@section('title','Créer un Admin')
@section('page-title','Créer un Admin')

@section('content')

{{-- Header --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center">
        <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow">
          <i class="material-symbols-rounded" style="font-size:36px; color:var(--color-primary);">person_add</i>
        </div>
        <div>
          <h5 class="text-white font-weight-bolder mb-0">Créer un nouvel Admin</h5>
          <p class="text-white text-sm mb-0 opacity-8">Remplissez les informations ci-dessous</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">
    <div class="card shadow">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">Informations de l'admin</h6>
      </div>
      <div class="card-body px-4 py-4">

        @if($errors->any())
          <div class="alert alert-danger">
            @foreach($errors->all() as $error)
              <p class="text-sm mb-0">⚠ {{ $error }}</p>
            @endforeach
          </div>
        @endif

        <form method="POST" action="{{ route('super-admin.admins.store') }}">
          @csrf

          {{-- ✅ Nom complet — input simple sans floating label --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Nom complet <span class="text-danger">*</span>
            </label>
            <input type="text" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   placeholder="Prénom Nom"
                   style="height:45px; border:1px solid #d2d6da; border-radius:8px; padding:0 14px; font-size:14px;"
                   required>
            @error('name')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- ✅ Email --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Adresse email <span class="text-danger">*</span>
            </label>
            <input type="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   placeholder="admin@exemple.com"
                   style="height:45px; border:1px solid #d2d6da; border-radius:8px; padding:0 14px; font-size:14px;"
                   required>
            @error('email')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- ✅ Mot de passe avec œil --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Mot de passe <span class="text-danger">*</span>
            </label>
            <div class="position-relative">
              <input type="password" name="password" id="password"
                     class="form-control @error('password') is-invalid @enderror"
                     placeholder="Min. 8 caractères"
                     style="height:45px; border:1px solid #d2d6da; border-radius:8px; padding:0 44px 0 14px; font-size:14px;"
                     required minlength="8">
              <button type="button" onclick="togglePwd('password','eyeIcon1')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#9ca3af;">
                <i class="material-symbols-rounded" id="eyeIcon1" style="font-size:20px;vertical-align:middle;">visibility_off</i>
              </button>
            </div>
            @error('password')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- ✅ Confirmer mot de passe avec œil --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Confirmer le mot de passe <span class="text-danger">*</span>
            </label>
            <div class="position-relative">
              <input type="password" name="password_confirmation" id="password_confirm"
                     class="form-control"
                     placeholder="Répétez le mot de passe"
                     style="height:45px; border:1px solid #d2d6da; border-radius:8px; padding:0 44px 0 14px; font-size:14px;"
                     required>
              <button type="button" onclick="togglePwd('password_confirm','eyeIcon2')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#9ca3af;">
                <i class="material-symbols-rounded" id="eyeIcon2" style="font-size:20px;vertical-align:middle;">visibility_off</i>
              </button>
            </div>

            {{-- ✅ Indicateur correspondance passwords --}}
            <p id="pwdMatch" class="text-xs mt-1" style="display:none;"></p>
          </div>

          {{-- Buttons --}}
          <div class="d-flex gap-3 mt-3">
            <button type="submit" class="btn w-100 mb-0"
                    style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%); color: white;">
              <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">person_add</i>
              Créer l'admin
            </button>
            <a href="{{ route('super-admin.admins') }}" class="btn btn-outline-secondary w-100 mb-0">
              Annuler
            </a>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script>
// ✅ Toggle afficher/masquer mot de passe
function togglePwd(inputId, iconId) {
  var input = document.getElementById(inputId);
  var icon  = document.getElementById(iconId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.textContent = 'visibility';
  } else {
    input.type = 'password';
    icon.textContent = 'visibility_off';
  }
}

// ✅ Vérifier correspondance en temps réel
document.addEventListener('DOMContentLoaded', function () {
  var pwd     = document.getElementById('password');
  var confirm = document.getElementById('password_confirm');
  var msg     = document.getElementById('pwdMatch');

  function checkMatch() {
    if (!confirm.value) { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if (pwd.value === confirm.value) {
      msg.textContent = '✅ Les mots de passe correspondent';
      msg.style.color = '#2e7d32';
      confirm.style.borderColor = '#4caf50';
    } else {
      msg.textContent = '❌ Les mots de passe ne correspondent pas';
      msg.style.color = '#c62828';
      confirm.style.borderColor = '#e53935';
    }
  }

  confirm.addEventListener('input', checkMatch);
  pwd.addEventListener('input', checkMatch);
});
</script>

@endsection