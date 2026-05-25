@extends('layouts.dashboard')
@section('title','Mon Profil')
@section('page-title','Mon Profil')

@section('content')

@php
  $user           = auth()->user();
  $initials       = strtoupper(substr($user->name, 0, 2));
  $uid            = auth()->id();
  $uPrimary       = \App\Models\Setting::get("user_{$uid}_primary_color");
  $uSecondary     = \App\Models\Setting::get("user_{$uid}_secondary_color");
  $uTheme         = \App\Models\Setting::get("user_{$uid}_theme_mode");
  $themeMode      = !empty($uTheme)     ? $uTheme     : ($appSettings['theme_mode']      ?? 'light');
  $primaryColor   = !empty($uPrimary)   ? $uPrimary   : ($appSettings['primary_color']   ?? '#1a56db');
  $secondaryColor = !empty($uSecondary) ? $uSecondary : ($appSettings['secondary_color'] ?? '#764ba2');
@endphp

<style>
  /* ══ PROFILE LAYOUT ══ */
  .profile-container {
    display: flex;
    gap: 24px;
    align-items: flex-start;
    padding-top: 10px;
    max-width: 1400px;
    margin: 0 auto;
  }
  
  .profile-nav {
    width: 300px;
    position: sticky;
    top: 100px;
    flex-shrink: 0;
    z-index: 10;
  }
  
  .profile-content {
    flex-grow: 1;
    min-width: 0;
  }

  /* ── Sidebar Nav ── */
  .pnav-card {
    background: var(--bg-card);
    border-radius: 24px;
    border: 1px solid var(--border-color);
    padding: 12px;
    box-shadow: var(--card-shadow);
  }
  
  .pnav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 14px;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 14px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    margin-bottom: 4px;
    position: relative;
    overflow: hidden;
  }
  
  .pnav-link i { font-size: 20px; transition: transform 0.25s ease; }
  
  .pnav-link:hover {
    background: rgba(var(--color-primary-rgb, 26, 86, 219), 0.05);
    color: var(--text-heading);
  }
  
  .pnav-link.active {
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: #fff !important;
    box-shadow: 0 8px 16px -4px color-mix(in srgb, var(--color-primary) 30%, transparent);
  }
  
  .pnav-link.active i { transform: scale(1.1); }

  [data-bs-theme="dark"] .pnav-link:hover { background: rgba(255,255,255,0.05); }

  /* ── Section Cards ── */
  .profile-section { display: none; animation: slideUpFade 0.4s cubic-bezier(0.16,1,0.3,1); }
  .profile-section.active { display: block; }
  
  @keyframes slideUpFade {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .section-card {
    background: var(--bg-card);
    border-radius: 28px;
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    overflow: hidden;
    margin-bottom: 24px;
  }
  
  .section-header {
    padding: 28px 32px;
    border-bottom: 1px solid var(--border-color);
    background: rgba(0,0,0,0.01);
  }
  [data-bs-theme="dark"] .section-header { background: rgba(255,255,255,0.01); }

  .section-body { padding: 32px; }

  /* ── Form Styling ── */
  .form-label-custom {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 8px;
    display: block;
  }

  .form-control-custom {
    background: var(--bg-body);
    border: 1.5px solid var(--border-color);
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 14px;
    color: var(--text-main);
    transition: all 0.2s ease;
    width: 100%;
  }
  .form-control-custom:focus {
    border-color: var(--color-primary);
    background: var(--bg-card);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 10%, transparent);
    outline: none;
  }

  /* ── Gender Radio ── */
  .gender-option { cursor: pointer; }
  .gender-box {
    border: 2px solid var(--border-color);
    border-radius: 18px;
    padding: 16px 12px;
    text-align: center;
    transition: all 0.2s ease;
    background: var(--bg-body);
  }
  .gender-radio:checked + .gender-box {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 8%, transparent);
  }
  .gender-radio:checked + .gender-box i,
  .gender-radio:checked + .gender-box span {
    color: var(--color-primary) !important;
  }

  /* ── Theme Options ── */
  .theme-option { cursor: pointer; position: relative; }
  .theme-box {
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 24px;
    text-align: center;
    transition: all 0.2s ease;
    background: var(--bg-body);
  }
  .theme-radio:checked + .theme-box {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 8%, transparent);
  }
  .theme-radio:checked + .theme-box i,
  .theme-radio:checked + .theme-box p { color: var(--color-primary) !important; }

  /* ── Palette Grid ── */
  .palette-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(48px, 1fr)); gap: 14px; }
  .palette-btn {
    aspect-ratio: 1;
    border-radius: 14px;
    border: 3px solid transparent;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    position: relative;
  }
  .palette-btn:hover { transform: scale(1.1) translateY(-2px); }
  .palette-btn.active {
    border-color: var(--text-heading);
    box-shadow: 0 0 0 2px var(--bg-card), 0 8px 20px -4px rgba(0,0,0,0.2);
  }

  /* ── Avatar Upload ── */
  .avatar-upload-zone {
    border: 2px dashed var(--border-color);
    border-radius: 20px;
    padding: 24px;
    background: var(--bg-body);
    transition: all 0.2s ease;
    cursor: pointer;
  }
  .avatar-upload-zone:hover {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 4%, transparent);
  }

  /* ── Section divider ── */
  .form-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-primary);
    margin-bottom: 20px;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .form-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: color-mix(in srgb, var(--color-primary) 20%, transparent);
  }

  /* ── Responsive ── */
  @media (max-width: 991px) {
    .profile-container { flex-direction: column; padding: 0 16px; }
    .profile-nav { width: 100%; position: static; margin-bottom: 24px; }
    .profile-nav .pnav-card { display: flex; overflow-x: auto; gap: 8px; padding: 8px; border-radius: 18px; }
    .pnav-link { margin-bottom: 0; white-space: nowrap; padding: 10px 16px; }
    .profile-nav hr { display: none; }
    .user-mini-card { display: none; }
  }
</style>

@if(session('success') || session('status'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4"
     style="border-radius:20px; background: #10b981; color: white; padding: 16px 24px;">
  <div class="d-flex align-items-center">
    <i class="material-symbols-rounded me-3">check_circle</i>
    <span class="font-weight-bold">{{ session('success') ?: (session('status') === 'profile-updated' ? 'Profil mis à jour avec succès !' : 'Action réussie !') }}</span>
  </div>
  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="profile-container">

  {{-- ══ SIDEBAR NAVIGATION ══ --}}
  <div class="profile-nav">
    <div class="pnav-card">
      <button class="pnav-link active" data-target="section-account">
        <i class="material-symbols-rounded">account_circle</i>
        <span>Mon Compte</span>
      </button>
      <button class="pnav-link" data-target="section-security">
        <i class="material-symbols-rounded">lock</i>
        <span>Sécurité</span>
      </button>
      <button class="pnav-link" data-target="section-appearance">
        <i class="material-symbols-rounded">palette</i>
        <span>Apparence</span>
      </button>
      <hr class="my-3 opacity-1 mx-2">
      <button class="pnav-link text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
        <i class="material-symbols-rounded">delete_forever</i>
        <span>Supprimer le compte</span>
      </button>
    </div>

    {{-- User Mini Card (Desktop Only) --}}
    <div class="section-card mt-4 text-center p-4 user-mini-card">
      <div id="avatarPreviewWrap" class="mx-auto mb-3"
           style="width:90px;height:90px;border-radius:28px;overflow:hidden;
                  background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
                  display:flex;align-items:center;justify-content:center;
                  font-size:32px;font-weight:800;color:white;box-shadow:var(--card-shadow);
                  border: 4px solid var(--bg-card);">
        @if($user->avatar && file_exists(storage_path("app/public/" . $user->avatar)))
          <img src="{{ asset('storage/'.$user->avatar) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
        @else
          {{ $initials }}
        @endif
      </div>
      <h5 class="mb-1 font-weight-bold text-heading">{{ $user->name }}</h5>
      <p class="text-xs text-muted mb-3">{{ $user->email }}</p>
      <div class="badge px-3 py-2 text-white text-xxs shadow-sm"
           style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary)); border-radius:10px;">
        <i class="material-symbols-rounded align-middle me-1" style="font-size:14px;">verified_user</i>
        {{ ucfirst(str_replace('_',' ', $user->role)) }}
      </div>
    </div>
  </div>

  {{-- ══ MAIN CONTENT ══ --}}
  <div class="profile-content">

    {{-- ══ SECTION: MON COMPTE (tout en un) ══ --}}
    <div id="section-account" class="profile-section active">
      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PATCH')

        <div class="section-card">
          <div class="section-header d-flex align-items-center justify-content-between">
            <div>
              <h5 class="mb-1 font-weight-bold">Mon Compte</h5>
              <p class="text-sm text-muted mb-0">Informations personnelles et coordonnées</p>
            </div>
            <i class="material-symbols-rounded text-primary fs-2">manage_accounts</i>
          </div>
          <div class="section-body">

            {{-- ── Identité ── --}}
            <p class="form-section-title"><i class="material-symbols-rounded" style="font-size:16px;">badge</i> Identité</p>
            <div class="row g-4 mb-4">
              <div class="col-md-6">
                <label class="form-label-custom">Prénom</label>
                <input type="text" name="first_name" class="form-control form-control-custom"
                       value="{{ old('first_name', $user->first_name) }}" placeholder="Votre prénom">
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">Nom de famille</label>
                <input type="text" name="last_name" class="form-control form-control-custom"
                       value="{{ old('last_name', $user->last_name) }}" placeholder="Votre nom">
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">Date de naissance</label>
                <input type="date" name="birthday" class="form-control form-control-custom"
                       value="{{ old('birthday', $user->birthday ? \Carbon\Carbon::parse($user->birthday)->format('Y-m-d') : '') }}">
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">Genre</label>
                <div class="row g-2">
                  <div class="col-4">
                    <label class="gender-option w-100 mb-0">
                      <input type="radio" name="gender" value="male" class="d-none gender-radio"
                             {{ old('gender', $user->gender) === 'male' ? 'checked' : '' }}>
                      <div class="gender-box">
                        <i class="material-symbols-rounded d-block mb-1 text-muted" style="font-size:22px;">male</i>
                        <span class="text-xs font-weight-bold text-muted">Homme</span>
                      </div>
                    </label>
                  </div>
                  <div class="col-4">
                    <label class="gender-option w-100 mb-0">
                      <input type="radio" name="gender" value="female" class="d-none gender-radio"
                             {{ old('gender', $user->gender) === 'female' ? 'checked' : '' }}>
                      <div class="gender-box">
                        <i class="material-symbols-rounded d-block mb-1 text-muted" style="font-size:22px;">female</i>
                        <span class="text-xs font-weight-bold text-muted">Femme</span>
                      </div>
                    </label>
                  </div>
                  <div class="col-4">
                    <label class="gender-option w-100 mb-0">
                      <input type="radio" name="gender" value="other" class="d-none gender-radio"
                             {{ old('gender', $user->gender) === 'other' ? 'checked' : '' }}>
                      <div class="gender-box">
                        <i class="material-symbols-rounded d-block mb-1 text-muted" style="font-size:22px;">person</i>
                        <span class="text-xs font-weight-bold text-muted">Autre</span>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            {{-- ── Photo de profil ── --}}
            <div class="row g-4 mb-5">
              <div class="col-12">
                <label class="form-label-custom">Photo de profil</label>
                <div class="avatar-upload-zone d-flex align-items-center gap-4"
                     onclick="document.getElementById('avatarInput').click()">
                  <div class="avatar-edit-preview"
                       style="width:64px;height:64px;border-radius:18px;background:var(--bg-card);
                              display:flex;align-items:center;justify-content:center;overflow:hidden;
                              border:2px solid var(--border-color);box-shadow:var(--card-shadow);">
                    @if($user->avatar && file_exists(storage_path("app/public/" . $user->avatar)))
                      <img src="{{ asset('storage/'.$user->avatar) }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                      <i class="material-symbols-rounded text-muted fs-2">add_a_photo</i>
                    @endif
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-1 font-weight-bold text-sm">Changer votre photo</h6>
                    <p class="text-xxs text-muted mb-0">Cliquez pour parcourir (JPG, PNG max 2MB)</p>
                    <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*">
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary mb-0">Parcourir</button>
                </div>
              </div>
            </div>

            {{-- ── Contact ── --}}
            <p class="form-section-title"><i class="material-symbols-rounded" style="font-size:16px;">contact_phone</i> Contact</p>
            <div class="row g-4 mb-4">
              <div class="col-12">
                <label class="form-label-custom">Adresse Email (Login)</label>
                <div class="input-group">
                  <span class="input-group-text bg-body border-end-0"
                        style="border-radius:14px 0 0 14px; border:1.5px solid var(--border-color);">
                    <i class="material-symbols-rounded text-muted">mail</i>
                  </span>
                  <input type="email" name="email" class="form-control form-control-custom"
                         style="border-radius:0 14px 14px 0;"
                         value="{{ old('email', $user->email) }}" required>
                </div>
                @error('email')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">Téléphone mobile</label>
                <div class="input-group">
                  <span class="input-group-text bg-body border-end-0"
                        style="border-radius:14px 0 0 14px; border:1.5px solid var(--border-color);">
                    <i class="material-symbols-rounded text-muted">smartphone</i>
                  </span>
                  <input type="tel" name="phone_mobile" class="form-control form-control-custom"
                         style="border-radius:0 14px 14px 0;"
                         value="{{ old('phone_mobile', $user->phone_mobile) }}" placeholder="216 ...">
                </div>
                @error('phone_mobile')<div class="text-danger text-xs mt-1">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">Téléphone fixe</label>
                <input type="tel" name="phone" class="form-control form-control-custom"
                       value="{{ old('phone', $user->phone) }}" placeholder="216 ...">
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">📱 WhatsApp</label>
                <input type="tel" name="whatsapp" class="form-control form-control-custom"
                       value="{{ old('whatsapp', $user->whatsapp) }}" placeholder="216 ...">
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">💬 Email Microsoft Teams</label>
                <div class="input-group">
                  <span class="input-group-text bg-body border-end-0"
                        style="border-radius:14px 0 0 14px; border:1.5px solid var(--border-color);">
                    <i class="material-symbols-rounded text-muted">chat</i>
                  </span>
                  <input type="email" name="teams_email" id="teamsEmailInput"
                         class="form-control form-control-custom"
                         style="border-radius:0 14px 14px 0;"
                         value="{{ old('teams_email', $user->teams_email) }}"
                         placeholder="votrecompte@company.com">
                </div>
              </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex justify-content-end">
              <button type="submit" class="btn btn-primary px-5 shadow-sm">
                Enregistrer les modifications
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    {{-- ══ SECTION: SECURITY ══ --}}
    <div id="section-security" class="profile-section">
      <div class="section-card">
        <div class="section-header d-flex align-items-center justify-content-between">
          <div>
            <h5 class="mb-1 font-weight-bold">Sécurité</h5>
            <p class="text-sm text-muted mb-0">Protégez votre accès avec un mot de passe fort</p>
          </div>
          <i class="material-symbols-rounded text-warning fs-2">lock</i>
        </div>
        <div class="section-body">
          <form method="POST" action="{{ route('password.update') }}">
            @csrf @method('PUT')
            <div class="row g-4">
              <div class="col-12">
                <label class="form-label-custom">Mot de passe actuel</label>
                <div class="position-relative">
                  <input type="password" name="current_password"
                         class="form-control form-control-custom pe-5" placeholder="••••••••">
                  <i class="material-symbols-rounded position-absolute end-0 top-50 translate-middle-y me-3 text-muted cursor-pointer toggle-password">visibility</i>
                </div>
                @error('current_password','updatePassword')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">Nouveau mot de passe</label>
                <div class="position-relative">
                  <input type="password" name="password"
                         class="form-control form-control-custom pe-5" placeholder="••••••••">
                  <i class="material-symbols-rounded position-absolute end-0 top-50 translate-middle-y me-3 text-muted cursor-pointer toggle-password">visibility</i>
                </div>
                @error('password','updatePassword')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label-custom">Confirmer le nouveau mot de passe</label>
                <input type="password" name="password_confirmation"
                       class="form-control form-control-custom" placeholder="••••••••">
              </div>
            </div>
            <div class="mt-5 pt-3 border-top d-flex justify-content-end">
              <button type="submit" class="btn btn-primary px-5">Mettre à jour la sécurité</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- ══ SECTION: APPEARANCE ══ --}}
    <div id="section-appearance" class="profile-section">
      <form id="uiSettingsForm" action="{{ route('user.settings.ui') }}" method="POST">
        @csrf
        <div class="section-card">
          <div class="section-header d-flex align-items-center justify-content-between">
            <div>
              <h5 class="mb-1 font-weight-bold">Personnalisation UI</h5>
              <p class="text-sm text-muted mb-0">Adaptez l'interface à vos préférences</p>
            </div>
            <i class="material-symbols-rounded text-secondary fs-2">palette</i>
          </div>
          <div class="section-body">

            <label class="form-label-custom mb-3">Mode d'affichage</label>
            <div class="row g-3 mb-5">
              @foreach(['light' => ['wb_sunny','Clair'], 'dark' => ['dark_mode','Sombre'], 'auto' => ['brightness_auto','Auto']] as $val => $d)
              <div class="col-4">
                <label class="theme-option w-100 mb-0">
                  <input type="radio" name="theme_mode" value="{{ $val }}"
                         class="d-none theme-radio" {{ $themeMode === $val ? 'checked' : '' }}>
                  <div class="theme-box shadow-sm">
                    <i class="material-symbols-rounded d-block mb-2 fs-2 text-muted">{{ $d[0] }}</i>
                    <p class="text-sm font-weight-bold mb-0 text-muted">{{ $d[1] }}</p>
                  </div>
                </label>
              </div>
              @endforeach
            </div>

            <label class="form-label-custom mb-3">Couleurs de l'interface</label>
            <div class="row g-5 align-items-center">
              <div class="col-lg-6">
                <p class="text-xs text-muted mb-4">Sélectionnez une palette de couleurs dynamique :</p>
                <div class="palette-grid mb-5">
                  @php $palettes = [['#1a56db','#764ba2'],['#0ea5e9','#06b6d4'],['#10b981','#059669'],['#f59e0b','#ef4444'],['#8b5cf6','#ec4899'],['#1e293b','#475569'],['#dc2626','#b91c1c'],['#0f766e','#115e59']]; @endphp
                  @foreach($palettes as [$p,$s])
                  <button type="button" class="palette-btn" data-primary="{{ $p }}" data-secondary="{{ $s }}"
                          style="background:linear-gradient(135deg,{{ $p }},{{ $s }});"></button>
                  @endforeach
                </div>

                <div class="row g-3">
                  <div class="col-6">
                    <label class="text-xxs font-weight-bold text-uppercase text-muted mb-2">Couleur Primaire</label>
                    <div class="d-flex align-items-center gap-2 p-2 bg-body border-radius-lg border">
                      <input type="color" name="primary_color" id="primaryColorPicker"
                             value="{{ $primaryColor }}" class="form-control-color border-0 p-0"
                             style="width:28px;height:28px;border-radius:6px;cursor:pointer;">
                      <input type="text" id="primaryColorText" value="{{ $primaryColor }}"
                             class="form-control form-control-sm border-0 bg-transparent font-monospace text-xs p-0"
                             style="max-width:70px;">
                    </div>
                  </div>
                  <div class="col-6">
                    <label class="text-xxs font-weight-bold text-uppercase text-muted mb-2">Couleur Secondaire</label>
                    <div class="d-flex align-items-center gap-2 p-2 bg-body border-radius-lg border">
                      <input type="color" name="secondary_color" id="secondaryColorPicker"
                             value="{{ $secondaryColor }}" class="form-control-color border-0 p-0"
                             style="width:28px;height:28px;border-radius:6px;cursor:pointer;">
                      <input type="text" id="secondaryColorText" value="{{ $secondaryColor }}"
                             class="form-control form-control-sm border-0 bg-transparent font-monospace text-xs p-0"
                             style="max-width:70px;">
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="p-5 border-radius-2xl shadow-lg text-center position-relative overflow-hidden"
                     id="uiPreview"
                     style="background:linear-gradient(135deg,{{ $primaryColor }},{{ $secondaryColor }}); min-height:220px; display:flex; flex-direction:column; justify-content:center;">
                  <div class="position-absolute top-0 start-0 w-100 h-100 opacity-1"
                       style="background-image:radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size:24px 24px;"></div>
                  <div class="mx-auto bg-white opacity-3 border-radius-lg mb-3" style="width:65%;height:14px;"></div>
                  <div class="mx-auto bg-white opacity-2 border-radius-md" style="width:45%;height:10px;"></div>
                  <div class="mt-5 d-flex justify-content-center gap-3">
                    <div class="bg-white opacity-3 border-radius-lg shadow-sm" style="width:40px;height:40px;"></div>
                    <div class="bg-white opacity-3 border-radius-lg shadow-sm" style="width:40px;height:40px;"></div>
                    <div class="bg-white opacity-3 border-radius-lg shadow-sm" style="width:40px;height:40px;"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex justify-content-between align-items-center">
              <button type="button" id="resetUiBtn" class="btn btn-link text-muted mb-0 font-weight-bold">
                <i class="material-symbols-rounded align-middle me-1">restore</i> Réinitialiser
              </button>
              <button type="submit" class="btn btn-primary px-5 shadow-md">Appliquer le thème</button>
            </div>
          </div>
        </div>
      </form>
    </div>

  </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-2xl" style="border-radius:32px;">
      <div class="modal-body p-5 text-center">
        <div class="icon-box mx-auto mb-4"
             style="width:90px;height:90px;border-radius:50%;background:rgba(239,68,68,0.1);
                    color:#ef4444;display:flex;align-items:center;justify-content:center;">
          <i class="material-symbols-rounded fs-1">warning</i>
        </div>
        <h3 class="font-weight-bold mb-2 text-heading">Supprimer le compte ?</h3>
        <p class="text-muted mb-5 px-3">Cette action est <strong>définitive</strong>. Toutes vos données seront effacées.</p>

        <form method="POST" action="{{ route('profile.destroy') }}" id="deleteForm" class="text-start">
          @csrf @method('DELETE')
          <div class="mb-4">
            <label class="form-label-custom">Confirmez avec votre mot de passe</label>
            <div class="position-relative">
              <input type="password" name="password" class="form-control form-control-custom pe-5"
                     required placeholder="••••••••">
              <i class="material-symbols-rounded position-absolute end-0 top-50 translate-middle-y me-3 text-muted cursor-pointer toggle-password">visibility</i>
            </div>
          </div>
          <div class="d-grid gap-3">
            <button type="submit" class="btn btn-danger py-3 shadow-sm font-weight-bold"
                    style="border-radius:16px;">Oui, supprimer définitivement</button>
            <button type="button" class="btn btn-link text-muted font-weight-bold"
                    data-bs-dismiss="modal">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('page-scripts')
<script>
  // Tab switching
  document.querySelectorAll('.pnav-link[data-target]').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.pnav-link').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
      const target = document.getElementById(this.dataset.target);
      if (target) {
        target.classList.add('active');
        if (window.innerWidth < 992) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      history.replaceState(null, null, '#' + this.dataset.target.replace('section-', ''));
    });
  });

  window.addEventListener('load', () => {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
      const btn = document.querySelector(`.pnav-link[data-target="section-${hash}"]`);
      if (btn) btn.click();
    }
  });

  // Password toggle
  document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
      const input = this.parentElement.querySelector('input');
      input.type = input.type === 'password' ? 'text' : 'password';
      this.textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
    });
  });

  // UI Personalization
  const primaryPicker    = document.getElementById('primaryColorPicker');
  const primaryText      = document.getElementById('primaryColorText');
  const secondaryPicker  = document.getElementById('secondaryColorPicker');
  const secondaryText    = document.getElementById('secondaryColorText');
  const preview          = document.getElementById('uiPreview');

  function updatePreview() {
    preview.style.background = `linear-gradient(135deg, ${primaryPicker.value}, ${secondaryPicker.value})`;
    document.querySelectorAll('.palette-btn').forEach(btn => {
      btn.classList.toggle('active',
        btn.dataset.primary === primaryPicker.value && btn.dataset.secondary === secondaryPicker.value);
    });
  }

  [primaryPicker, secondaryPicker].forEach(p => p.addEventListener('input', () => {
    (p.id === 'primaryColorPicker' ? primaryText : secondaryText).value = p.value;
    updatePreview();
  }));

  [primaryText, secondaryText].forEach(t => t.addEventListener('input', function() {
    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
      (this.id === 'primaryColorText' ? primaryPicker : secondaryPicker).value = this.value;
      updatePreview();
    }
  }));

  document.querySelectorAll('.palette-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      primaryPicker.value   = primaryText.value   = this.dataset.primary;
      secondaryPicker.value = secondaryText.value = this.dataset.secondary;
      updatePreview();
    });
  });

  document.getElementById('resetUiBtn').addEventListener('click', () => {
    primaryPicker.value   = primaryText.value   = '{{ $appSettings["primary_color"] ?? "#1a56db" }}';
    secondaryPicker.value = secondaryText.value = '{{ $appSettings["secondary_color"] ?? "#764ba2" }}';
    updatePreview();
  });

  // Avatar preview
  document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { alert("L'image est trop volumineuse (max 2MB)"); this.value = ''; return; }
    const reader = new FileReader();
    reader.onload = ev => {
      document.querySelectorAll('.avatar-edit-preview, #avatarPreviewWrap').forEach(el => {
        el.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
      });
    };
    reader.readAsDataURL(file);
  });

  updatePreview();
</script>
@endpush