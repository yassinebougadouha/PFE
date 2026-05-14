@extends('layouts.dashboard')
@section('title','Paramètres')
@section('page-title','Paramètres')

@section('content')

@php
  $user           = auth()->user();
  $initials       = strtoupper(substr($user->name, 0, 2));
  $themeMode      = $clientPrefs['theme_mode']      ?? 'light';
  $primaryColor   = $clientPrefs['primary_color']   ?? ($appSettings['primary_color']   ?? '#1a56db');
  $secondaryColor = $clientPrefs['secondary_color'] ?? ($appSettings['secondary_color'] ?? '#764ba2');
@endphp

<style>
  /* ══ SETTINGS LAYOUT ══ */
  .settings-container {
    display: flex;
    gap: 24px;
    align-items: flex-start;
    padding-top: 10px;
  }
  
  .settings-nav {
    width: 280px;
    position: sticky;
    top: 100px; /* Account for dashboard navbar */
    flex-shrink: 0;
  }
  
  .settings-content {
    flex-grow: 1;
    min-width: 0; /* Fix flex overflow */
  }

  /* ── Sidebar Nav ── */
  .snav-card {
    background: var(--bg-card);
    border-radius: 20px;
    border: 1px solid var(--border-color);
    padding: 12px;
    box-shadow: var(--card-shadow);
  }
  
  .snav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    margin-bottom: 4px;
  }
  
  .snav-link i {
    font-size: 20px;
    transition: transform 0.2s ease;
  }
  
  .snav-link:hover {
    background: rgba(0, 0, 0, 0.04);
    color: var(--text-heading);
  }
  
  .snav-link.active {
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: #fff !important;
    box-shadow: 0 8px 16px -4px color-mix(in srgb, var(--color-primary) 30%, transparent);
  }
  
  .snav-link.active i {
    transform: scale(1.1);
  }

  [data-bs-theme="dark"] .snav-link:hover {
    background: rgba(255, 255, 255, 0.05);
  }

  /* ── Section Cards ── */
  .settings-section {
    display: none;
    animation: fadeIn 0.3s ease;
  }
  .settings-section.active {
    display: block;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .section-card {
    background: var(--bg-card);
    border-radius: 24px;
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    overflow: hidden;
    margin-bottom: 24px;
  }
  
  .section-header {
    padding: 24px 32px;
    border-bottom: 1px solid var(--border-color);
    background: rgba(0, 0, 0, 0.01);
  }
  
  [data-bs-theme="dark"] .section-header {
    background: rgba(255, 255, 255, 0.01);
  }

  .section-body {
    padding: 32px;
  }

  /* ── Custom UI ── */
  .genre-pill {
    cursor: pointer;
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    padding: 12px;
    text-align: center;
    transition: all 0.2s ease;
    font-weight: 600;
    font-size: 13px;
    color: var(--text-muted);
    background: var(--bg-body);
  }
  .genre-pill:hover {
    border-color: var(--color-primary);
    color: var(--text-heading);
  }
  .genre-radio:checked + .genre-pill {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 10%, transparent);
    color: var(--color-primary);
  }

  .theme-option {
    cursor: pointer;
    position: relative;
  }
  .theme-box {
    border: 2px solid var(--border-color);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s ease;
    background: var(--bg-body);
  }
  .theme-radio:checked + .theme-box {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 8%, transparent);
  }
  .theme-radio:checked + .theme-box i, 
  .theme-radio:checked + .theme-box p {
    color: var(--color-primary) !important;
  }

  .palette-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
    gap: 12px;
  }
  .palette-btn {
    aspect-ratio: 1;
    border-radius: 12px;
    border: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
  }
  .palette-btn:hover {
    transform: scale(1.1);
  }
  .palette-btn.active {
    border-color: var(--text-heading);
    box-shadow: 0 0 0 2px var(--bg-card);
  }

  @media (max-width: 991px) {
    .settings-container { flex-direction: column; }
    .settings-nav { width: 100%; position: static; margin-bottom: 20px; }
    .settings-nav .snav-card { display: flex; overflow-x: auto; gap: 8px; padding: 8px; }
    .snav-link { margin-bottom: 0; white-space: nowrap; }
  }
</style>

@if(session('success') || session('status'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:16px; background: #10b981; color: white;">
  <div class="d-flex align-items-center">
    <i class="material-symbols-rounded me-2">check_circle</i>
    <span>{{ session('success') ?: (session('status') === 'profile-updated' ? 'Profil mis à jour !' : 'Action réussie !') }}</span>
  </div>
  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="settings-container">
  
  {{-- ══ SIDEBAR NAVIGATION ══ --}}
  <div class="settings-nav">
    <div class="snav-card">
      <button class="snav-link active" data-target="section-profile">
        <i class="material-symbols-rounded">person</i>
        <span>Mon Profil</span>
      </button>
      <button class="snav-link" data-target="section-security">
        <i class="material-symbols-rounded">security</i>
        <span>Sécurité</span>
      </button>
      <button class="snav-link" data-target="section-appearance">
        <i class="material-symbols-rounded">palette</i>
        <span>Apparence</span>
      </button>
      <hr class="my-2 opacity-1">
      <button class="snav-link text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
        <i class="material-symbols-rounded">delete</i>
        <span>Supprimer le compte</span>
      </button>
    </div>

    {{-- User Mini Card --}}
    <div class="section-card mt-4 text-center p-4">
      <div id="avatarPreviewWrap" class="mx-auto mb-3"
           style="width:80px;height:80px;border-radius:24px;overflow:hidden;
                  background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
                  display:flex;align-items:center;justify-content:center;
                  font-size:28px;font-weight:700;color:white;box-shadow:var(--card-shadow);">
        @if($user->avatar)
          <img src="{{ asset('storage/'.$user->avatar) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
        @else
          {{ $initials }}
        @endif
      </div>
      <h6 class="mb-1">{{ $user->name }}</h6>
      <p class="text-xs text-muted mb-0">{{ $user->email }}</p>
    </div>
  </div>

  {{-- ══ MAIN CONTENT ══ --}}
  <div class="settings-content">

    {{-- SECTION: PROFILE --}}
    <div id="section-profile" class="settings-section active">
      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PATCH')
        
        <div class="section-card">
          <div class="section-header">
            <h5 class="mb-1 font-weight-bold">Informations Personnelles</h5>
            <p class="text-sm text-muted mb-0">Gérez votre identité et vos coordonnées</p>
          </div>
          <div class="section-body">
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label text-xs font-weight-bold text-uppercase">Prénom</label>
                <input type="text" name="first_name" id="fnInput" class="form-control" 
                       value="{{ old('first_name', $user->first_name) }}" placeholder="Prénom">
              </div>
              <div class="col-md-6">
                <label class="form-label text-xs font-weight-bold text-uppercase">Nom</label>
                <input type="text" name="last_name" id="lnInput" class="form-control" 
                       value="{{ old('last_name', $user->last_name) }}" placeholder="Nom">
              </div>
              <div class="col-md-6">
                <label class="form-label text-xs font-weight-bold text-uppercase">Email</label>
                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label text-xs font-weight-bold text-uppercase">Téléphone</label>
                <input type="tel" name="phone_mobile" class="form-control" value="{{ $user->phone_mobile }}" placeholder="Mobile">
              </div>
              <div class="col-12">
                <label class="form-label text-xs font-weight-bold text-uppercase d-block mb-2">Genre</label>
                <div class="row g-2">
                  @foreach(['male' => '♂ Homme', 'female' => '♀ Femme', 'other' => '⚧ Autre'] as $val => $label)
                  <div class="col-4">
                    <input type="radio" name="gender" value="{{ $val }}" id="g-{{ $val }}" class="d-none genre-radio" {{ $user->gender === $val ? 'checked' : '' }}>
                    <label for="g-{{ $val }}" class="genre-pill w-100 mb-0">{{ $label }}</label>
                  </div>
                  @endforeach
                </div>
              </div>
              <div class="col-12">
                <label class="form-label text-xs font-weight-bold text-uppercase">Photo de profil</label>
                <div class="d-flex align-items-center gap-3 p-3 border-radius-lg bg-body" style="border: 1px dashed var(--border-color);">
                  <div class="avatar-edit-preview" style="width:48px;height:48px;border-radius:12px;background:var(--bg-card);display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid var(--border-color);">
                    @if($user->avatar)
                      <img src="{{ asset('storage/'.$user->avatar) }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                      <i class="material-symbols-rounded text-muted">person</i>
                    @endif
                  </div>
                  <div class="flex-grow-1">
                    <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*">
                    <button type="button" class="btn btn-sm btn-outline-primary mb-0" onclick="document.getElementById('avatarInput').click()">
                      Changer la photo
                    </button>
                    <span class="text-xxs text-muted ms-2">JPG, PNG max 2MB</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-5 pt-3 border-top d-flex justify-content-end">
              <button type="submit" class="btn btn-primary px-5">Enregistrer les modifications</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    {{-- SECTION: SECURITY --}}
    <div id="section-security" class="settings-section">
      <div class="section-card">
        <div class="section-header">
          <h5 class="mb-1 font-weight-bold">Mot de passe</h5>
          <p class="text-sm text-muted mb-0">Sécurisez votre compte avec un mot de passe robuste</p>
        </div>
        <div class="section-body">
          <form method="POST" action="{{ route('password.update') }}">
            @csrf @method('PUT')
            <div class="row g-4">
              <div class="col-12">
                <label class="form-label text-xs font-weight-bold text-uppercase">Mot de passe actuel</label>
                <div class="position-relative">
                  <input type="password" name="current_password" class="form-control pe-5" placeholder="••••••••">
                  <i class="material-symbols-rounded position-absolute end-0 top-50 translate-middle-y me-3 text-muted cursor-pointer toggle-password">visibility</i>
                </div>
                @error('current_password','updatePassword')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label text-xs font-weight-bold text-uppercase">Nouveau mot de passe</label>
                <div class="position-relative">
                  <input type="password" name="password" class="form-control pe-5" placeholder="••••••••">
                  <i class="material-symbols-rounded position-absolute end-0 top-50 translate-middle-y me-3 text-muted cursor-pointer toggle-password">visibility</i>
                </div>
                @error('password','updatePassword')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label text-xs font-weight-bold text-uppercase">Confirmer</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••">
              </div>
            </div>
            <div class="mt-5 pt-3 border-top d-flex justify-content-end">
              <button type="submit" class="btn btn-primary px-5">Mettre à jour le mot de passe</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- SECTION: APPEARANCE --}}
    <div id="section-appearance" class="settings-section">
      <form id="uiSettingsForm" action="{{ route('client.settings.ui') }}" method="POST">
        @csrf
        
        <div class="section-card">
          <div class="section-header">
            <h5 class="mb-1 font-weight-bold">Thème & Couleurs</h5>
            <p class="text-sm text-muted mb-0">Personnalisez votre expérience visuelle</p>
          </div>
          <div class="section-body">
            
            <label class="form-label text-xs font-weight-bold text-uppercase mb-3">Mode d'affichage</label>
            <div class="row g-3 mb-5">
              @foreach(['light' => ['wb_sunny','Clair'], 'dark' => ['dark_mode','Sombre'], 'auto' => ['brightness_auto','Auto']] as $val => $d)
              <div class="col-4">
                <label class="theme-option w-100 mb-0">
                  <input type="radio" name="theme_mode" value="{{ $val }}" class="d-none theme-radio" {{ $themeMode === $val ? 'checked' : '' }}>
                  <div class="theme-box">
                    <i class="material-symbols-rounded d-block mb-2 fs-3 text-muted">{{ $d[0] }}</i>
                    <p class="text-sm font-weight-bold mb-0 text-muted">{{ $d[1] }}</p>
                  </div>
                </label>
              </div>
              @endforeach
            </div>

            <label class="form-label text-xs font-weight-bold text-uppercase mb-3">Couleurs de l'interface</label>
            <div class="row g-4 align-items-center">
              <div class="col-lg-6">
                <p class="text-xs text-muted mb-3">Choisissez une palette prédéfinie ou créez la vôtre</p>
                <div class="palette-grid mb-4">
                  @php $palettes = [['#1a56db','#764ba2'],['#0ea5e9','#06b6d4'],['#10b981','#059669'],['#f59e0b','#ef4444'],['#8b5cf6','#ec4899'],['#1e293b','#475569'],['#dc2626','#b91c1c'],['#0f766e','#115e59']]; @endphp
                  @foreach($palettes as [$p,$s])
                  <button type="button" class="palette-btn" data-primary="{{ $p }}" data-secondary="{{ $s }}"
                          style="background:linear-gradient(135deg,{{ $p }},{{ $s }});">
                  </button>
                  @endforeach
                </div>
                
                <div class="row g-3">
                  <div class="col-6">
                    <label class="text-xxs font-weight-bold text-uppercase text-muted mb-1">Primaire</label>
                    <div class="d-flex align-items-center gap-2">
                      <input type="color" name="primary_color" id="primaryColorPicker" value="{{ $primaryColor }}" class="form-control-color border-0 p-0" style="width:30px;height:30px;">
                      <input type="text" id="primaryColorText" value="{{ $primaryColor }}" class="form-control form-control-sm font-monospace text-xs" style="max-width:85px;">
                    </div>
                  </div>
                  <div class="col-6">
                    <label class="text-xxs font-weight-bold text-uppercase text-muted mb-1">Secondaire</label>
                    <div class="d-flex align-items-center gap-2">
                      <input type="color" name="secondary_color" id="secondaryColorPicker" value="{{ $secondaryColor }}" class="form-control-color border-0 p-0" style="width:30px;height:30px;">
                      <input type="text" id="secondaryColorText" value="{{ $secondaryColor }}" class="form-control form-control-sm font-monospace text-xs" style="max-width:85px;">
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-lg-6">
                <div class="p-4 border-radius-xl shadow-sm text-center" id="uiPreview"
                     style="background:linear-gradient(135deg,{{ $primaryColor }},{{ $secondaryColor }}); min-height:180px; display:flex; flex-direction:column; justify-content:center;">
                   <div class="mx-auto bg-white opacity-2 border-radius-md mb-2" style="width:60%;height:12px;"></div>
                   <div class="mx-auto bg-white opacity-1 border-radius-md" style="width:40%;height:8px;"></div>
                   <div class="mt-4 d-flex justify-content-center gap-2">
                     <div class="bg-white opacity-2 border-radius-sm" style="width:30px;height:30px;"></div>
                     <div class="bg-white opacity-2 border-radius-sm" style="width:30px;height:30px;"></div>
                     <div class="bg-white opacity-2 border-radius-sm" style="width:30px;height:30px;"></div>
                   </div>
                </div>
              </div>
            </div>

            <div class="mt-5 pt-3 border-top d-flex justify-content-between">
              <button type="button" id="resetUiBtn" class="btn btn-link text-muted mb-0">Réinitialiser par défaut</button>
              <button type="submit" class="btn btn-primary px-5">Appliquer l'apparence</button>
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
    <div class="modal-content border-0 shadow-lg" style="border-radius:24px;">
      <div class="modal-body p-5 text-center">
        <div class="icon-box mx-auto mb-4" style="width:80px;height:80px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;">
          <i class="material-symbols-rounded fs-1">warning</i>
        </div>
        <h4 class="font-weight-bold mb-2">Supprimer le compte ?</h4>
        <p class="text-muted mb-4">Cette action est définitive. Toutes vos données seront effacées de nos serveurs.</p>
        
        <form method="POST" action="{{ route('profile.destroy') }}" id="deleteForm" class="text-start">
          @csrf @method('DELETE')
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase">Confirmez avec votre mot de passe</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-danger py-3">Oui, supprimer définitivement</button>
            <button type="button" class="btn btn-link text-muted" data-bs-toggle="modal" data-bs-target="#deleteModal">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('page-scripts')
<script>
  // Tab switching logic
  document.querySelectorAll('.snav-link[data-target]').forEach(btn => {
    btn.addEventListener('click', function() {
      // Nav buttons
      document.querySelectorAll('.snav-link').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      
      // Sections
      const targetId = this.dataset.target;
      document.querySelectorAll('.settings-section').forEach(sec => {
        sec.classList.remove('active');
        if (sec.id === targetId) sec.classList.add('active');
      });
      
      // Update URL hash
      window.location.hash = targetId.replace('section-', '');
    });
  });

  // Handle URL hash on load
  window.addEventListener('load', () => {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
      const targetBtn = document.querySelector(`.snav-link[data-target="section-${hash}"]`);
      if (targetBtn) targetBtn.click();
    }
  });

  // Password toggle
  document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
      const input = this.previousElementSibling;
      if (input.type === 'password') {
        input.type = 'text';
        this.textContent = 'visibility_off';
      } else {
        input.type = 'password';
        this.textContent = 'visibility';
      }
    });
  });

  // UI Personalization Logic
  const primaryPicker = document.getElementById('primaryColorPicker');
  const primaryText = document.getElementById('primaryColorText');
  const secondaryPicker = document.getElementById('secondaryColorPicker');
  const secondaryText = document.getElementById('secondaryColorText');
  const preview = document.getElementById('uiPreview');

  function updatePreview() {
    preview.style.background = `linear-gradient(135deg, ${primaryPicker.value}, ${secondaryPicker.value})`;
    
    // Highlight active palette if matches
    document.querySelectorAll('.palette-btn').forEach(btn => {
      if (btn.dataset.primary === primaryPicker.value && btn.dataset.secondary === secondaryPicker.value) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }

  [primaryPicker, secondaryPicker].forEach(p => p.addEventListener('input', () => {
    const textInput = p.id === 'primaryColorPicker' ? primaryText : secondaryText;
    textInput.value = p.value;
    updatePreview();
  }));

  [primaryText, secondaryText].forEach(t => t.addEventListener('input', function() {
    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
      const picker = this.id === 'primaryColorText' ? primaryPicker : secondaryPicker;
      picker.value = this.value;
      updatePreview();
    }
  }));

  document.querySelectorAll('.palette-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      primaryPicker.value = this.dataset.primary;
      primaryText.value = this.dataset.primary;
      secondaryPicker.value = this.dataset.secondary;
      secondaryText.value = this.dataset.secondary;
      updatePreview();
    });
  });

  document.getElementById('resetUiBtn').addEventListener('click', () => {
    primaryPicker.value = '{{ $appSettings["primary_color"] ?? "#1a56db" }}';
    primaryText.value = primaryPicker.value;
    secondaryPicker.value = '{{ $appSettings["secondary_color"] ?? "#764ba2" }}';
    secondaryText.value = secondaryPicker.value;
    updatePreview();
  });

  // Avatar preview
  document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(ev) {
        document.querySelectorAll('.avatar-edit-preview, #avatarPreviewWrap').forEach(el => {
          el.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        });
      };
      reader.readAsDataURL(file);
    }
  });

  updatePreview();
</script>
@endpush