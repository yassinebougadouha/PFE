@extends('layouts.dashboard')
@section('title','Paramètres Système')
@section('page-title','Paramètres')

@section('content')

<div id="superAdminSettingsPage">
  <style>
    #superAdminSettingsPage .tab-content,
    #superAdminSettingsPage .tab-pane {
      margin-top: 0 !important;
      padding-top: 0 !important;
      min-height: 0 !important;
    }
    #superAdminSettingsPage .tab-pane { scroll-margin-top: 90px; }

    /* ── Sticky header wrapper ── */
    #settingsStickyHeader {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--color-background-primary, #fff);
      padding-bottom: 0;
      margin-bottom: 0;
    }
    [data-bs-theme="dark"] #settingsStickyHeader {
      background: var(--color-background-primary, #0f172a);
    }
    #settingsStickyHeader.scrolled {
      box-shadow: 0 4px 24px rgba(0,0,0,0.10);
      backdrop-filter: blur(12px);
    }
  </style>

<div id="settingsStickyHeader">

{{-- Header --}}
<div class="row mb-2">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center">
        <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow">
          <i class="material-symbols-rounded" style="font-size:36px; color:var(--color-primary);">settings</i>
        </div>
        <div>
          <h5 class="text-white font-weight-bolder mb-0">Configuration Système</h5>
          <p class="text-white text-sm mb-0 opacity-8">Gérer les paramètres de la plateforme</p>
        </div>
      </div>
    </div>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
  <i class="material-symbols-rounded me-2">check_circle</i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3">
  @foreach($errors->all() as $error)
    <p class="mb-0 text-sm">⚠ {{ $error }}</p>
  @endforeach
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tabs --}}
<div class="row mb-2">
  <div class="col-12">
    @php
      $activeTab = request('tab', 'general');
      $validTabs = ['general','tickets','security','notifications','system','glpi','sms','voice_agents'];
      if(!in_array($activeTab,$validTabs)) $activeTab='general';
    @endphp
    <ul class="nav nav-fill mb-3 flex-wrap" id="settingsTabs" role="tablist"
        style="background:var(--color-background-secondary);border-radius:16px;padding:6px;gap:6px;border:1px solid var(--color-border-tertiary);display:flex;width:100%;">
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" data-bs-toggle="pill" href="#general">
          <i class="material-symbols-rounded">info</i>Général
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'tickets' ? 'active' : '' }}" data-bs-toggle="pill" href="#tickets">
          <i class="material-symbols-rounded">confirmation_number</i>Tickets
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'security' ? 'active' : '' }}" data-bs-toggle="pill" href="#security">
          <i class="material-symbols-rounded">security</i>Sécurité
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'notifications' ? 'active' : '' }}" data-bs-toggle="pill" href="#notifications">
          <i class="material-symbols-rounded">notifications</i>Notifications
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'system' ? 'active' : '' }}" data-bs-toggle="pill" href="#system">
          <i class="material-symbols-rounded">computer</i>Système
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'glpi' ? 'active' : '' }}" data-bs-toggle="pill" href="#glpi">
          <i class="material-symbols-rounded">api</i>GLPI
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'sms' ? 'active' : '' }}" data-bs-toggle="pill" href="#sms">
          <i class="material-symbols-rounded">sms</i>SMS
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'voice_agents' ? 'active' : '' }}" data-bs-toggle="pill" href="#voice_agents">
          <i class="material-symbols-rounded">settings_voice</i>Voice Agents
        </a>
      </li>
    </ul>
  </div>
</div>

<div style="width:100%;height:2px;background:linear-gradient(90deg,var(--color-primary),var(--color-secondary));border-radius:2px;margin-bottom:0;"></div>

</div>{{-- /#settingsStickyHeader --}}

<div style="height:24px;"></div>

{{-- ═══════════════════════════════════════════════════════════
     TAB CONTENT
═══════════════════════════════════════════════════════════ --}}
<div class="tab-content">

  {{-- ===== 1. GÉNÉRAL ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="general">
    <div class="row">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="font-weight-bold mb-0">Informations générales</h6>
          </div>
          <div class="card-body px-4">
            <form action="{{ route('super-admin.settings') }}" method="POST">
              @csrf
              @method('PUT')
              <input type="hidden" name="_section" value="general">
              <div class="mb-3">
                <label class="form-label text-sm font-weight-bold">Nom de l'application</label>
                <input type="text" name="app_name" class="form-control border px-3"
                       value="{{ old('app_name', $settings['app_name'] ?? 'L2T') }}" required>
              </div>
              <div class="mb-3">
                <label class="form-label text-sm font-weight-bold">Email de contact / Support</label>
                <input type="email" name="support_email" class="form-control border px-3"
                       value="{{ old('support_email', $settings['support_email'] ?? 'support@l2t.com') }}" required>
              </div>
              <div class="mb-3">
                <label class="form-label text-sm font-weight-bold">Description</label>
                <textarea name="description" class="form-control border px-3" rows="3">{{ old('description', $settings['description'] ?? '') }}</textarea>
              </div>
              <input type="hidden" name="locale" value="fr">
              <input type="hidden" name="timezone" value="Africa/Tunis">
              <button type="submit" class="btn w-100 mb-0"
                      style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%); color:white;">
                <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">save</i>
                Enregistrer
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="font-weight-bold mb-0">Logo de l'application</h6>
          </div>
          <div class="card-body px-4 text-center">
            <div class="mb-3">
              <img src="{{ $settings['logo_path'] ?? asset('assets/img/logo-l2t.png') }}?v={{ time() }}"
                   alt="Logo actuel" id="currentLogo"
                   style="max-width:180px; height:auto;"
                   class="img-fluid border-radius-lg shadow"
                   onerror="this.style.display='none'; document.getElementById('noLogo').style.display='block'">
              <div id="noLogo" style="display:none;">
                <i class="material-symbols-rounded text-secondary" style="font-size:64px;">image</i>
              </div>
              <p class="text-xs text-muted mt-2 mb-0">Logo actuel</p>
            </div>
            <form action="{{ route('super-admin.settings') }}" method="POST" enctype="multipart/form-data">
              @csrf
              @method('PUT')
              <input type="hidden" name="_section" value="logo">
              <div class="mb-3">
                <label for="logoFile" class="btn btn-outline-primary mb-0">
                  <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">upload</i>
                  Choisir un logo
                </label>
                <input type="file" id="logoFile" name="logo"
                       accept="image/png,image/jpeg,image/jpg"
                       class="d-none" onchange="previewLogo(event)">
                <p class="text-xs text-muted mt-2 mb-0">PNG, JPG ou JPEG • Max 2MB</p>
              </div>
              <div id="logoPreview" class="mb-3" style="display:none;">
                <p class="text-sm font-weight-bold mb-2">Aperçu :</p>
                <img id="previewImage" src="" alt="Preview"
                     style="max-width:180px; height:auto;"
                     class="img-fluid border-radius-lg shadow mb-2">
              </div>
              <button type="submit" class="btn bg-gradient-success w-100 mb-0" id="uploadBtn" disabled>
                <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">check_circle</i>
                Télécharger le logo
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- ===== FIN GÉNÉRAL ===== --}}


  {{-- ===== 2. TICKETS ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'tickets' ? 'show active' : '' }}" id="tickets">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="font-weight-bold mb-0">Règles & Comportement</h6>
          </div>
          <div class="card-body px-4">
            <form action="{{ route('super-admin.settings') }}" method="POST">
              @csrf
              @method('PUT')
              <input type="hidden" name="_section" value="tickets">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="autoAssign" name="auto_assignment"
                           {{ ($settings['auto_assignment'] ?? '0') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label text-sm" for="autoAssign">Auto-assignation des tickets</label>
                  </div>
                  <label class="form-label text-xs">Méthode d'assignation</label>
                  <select name="auto_assignment_method" class="form-select form-select-sm mb-3">
                    @foreach(['Round-robin','Par catégorie','Par charge'] as $m)
                    <option {{ ($settings['auto_assignment_method'] ?? 'Round-robin') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                  </select>
                  <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="clientClose" name="allow_client_close"
                           {{ ($settings['allow_client_close'] ?? '0') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label text-sm" for="clientClose">Client peut fermer son ticket</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <h6 class="text-sm font-weight-bold mb-2">SLA par priorité</h6>
                  @foreach(['Très haute'=>'4h','Haute'=>'8h','Moyenne'=>'24h','Basse'=>'48h'] as $p => $d)
                  <div class="d-flex align-items-center mb-2 gap-2">
                    <span class="text-xs" style="min-width:80px;">{{ $p }}</span>
                    <input type="text" name="sla_{{ strtolower($p) }}" class="form-control form-control-sm"
                           value="{{ $settings['sla_'.strtolower($p)] ?? $d }}" style="max-width:80px;">
                    <span class="text-xs text-muted">délai max</span>
                  </div>
                  @endforeach
                </div>
              </div>
              <button type="submit" class="btn btn-primary mt-2">
                <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">save</i>
                Enregistrer
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- ===== FIN TICKETS ===== --}}


  {{-- ===== 3. SÉCURITÉ ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'security' ? 'show active' : '' }}" id="security">
    <div class="card">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">Sécurité & Accès</h6>
      </div>
      <div class="card-body px-4">
        <form action="{{ route('super-admin.settings') }}" method="POST">
          @csrf
          @method('PUT')
          <input type="hidden" name="_section" value="security">
          <h6 class="text-sm font-weight-bold mb-3">🔒 Politique des mots de passe</h6>
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label text-xs">Longueur minimum</label>
              <input type="number" name="min_password_length" class="form-control"
                     value="{{ $settings['min_password_length'] ?? 8 }}" min="6" max="32">
            </div>
            <div class="col-md-4">
              <label class="form-label text-xs">Session timeout (minutes)</label>
              <input type="number" name="session_timeout" class="form-control"
                     value="{{ $settings['session_timeout'] ?? 120 }}">
            </div>
            <div class="col-md-4">
              <label class="form-label text-xs">Max tentatives login</label>
              <input type="number" name="max_login_attempts" class="form-control"
                     value="{{ $settings['max_login_attempts'] ?? 5 }}">
            </div>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="passComplex" name="password_complexity"
                   {{ ($settings['password_complexity'] ?? '0') === '1' ? 'checked' : '' }}>
            <label class="form-check-label text-sm" for="passComplex">Exiger majuscule, chiffre et caractère spécial</label>
          </div>
          <hr class="my-3">
          <h6 class="text-sm font-weight-bold mb-3">👤 Inscription & Accès</h6>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="allowReg" name="allow_registration"
                   {{ ($settings['allow_registration'] ?? '1') === '1' ? 'checked' : '' }}>
            <label class="form-check-label text-sm" for="allowReg">Autoriser l'inscription des clients</label>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="emailVerif" name="require_email_verification"
                   {{ ($settings['require_email_verification'] ?? '0') === '1' ? 'checked' : '' }}>
            <label class="form-check-label text-sm" for="emailVerif">Vérification email obligatoire</label>
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="twoFactor" name="two_factor_auth"
                   {{ ($settings['two_factor_auth'] ?? '0') === '1' ? 'checked' : '' }}>
            <label class="form-check-label text-sm" for="twoFactor">Authentification 2FA (optionnel)</label>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">save</i>
            Enregistrer
          </button>
        </form>
      </div>
    </div>
  </div>
  {{-- ===== FIN SÉCURITÉ ===== --}}


  {{-- ===== 4. NOTIFICATIONS ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'notifications' ? 'show active' : '' }}" id="notifications">

    <style>
      [data-bs-theme="dark"] #notifications .card { background: #1e293b !important; border-color: #334155 !important; }
      [data-bs-theme="dark"] #notifications .card-header { background: transparent !important; border-bottom-color: #334155 !important; }
      [data-bs-theme="dark"] #notifications .text-muted { color: #64748b !important; }
      [data-bs-theme="dark"] #notifications .alert[style*="background:#f0f4ff"] {
        background: rgba(99,102,241,0.10) !important; border-left-color: rgba(99,102,241,0.75) !important;
      }
      [data-bs-theme="dark"] #notifications .alert[style*="background:#f0f4ff"] p,
      [data-bs-theme="dark"] #notifications .alert[style*="background:#f0f4ff"] i { color: #c7d2fe !important; }
      [data-bs-theme="dark"] #notifications [style*="background:#f0f0ff"] {
        background: rgba(98,100,167,0.14) !important; border-color: rgba(98,100,167,0.55) !important;
      }
      [data-bs-theme="dark"] #notifications [style*="background:#f0fff4"] {
        background: rgba(34,197,94,0.10) !important; border-color: rgba(34,197,94,0.55) !important;
      }
      [data-bs-theme="dark"] #notifications [style*="background:#f0f0ff"] .text-muted,
      [data-bs-theme="dark"] #notifications [style*="background:#f0fff4"] .text-muted { color: #94a3b8 !important; }
      [data-bs-theme="dark"] #notifications label.border {
        border-color: #334155 !important; background: rgba(255,255,255,0.02) !important;
      }
      [data-bs-theme="dark"] #notifications label.border:hover { background: rgba(255,255,255,0.04) !important; }
      [data-bs-theme="dark"] #notifications label.border .text-secondary { color: #94a3b8 !important; }
      [data-bs-theme="dark"] #notifications label.border .font-weight-bold { color: #e2e8f0 !important; }
    </style>

    <div class="card">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">Configuration Email & Notifications</h6>
      </div>
      <div class="card-body px-4">
        <form action="{{ route('super-admin.settings') }}" method="POST">
          @csrf
          @method('PUT')
          <input type="hidden" name="_section" value="notifications">

          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Mode d'envoi des emails</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="mail_mode" id="mode_gmail" value="gmail"
                       {{ ($settings['mail_mode'] ?? 'gmail') === 'gmail' ? 'checked' : '' }}
                       onchange="document.getElementById('gmail_section').style.display='block'; document.getElementById('smtp_section').style.display='none';">
                <label class="form-check-label text-sm" for="mode_gmail">
                  <img src="https://www.google.com/favicon.ico" style="width:14px;vertical-align:middle;" class="me-1"> Gmail OAuth
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="mail_mode" id="mode_smtp" value="smtp"
                       {{ ($settings['mail_mode'] ?? '') === 'smtp' ? 'checked' : '' }}
                       onchange="document.getElementById('smtp_section').style.display='block'; document.getElementById('gmail_section').style.display='none';">
                <label class="form-check-label text-sm" for="mode_smtp">
                  📨 SMTP classique
                </label>
              </div>
            </div>
          </div>

          <div id="gmail_section" style="display:{{ ($settings['mail_mode'] ?? 'gmail') === 'gmail' ? 'block' : 'none' }};">
            <h6 class="text-sm font-weight-bold mb-3">📧 Configuration Gmail (Google OAuth)</h6>
            <div class="alert mb-3" style="background:#f0f4ff;border-left:4px solid #667eea;border-radius:8px;">
              <p class="text-xs mb-0" style="color:#4c51bf;">
                <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">info</i>
                Ces credentials se trouvent dans <strong>Google Cloud Console → APIs → Gmail API → OAuth 2.0</strong>
              </p>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Email expéditeur</label>
                <input type="email" name="gmail_from_email" class="form-control"
                       value="{{ $settings['gmail_from_email'] ?? '' }}" placeholder="votre@gmail.com"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Nom expéditeur</label>
                <input type="text" name="smtp_from_name" class="form-control"
                       value="{{ $settings['smtp_from_name'] ?? 'L2T Support' }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Client ID</label>
                <input type="text" name="gmail_client_id" class="form-control"
                       value="{{ $settings['gmail_client_id'] ?? '' }}" placeholder="xxxx.apps.googleusercontent.com"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Client Secret</label>
                <input type="password" name="gmail_client_secret" class="form-control"
                       placeholder="{{ !empty($settings['gmail_client_secret']) ? '••••••••' : 'Client Secret' }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
                <p class="text-xs text-muted mt-1">Laisser vide = inchangé</p>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Refresh Token</label>
                <input type="password" name="gmail_refresh_token" class="form-control"
                       placeholder="{{ !empty($settings['gmail_refresh_token']) ? '••••••••' : 'Refresh Token' }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
                <p class="text-xs text-muted mt-1">Laisser vide = inchangé</p>
              </div>
            </div>
          </div>

          <div id="smtp_section" style="display:{{ ($settings['mail_mode'] ?? 'gmail') === 'smtp' ? 'block' : 'none' }};">
            <h6 class="text-sm font-weight-bold mb-3">📨 Configuration SMTP</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Email expéditeur</label>
                <input type="email" name="smtp_from_email_field" class="form-control"
                       value="{{ $settings['smtp_from_email'] ?? '' }}" placeholder="votre@email.com"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Nom expéditeur</label>
                <input type="text" name="smtp_from_name" class="form-control"
                       value="{{ $settings['smtp_from_name'] ?? 'L2T Support' }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-5 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">SMTP Host</label>
                <input type="text" name="smtp_host" class="form-control"
                       value="{{ $settings['smtp_host'] ?? 'smtp.gmail.com' }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Port</label>
                <input type="number" name="smtp_port" class="form-control"
                       value="{{ $settings['smtp_port'] ?? 587 }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Encryption</label>
                <select name="smtp_encryption" class="form-select" style="height:45px;border:1px solid #d2d6da;border-radius:8px;">
                  <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                  <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                  <option value="" {{ ($settings['smtp_encryption'] ?? '') === '' ? 'selected' : '' }}>Aucun</option>
                </select>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Username SMTP</label>
                <input type="text" name="smtp_username" class="form-control"
                       value="{{ $settings['smtp_username'] ?? '' }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Password SMTP <small class="text-muted">(laisser vide = inchangé)</small></label>
                <input type="password" name="smtp_password" class="form-control" placeholder="••••••••"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
            </div>
          </div>

          <hr class="my-3">
          <h6 class="text-sm font-weight-bold mb-3">🔔 Règles de notification</h6>
          <div class="row">
            <div class="col-md-6">
              @foreach([
                ['notify_new_ticket','Nouveau ticket créé'],
                ['notify_status_change','Changement de statut'],
                ['notify_new_comment','Nouveau commentaire'],
              ] as [$key, $label])
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="{{ $key }}" id="{{ $key }}"
                       {{ ($settings[$key] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label text-sm" for="{{ $key }}">{{ $label }}</label>
              </div>
              @endforeach
            </div>
            <div class="col-md-6">
              @foreach([
                ['notify_assigned','Ticket assigné'],
                ['notify_overdue','Ticket en retard (SLA)'],
                ['notify_resolved','Ticket résolu'],
              ] as [$key, $label])
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="{{ $key }}" id="{{ $key }}"
                       {{ ($settings[$key] ?? '1') === '1' ? 'checked' : '' }}>
                <label class="form-check-label text-sm" for="{{ $key }}">{{ $label }}</label>
              </div>
              @endforeach
            </div>
          </div>

          <button type="submit" class="btn btn-primary mt-3">
            <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">save</i>
            Enregistrer
          </button>
        </form>
      </div>
    </div>

    {{-- TEAMS ROUTING SECTION --}}
    <div class="card mt-4">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div style="width:36px;height:36px;border-radius:10px;background:#6264A7;display:flex;align-items:center;justify-content:center;">
            <i class="material-symbols-rounded" style="font-size:20px;color:white;">groups</i>
          </div>
          <div>
            <h6 class="font-weight-bold mb-0">Notifications Teams — Routing avancé</h6>
            <p class="text-xs text-secondary mb-0">Définissez comment et où chaque ticket est envoyé dans Teams</p>
          </div>
        </div>
      </div>
      <div class="card-body px-4">
        <form action="{{ route('super-admin.settings') }}" method="POST">
          @csrf
          @method('PUT')
          <input type="hidden" name="_section" value="teams_routing">

          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Méthode de routing Teams</label>
            <div class="row g-2 mt-1">
              <div class="col-md-6">
                <label class="d-block p-3 border border-radius-lg" style="cursor:pointer;transition:all .15s;" id="lbl_general" onclick="selectTeamsMethod('general')">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <input class="form-check-input mt-0" type="radio" name="teams_routing_method" value="general" id="method_general" {{ ($settings['teams_routing_method'] ?? 'general') === 'general' ? 'checked' : '' }}>
                    <span class="badge" style="background:#6264A7;color:white;font-size:10px;">Par défaut</span>
                  </div>
                  <div class="text-sm font-weight-bold">Channel général</div>
                  <div class="text-xs text-secondary mt-1">Tous les tickets vers un seul channel Teams global avec @tag de l'admin</div>
                </label>
              </div>
              <div class="col-md-6">
                <label class="d-block p-3 border border-radius-lg" style="cursor:pointer;transition:all .15s;" id="lbl_category" onclick="selectTeamsMethod('category')">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <input class="form-check-input mt-0" type="radio" name="teams_routing_method" value="category" id="method_category" {{ ($settings['teams_routing_method'] ?? '') === 'category' ? 'checked' : '' }}>
                    <span class="badge bg-gradient-success" style="font-size:10px;">Intelligent</span>
                  </div>
                  <div class="text-sm font-weight-bold">Par catégorie</div>
                  <div class="text-xs text-secondary mt-1">Ticket routé vers l'admin spécialiste selon la catégorie avec @tag</div>
                </label>
              </div>
            </div>
          </div>

          <div class="p-3 border-radius-lg mb-3" style="background:#f0f0ff;border:1px solid #6264A7;">
            <label class="form-label text-xs font-weight-bold text-uppercase" style="color:#6264A7;">
              Webhook Power Automate — Channel général
            </label>
            <input type="text" name="teams_webhook_url" class="form-control"
                   value="{{ $settings['teams_webhook_url'] ?? config('services.teams.webhook_url', '') }}"
                   placeholder="https://prod-xx.westeurope.logic.azure.com:443/workflows/...">
            <p class="text-xs text-muted mt-1 mb-0">URL Power Automate qui reçoit tous les tickets — l'admin responsable sera @tagué automatiquement</p>
          </div>

          <div id="section_category" style="{{ ($settings['teams_routing_method'] ?? 'general') === 'category' ? '' : 'display:none;' }}">
            <div class="p-3 border-radius-lg mb-3" style="background:#f0fff4;border:1px solid #22c55e;">
              <label class="form-label text-xs font-weight-bold text-uppercase text-secondary mb-3">Admin responsable par catégorie</label>
              <p class="text-xs text-secondary mb-3">Le ticket arrivera dans le channel général avec @tag de l'admin responsable de la catégorie.</p>
              @php
                $teamsAdmins2 = App\Models\User::where('role','admin')->where('is_active',true)->get();
                $teamsMappings = [];
                try { $teamsMappings = Illuminate\Support\Facades\DB::table('category_admin_mappings')->get()->keyBy('category')->toArray(); } catch(Exception $e) {}
                $teamsCategories = [
                    'incident_technique' => '🔴 Incident technique',
                    'integration_api'    => '🔵 Intégration API SMS',
                    'facturation'        => '🟡 Facturation & Commande',
                    'plateforme'         => '🟢 Plateforme L2T',
                    'paiement_mobile'    => '🟠 Paiement Mobile',
                    'autre'              => '⚪ Autre demande',
                ];
              @endphp
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th class="text-xs text-secondary">Catégorie</th>
                      <th class="text-xs text-secondary">Admin responsable (@tag automatique)</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($teamsCategories as $catKey => $catLabel)
                    @php $tmap = $teamsMappings[$catKey] ?? null; @endphp
                    <tr>
                      <td><span class="badge bg-gradient-secondary text-white px-2 py-1">{{ $catLabel }}</span></td>
                      <td>
                        <select name="category_admin[{{ $catKey }}]" class="form-select form-select-sm" style="min-width:180px;">
                          <option value="">— Non assigné —</option>
                          @foreach($teamsAdmins2 as $adm)
                          <option value="{{ $adm->id }}" {{ ($tmap && isset($tmap->admin_id) && $tmap->admin_id == $adm->id) ? 'selected' : '' }}>
                            {{ $adm->name }} {{ $adm->teams_email ? '('.$adm->teams_email.')' : '' }}
                          </option>
                          @endforeach
                        </select>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <div class="alert py-2 px-3 mt-3" style="background:#e8f5e9;border-left:3px solid #22c55e;border-radius:6px;">
                <p class="text-xs mb-0" style="color:#15803d;">📌 Le ticket sera envoyé au channel général avec @tag de l'admin responsable.</p>
              </div>
            </div>
          </div>

          <button type="submit" class="btn text-white mt-2" style="background:linear-gradient(135deg,#6264A7,#464775);">
            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i>
            Enregistrer routing Teams
          </button>
        </form>
      </div>
    </div>

  </div>
  {{-- ===== FIN NOTIFICATIONS ===== --}}


  {{-- ===== 5. SYSTÈME ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'system' ? 'show active' : '' }}" id="system">
    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="font-weight-bold mb-0">Informations système</h6>
          </div>
          <div class="card-body px-4">
            <table class="table table-sm">
              <tr>
                <td class="text-sm font-weight-bold">Laravel Version</td>
                <td><span class="badge bg-gradient-info">{{ app()->version() }}</span></td>
              </tr>
              <tr>
                <td class="text-sm font-weight-bold">PHP Version</td>
                <td><span class="badge bg-gradient-info">{{ phpversion() }}</span></td>
              </tr>
              <tr>
                <td class="text-sm font-weight-bold">Base de données</td>
                <td><span class="badge bg-gradient-success">PostgreSQL</span></td>
              </tr>
              <tr>
                <td class="text-sm font-weight-bold">Environnement</td>
                <td>
                  <span class="badge bg-gradient-{{ config('app.env') === 'production' ? 'success' : 'warning' }}">
                    {{ strtoupper(config('app.env')) }}
                  </span>
                </td>
              </tr>
              <tr>
                <td class="text-sm font-weight-bold">Debug Mode</td>
                <td>
                  <span class="badge bg-gradient-{{ config('app.debug') ? 'danger' : 'success' }}">
                    {{ config('app.debug') ? 'ON' : 'OFF' }}
                  </span>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="font-weight-bold mb-0">Actions système</h6>
          </div>
          <div class="card-body px-4">
            <form action="{{ route('super-admin.settings') }}" method="POST" class="mb-2">
              @csrf
              @method('PUT')
              <input type="hidden" name="_section" value="cache">
              <button type="submit" class="btn btn-outline-warning w-100 mb-0"
                      onclick="return confirm('Vider le cache ?')">
                <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">refresh</i>
                Vider le cache
              </button>
            </form>
            <form action="{{ route('super-admin.settings') }}" method="POST" class="mb-2">
              @csrf
              @method('PUT')
              <input type="hidden" name="_section" value="optimize">
              <button type="submit" class="btn btn-outline-success w-100 mb-0"
                      onclick="return confirm('Optimiser l\'application ?')">
                <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">bolt</i>
                Optimiser l'application
              </button>
            </form>
            <form action="{{ route('super-admin.settings') }}" method="POST">
              @csrf
              @method('PUT')
              <input type="hidden" name="_section" value="maintenance">
              <button type="submit" class="btn btn-outline-danger w-100 mb-0"
                      onclick="return confirm('Changer le mode maintenance ?')">
                <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">construction</i>
                {{ app()->isDownForMaintenance() ? 'Désactiver maintenance' : 'Activer maintenance' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- ===== FIN SYSTÈME ===== --}}


  {{-- ===== 6. GLPI ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'glpi' ? 'show active' : '' }}" id="glpi">

    <style>
      [data-bs-theme="dark"] #glpi .card { background: #1e293b !important; border-color: #334155 !important; }
      [data-bs-theme="dark"] #glpi .card-header { background: transparent !important; border-bottom-color: #334155 !important; }
      [data-bs-theme="dark"] #glpi .btn-outline-secondary { border-color: #334155 !important; color: #cbd5e1 !important; }
      [data-bs-theme="dark"] #glpi .btn-outline-secondary:hover { background: #334155 !important; color: #e2e8f0 !important; }
      [data-bs-theme="dark"] #glpi [style*="background:#f0f4ff"],
      [data-bs-theme="dark"] #glpi [style*="background:#e8f5e9"],
      [data-bs-theme="dark"] #glpi [style*="background:#fff3e0"] {
        background: #0f172a !important; border: 1px solid #334155 !important;
      }
      [data-bs-theme="dark"] #glpi [style*="background:#f0f4ff"] h5,
      [data-bs-theme="dark"] #glpi [style*="background:#e8f5e9"] h5,
      [data-bs-theme="dark"] #glpi [style*="background:#fff3e0"] h5 { color: #e2e8f0 !important; }
      [data-bs-theme="dark"] #glpi [style*="background:#f0f4ff"] p,
      [data-bs-theme="dark"] #glpi [style*="background:#e8f5e9"] p,
      [data-bs-theme="dark"] #glpi [style*="background:#fff3e0"] p { color: #94a3b8 !important; }
      [data-bs-theme="dark"] #glpi .alert[style*="background:#e8f5e9"] {
        background: rgba(34,197,94,0.10) !important; border-left-color: rgba(34,197,94,0.60) !important;
      }
      [data-bs-theme="dark"] #glpi .alert[style*="background:#e8f5e9"] p,
      [data-bs-theme="dark"] #glpi .alert[style*="background:#e8f5e9"] i { color: #bbf7d0 !important; }
      [data-bs-theme="dark"] #glpi #importRoleSelect {
        background: #0f172a !important; color: #e2e8f0 !important; border-color: #334155 !important;
      }
      [data-bs-theme="dark"] #glpi #syncUsersResult .alert,
      [data-bs-theme="dark"] #glpi #importUsersResult .alert,
      [data-bs-theme="dark"] #glpi #glpi-config-result .alert {
        background: #0f172a !important; border-color: #334155 !important; color: #cbd5e1 !important;
      }
    </style>

    <div class="row">

      <div class="col-12 mb-4">
        <div class="card" style="border-left:4px solid var(--color-primary);">
          <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0 font-weight-bold">
                <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">sync</i>
                Synchronisation Utilisateurs vers GLPI
              </h6>
              <p class="text-xs text-secondary mb-0">Synchronise admins et clients de la plateforme vers GLPI (matching par email)</p>
            </div>
            <button class="btn btn-sm mb-0 text-white" id="syncUsersBtn"
                    style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));"
                    onclick="syncUsersToGlpi()">
              <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">sync</i>
              Synchroniser maintenant
            </button>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row mb-3">
              <div class="col-md-4">
                <div class="p-3 border-radius-md text-center" style="background:#f0f4ff;">
                  <h5 class="font-weight-bolder mb-0" style="color:var(--color-primary);">{{ \App\Models\User::whereIn('role',['admin','client'])->count() }}</h5>
                  <p class="text-xs text-secondary mb-0">Users total</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 border-radius-md text-center" style="background:#e8f5e9;">
                  <h5 class="font-weight-bolder mb-0" style="color:#2e7d32;">{{ \App\Models\User::whereNotNull('glpi_user_id')->count() }}</h5>
                  <p class="text-xs text-secondary mb-0">Deja synchronises</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 border-radius-md text-center" style="background:#fff3e0;">
                  <h5 class="font-weight-bolder mb-0" style="color:#e65100;">{{ \App\Models\User::whereIn('role',['admin','client'])->whereNull('glpi_user_id')->count() }}</h5>
                  <p class="text-xs text-secondary mb-0">En attente de sync</p>
                </div>
              </div>
            </div>
            <div id="syncUsersResult"></div>
          </div>
        </div>
      </div>

      <div class="col-12 mb-4">
        <div class="card" style="border-left:4px solid #2dce89;">
          <div class="card-header pb-0 pt-3 px-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
              <div>
                <h6 class="mb-0 font-weight-bold">
                  <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;color:#2dce89;">download</i>
                  Importer les utilisateurs GLPI vers la plateforme
                </h6>
                <p class="text-xs text-secondary mb-0">Tous les users GLPI avec un email valide seront importes (les comptes systeme sont ignores). Les roles sont determines par les profils GLPI.</p>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <button class="btn btn-sm mb-0 text-white" id="importUsersBtn"
                        style="background:linear-gradient(135deg,#2dce89,#2dcecc);"
                        onclick="importUsersFromGlpi()">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">download</i>
                  Importer maintenant
                </button>
              </div>
            </div>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="alert py-2 px-3 mb-3" style="background:#e8f5e9;border-left:4px solid #2dce89;border-radius:6px;">
              <p class="text-xs mb-0" style="color:#2e7d32;">
                <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">info</i>
                <strong>Roles importes :</strong> Super-Admin et Admin selon les profils GLPI, Client pour les autres utilisateurs. Les users deja existants (meme email) ne seront pas dupliques — leur GLPI ID et roles seront mis a jour si necessaire.
                Un mot de passe aleatoire est genere, il devra etre reinitialise par email.
              </p>
            </div>
            <div id="importUsersResult"></div>
          </div>
        </div>
      </div>

      <div class="col-12 mb-4">
        <div class="card">
          <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold">
              <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">api</i>
              Connexion GLPI
            </h6>
            <button class="btn btn-sm mb-0 btn-outline-secondary"
                    onclick="glpiCall('glpi-config-result','/glpi/session/info')">
              <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">info</i>
              Tester la connexion
            </button>
          </div>
          <div class="card-body px-4 pb-4">
            <form action="{{ route('super-admin.settings') }}" method="POST">
              @csrf
              @method('PUT')
              <input type="hidden" name="_section" value="glpi">
              <div class="mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">URL GLPI</label>
                <input type="url" name="glpi_url" class="form-control"
                       value="{{ $settings['glpi_url'] ?? config('services.glpi.url') }}"
                       placeholder="http://host.docker.internal:8603"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">App Token</label>
                <input type="text" name="glpi_app_token" class="form-control"
                       value="{{ $settings['glpi_app_token'] ?? config('services.glpi.app_token') }}"
                       placeholder="App Token GLPI"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="mb-3">
                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">User Token <small class="text-muted">(laisser vide = inchangé)</small></label>
                <input type="password" name="glpi_user_token" class="form-control"
                       placeholder="{{ !empty($settings['glpi_user_token']) ? '••••••••' : 'User Token GLPI' }}"
                       style="height:45px;border:1px solid #d2d6da;border-radius:8px;padding:0 12px;">
              </div>
              <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="submit" class="btn btn-sm text-white mb-0" style="background:var(--color-primary);">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">save</i>
                  Enregistrer
                </button>
                <div id="glpi-config-result" class="text-xs"></div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold">Profils GLPI</h6>
            <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                    onclick="glpiCall('profiles-result','/glpi/profiles')">
              <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
              Charger
            </button>
          </div>
          <div class="card-body px-4 pb-4">
            <button class="btn btn-sm mb-0 btn-outline-secondary mb-3"
                    onclick="glpiCall('profiles-result','/glpi/profiles/active')">Profil actif</button>
            <div id="profiles-result"><p class="text-xs text-secondary">Cliquez sur Charger</p></div>
            <hr class="my-3">
            <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Changer de profil</p>
            <div class="d-flex gap-2">
              <input type="number" id="profileId" class="form-control form-control-sm" placeholder="ID profil" style="width:120px;">
              <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);" onclick="changeGlpiProfile()">Changer</button>
            </div>
            <div id="profile-result" class="mt-2"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold">Entités GLPI</h6>
            <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                    onclick="glpiCall('entities-result','/glpi/entities')">
              <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
              Charger
            </button>
          </div>
          <div class="card-body px-4 pb-4">
            <button class="btn btn-sm mb-0 btn-outline-secondary mb-3"
                    onclick="glpiCall('entities-result','/glpi/entities/active')">Entité active</button>
            <div id="entities-result"><p class="text-xs text-secondary">Cliquez sur Charger</p></div>
            <hr class="my-3">
            <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Changer d'entité</p>
            <div class="d-flex gap-2 align-items-center flex-wrap">
              <input type="text" id="entityId" class="form-control form-control-sm" placeholder="ID ou 'all'" style="width:100px;">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="entityRecursive">
                <label class="form-check-label text-xs" for="entityRecursive">Récursif</label>
              </div>
              <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);" onclick="changeGlpiEntity()">Changer</button>
            </div>
            <div id="entity-result" class="mt-2"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="mb-0 font-weight-bold">Recherche GLPI</h6>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row mb-3">
              <div class="col-6">
                <label class="form-label text-xs text-secondary">Type d'item</label>
                <select id="srchType" class="form-control form-select form-control-sm">
                  <option value="Ticket">Ticket</option>
                  <option value="User">User</option>
                  <option value="ITILCategory">Catégorie</option>
                  <option value="Computer">Computer</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-xs text-secondary">Valeur</label>
                <input type="text" id="srchValue" class="form-control form-control-sm" placeholder="Terme...">
              </div>
            </div>
            <button class="btn btn-sm mb-0 text-white w-100" style="background:var(--color-primary);" onclick="runGlpiSearch()">
              <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">search</i>Rechercher
            </button>
            <button class="btn btn-sm mb-0 btn-outline-secondary w-100 mt-2"
                    onclick="glpiCall('search-result', '/glpi/search/'+document.getElementById('srchType').value+'/options')">
              Voir champs disponibles
            </button>
            <div id="search-result" class="mt-3"><p class="text-xs text-secondary">Résultats ici</p></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="mb-0 font-weight-bold">Documents & Photos</h6>
          </div>
          <div class="card-body px-4 pb-4">
            <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Upload document</p>
            <div class="d-flex gap-2 mb-2">
              <input type="file" id="glpiUploadFile" class="form-control form-control-sm">
              <button class="btn btn-sm mb-0 text-white flex-shrink-0" style="background:var(--color-primary);" onclick="uploadGlpiDoc()">Upload</button>
            </div>
            <div id="upload-result" class="mb-3"></div>
            <hr class="my-3">
            <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Télécharger document</p>
            <div class="d-flex gap-2 mb-3">
              <input type="number" id="dlDocId" class="form-control form-control-sm" placeholder="ID doc GLPI">
              <a id="dlDocLink" href="#" target="_blank" class="btn btn-sm mb-0 text-white flex-shrink-0" style="background:#28a745;" onclick="buildDlLink(event)">
                <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">download</i>
              </a>
            </div>
            <hr class="my-3">
            <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Photo utilisateur GLPI</p>
            <div class="d-flex gap-2 align-items-center">
              <input type="number" id="picUserId" class="form-control form-control-sm" placeholder="ID user GLPI">
              <button class="btn btn-sm mb-0 text-white flex-shrink-0" style="background:var(--color-primary);" onclick="showUserPic()">Voir</button>
            </div>
            <div id="user-pic-result" class="mt-2"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="mb-0 font-weight-bold">Massive Actions GLPI</h6>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="mb-3">
              <label class="form-label text-xs text-secondary">Type d'item</label>
              <select id="massiveType" class="form-control form-select form-control-sm">
                <option value="Ticket">Ticket</option>
                <option value="User">User</option>
                <option value="Computer">Computer</option>
              </select>
            </div>
            <button class="btn btn-sm mb-0 text-white w-100 mb-2" style="background:var(--color-primary);"
                    onclick="glpiCall('massive-result', '/glpi/massive-actions/'+document.getElementById('massiveType').value)">
              <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">list</i>Voir actions disponibles
            </button>
            <div class="mb-3">
              <label class="form-label text-xs text-secondary mt-2">Clé action</label>
              <input type="text" id="massiveActionKey" class="form-control form-control-sm" placeholder="Ex: MassiveAction:update">
            </div>
            <button class="btn btn-sm mb-0 btn-outline-secondary w-100" onclick="loadMassiveParams()">Voir paramètres de cette action</button>
            <div id="massive-result" class="mt-3"><p class="text-xs text-secondary">Résultats ici</p></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header pb-0 pt-3 px-4">
            <h6 class="mb-0 font-weight-bold">Reset Password GLPI</h6>
            <p class="text-xs text-secondary mb-0">Envoyer reset password directement via GLPI API</p>
          </div>
          <div class="card-body px-4 pb-4">
            <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Demande de reset</p>
            <div class="d-flex gap-2 mb-3">
              <input type="email" id="resetEmail" class="form-control form-control-sm" placeholder="email@exemple.com">
              <button class="btn btn-sm mb-0 text-white flex-shrink-0" style="background:#f57c00;" onclick="glpiPasswordRequest()">
                <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">send</i>
              </button>
            </div>
            <div id="reset-request-result" class="mb-3"></div>
            <hr class="my-3">
            <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Confirmer reset</p>
            <input type="email" id="resetEmail2" class="form-control form-control-sm mb-2" placeholder="Email">
            <input type="text" id="resetToken" class="form-control form-control-sm mb-2" placeholder="Token reçu par email">
            <input type="password" id="resetPwd" class="form-control form-control-sm mb-2" placeholder="Nouveau mot de passe">
            <button class="btn btn-sm mb-0 text-white w-100" style="background:#e53935;" onclick="glpiPasswordConfirm()">Confirmer reset</button>
            <div id="reset-confirm-result" class="mt-2"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
  {{-- ===== FIN GLPI ===== --}}


  {{-- ===== 7. SMS ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'sms' ? 'show active' : '' }}" id="sms" role="tabpanel">

    <style>
      /* ── Settings tabs — segmented control ── */
      #settingsTabs .nav-item { flex: 1; }
      #settingsTabs .nav-link {
        display:flex; justify-content:center; align-items:center; gap:8px; font-size:14px; font-weight:600;
        padding:12px 16px; border-radius:12px; color:var(--color-text-secondary);
        background:transparent; border:none; transition:all .15s ease; white-space:nowrap; width:100%;
      }
      #settingsTabs .nav-link .material-symbols-rounded { font-size:20px; }
      #settingsTabs .nav-link:hover:not(.active) { background:rgba(0,0,0,.04); color:var(--color-text-primary); }
      #settingsTabs .nav-link.active {
        background:var(--color-background-primary) !important;
        color:var(--color-text-primary) !important;
        box-shadow:0 1px 6px rgba(0,0,0,.10),0 0 0 1px rgba(0,0,0,.04);
      }
      @media (prefers-color-scheme: dark) {
        #settingsTabs .nav-link.active { box-shadow:0 1px 6px rgba(0,0,0,.35),0 0 0 1px rgba(255,255,255,.06); }
      }

      /* ── SMS split layout ── */
      .sms-split-layout {
        display: flex;
        gap: 28px;
        align-items: flex-start;
      }
      .sms-split-left {
        flex: 1;
        min-width: 0;
      }
      .sms-split-right {
        width: 290px;
        flex-shrink: 0;
        position: sticky;
        top: 90px;
        z-index: 5;
      }
      @media (max-width: 1100px) {
        .sms-split-layout { flex-direction: column; align-items: stretch; }
        .sms-split-left { width: 100%; }
        .sms-split-right { width: 100%; position: static; display:flex; flex-direction:column; align-items:center; }
      }

      /* SMS field styles */
      .sms-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#64748b; display:flex; align-items:center; gap:6px; margin-bottom:14px; }
      .sms-field-label { font-size:11px; font-weight:700; color:#374151; margin-bottom:5px; display:block; }
      .sms-field-hint { font-size:11px; color:#94a3b8; margin-top:4px; }
      .sms-input { height:44px; border:1.5px solid #e2e8f0; border-radius:10px; padding:0 14px; font-size:13px; color:#1e293b; width:100%; background:#fff; transition:border-color .2s,box-shadow .2s; outline:none; }
      .sms-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
      .sms-input-icon-wrap { position:relative; }
      .sms-input-icon-wrap .sms-icon-left { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:17px; pointer-events:none; }
      .sms-input-icon-wrap .sms-input { padding-left:40px; }
      .sms-input-icon-wrap .sms-icon-right { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#9ca3af; background:none; border:none; padding:0; }
      .sms-input-icon-wrap .sms-icon-right:hover { color:#6366f1; }
      .sms-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:16px; padding:20px 22px; margin-bottom:16px; }
      .sms-card-dark { background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); border-radius:16px; padding:20px 22px; margin-bottom:16px; }
      .sms-status-pill { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700; }
      .sms-status-pill.ok   { background:rgba(34,197,94,.15); color:#16a34a; border:1px solid rgba(34,197,94,.3); }
      .sms-status-pill.fail { background:rgba(239,68,68,.15); color:#dc2626; border:1px solid rgba(239,68,68,.3); }
      .sms-status-dot { width:7px; height:7px; border-radius:50%; }
      .sms-status-dot.ok   { background:#22c55e; animation:pulse-green 1.5s infinite; }
      .sms-status-dot.fail { background:#ef4444; }
      @keyframes pulse-green { 0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.4);} 50%{box-shadow:0 0 0 5px rgba(34,197,94,0);} }
      .sms-toggle-row { display:flex; align-items:center; justify-content:space-between; padding:11px 0; border-bottom:1px solid #f1f5f9; }
      .sms-toggle-row:last-child { border-bottom:none; padding-bottom:0; }
      .sms-toggle-label { display:flex; align-items:center; gap:9px; }
      .sms-trigger-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
      .param-badge { display:inline-flex; align-items:center; gap:5px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; padding:4px 10px; font-size:11px; font-weight:600; color:#475569; }
      .sms-save-btn { width:100%; height:50px; border:none; border-radius:12px; cursor:pointer; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; font-size:14px; font-weight:700; letter-spacing:.03em; box-shadow:0 4px 16px rgba(99,102,241,.3); transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px; }
      .sms-save-btn:hover { transform:translateY(-1px); box-shadow:0 6px 22px rgba(99,102,241,.4); }
      .sms-test-row { display:flex; align-items:center; gap:10px; }
      .sms-test-input-wrap { flex:1; position:relative; }
      .sms-test-prefix { position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:11px; font-weight:700; color:#6366f1; background:#ede9fe; padding:2px 7px; border-radius:5px; pointer-events:none; white-space:nowrap; }
      .sms-test-input { height:40px; border:1.5px solid #e2e8f0; border-radius:10px; padding-left:72px; padding-right:12px; font-size:13px; color:#1e293b; width:100%; outline:none; transition:border-color .2s; }
      .sms-test-input:focus { border-color:#6366f1; }
      .sms-test-btn { height:40px; padding:0 18px; border:none; border-radius:10px; cursor:pointer; background:linear-gradient(135deg,#10b981,#059669); color:#fff; font-size:13px; font-weight:700; white-space:nowrap; box-shadow:0 3px 10px rgba(16,185,129,.3); transition:all .18s; display:flex; align-items:center; gap:6px; }
      .sms-test-btn:hover { transform:translateY(-1px); box-shadow:0 5px 14px rgba(16,185,129,.4); }

      /* ── Phone preview ── */
      .sms-preview-panel {
        background: var(--color-background-secondary, #f8fafc);
        border: 1.5px solid var(--color-border-tertiary, #e2e8f0);
        border-radius: 24px;
        padding: 24px 20px;
      }
      .sms-preview-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .09em;
        color: #94a3b8;
        text-align: center;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
      }
      .phone-frame { width: 220px; margin: 0 auto; background: #111827; border-radius: 36px; padding: 9px; box-shadow: 0 28px 60px rgba(0,0,0,.55), inset 0 0 0 1.5px #374151; position: relative; }
      .phone-screen { background: #0d1117; border-radius: 26px; padding: 10px 8px; min-height: 440px; overflow: hidden; position: relative; }
      .phone-notch { width: 64px; height: 5px; background: #1f2937; border-radius: 3px; margin: 0 auto 10px; }
      .phone-status { display: flex; justify-content: space-between; padding: 0 6px; margin-bottom: 10px; }
      .phone-status span { color: #6b7280; font-size: 9.5px; }
      .sms-thread-header { display: flex; align-items: center; gap: 8px; padding: 0 6px; margin-bottom: 12px; }
      .sms-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg,#6366f1,#8b5cf6); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
      .sms-bubble { background: #1e293b; border-radius: 3px 11px 11px 11px; padding: 8px 10px; margin-bottom: 9px; max-width: 95%; position: relative; }
      .sms-bubble-text { color: #e2e8f0; font-size: 10px; line-height: 1.55; margin: 0; }
      .sms-bubble-label { color: #6b7280; font-size: 8.5px; margin-bottom: 4px; padding-left: 2px; }
      .sms-bubble.blue  { border-left: 3px solid #6366f1; }
      .sms-bubble.green { border-left: 3px solid #10b981; }
      .sms-bubble.amber { border-left: 3px solid #f59e0b; }
      .sms-bubble.purple{ border-left: 3px solid #8b5cf6; }
      .phone-side-btn { position: absolute; background: #1f2937; border-radius: 2px; }
      .phone-home-bar { display: flex; justify-content: center; margin-top: 8px; }
      .phone-home-bar span { width: 36px; height: 3px; background: #1f2937; border-radius: 2px; }

      /* SMS dark mode */
      [data-bs-theme="dark"] #sms .sms-card { background:#1e293b !important; border-color:#334155 !important; box-shadow:none !important; }
      [data-bs-theme="dark"] #sms .sms-preview-panel { background: rgba(255,255,255,0.03) !important; border-color:#334155 !important; }
      [data-bs-theme="dark"] #sms .sms-section-title { color:#94a3b8 !important; }
      [data-bs-theme="dark"] #sms .sms-field-label { color:#cbd5e1 !important; }
      [data-bs-theme="dark"] #sms .sms-field-hint { color:#64748b !important; }
      [data-bs-theme="dark"] #sms .sms-input,[data-bs-theme="dark"] #sms .sms-test-input { background:#0f172a !important; border-color:#334155 !important; color:#e2e8f0 !important; }
      [data-bs-theme="dark"] #sms .sms-input::placeholder,[data-bs-theme="dark"] #sms .sms-test-input::placeholder { color:#64748b !important; }
      [data-bs-theme="dark"] #sms .sms-icon-left,[data-bs-theme="dark"] #sms .sms-icon-right { color:#94a3b8 !important; }
      [data-bs-theme="dark"] #sms .sms-toggle-row { border-bottom-color:#1f2a3a !important; }
      [data-bs-theme="dark"] #sms .sms-toggle-label span { color:#e2e8f0 !important; }
      [data-bs-theme="dark"] #sms .form-check-input { background-color:#334155 !important; border-color:#475569 !important; }
      [data-bs-theme="dark"] #sms .form-check-input:checked { background-color:var(--color-primary) !important; border-color:var(--color-primary) !important; }
      [data-bs-theme="dark"] #sms .param-badge { background:rgba(255,255,255,0.06) !important; border-color:#334155 !important; color:#cbd5e1 !important; }
      [data-bs-theme="dark"] #sms .sms-test-prefix { color:#c7d2fe !important; background:rgba(99,102,241,0.16) !important; border:1px solid rgba(99,102,241,0.25); }
      [data-bs-theme="dark"] #sms #testSmsMessage { background:#0f172a !important; color:#e2e8f0 !important; border-color:#334155 !important; }
      @keyframes sms-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
    </style>

    <form method="POST" action="{{ route('super-admin.settings') }}" id="smsForm">
      @csrf
      @method('PUT')
      <input type="hidden" name="_section" value="sms">

      {{-- ── SPLIT SCREEN LAYOUT ── --}}
      <div class="sms-split-layout">

        {{-- LEFT — Configuration --}}
        <div class="sms-split-left">

          {{-- Provider header --}}
          <div class="sms-card-dark">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
              <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;background:rgba(99,102,241,.25);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(99,102,241,.3);">
                  <i class="material-symbols-rounded" style="font-size:22px;color:#818cf8;">sms</i>
                </div>
                <div>
                  <h6 class="mb-0 font-weight-bold text-white" style="font-size:15px;">Configuration SMS Provider</h6>
                  <p class="mb-0 text-xs" style="color:#94a3b8;margin-top:2px;">Renseignez les informations fournies par votre opérateur (Tunisie SMS, etc.)</p>
                </div>
              </div>
              @php $smsOk = \App\Models\Setting::get('sms_api_url') && \App\Models\Setting::get('sms_api_key'); @endphp
              <span class="sms-status-pill {{ $smsOk ? 'ok' : 'fail' }}">
                <span class="sms-status-dot {{ $smsOk ? 'ok' : 'fail' }}"></span>
                {{ $smsOk ? 'Configuré' : 'Non configuré' }}
              </span>
            </div>
          </div>

          <input type="hidden" name="sms_api_type" value="post_json">

          {{-- API URL --}}
          <div class="sms-card">
            <div class="sms-section-title">
              <i class="material-symbols-rounded" style="font-size:15px;color:#6366f1;">link</i>
              URL de l'API SMS
            </div>
            <div class="sms-input-icon-wrap">
              <i class="material-symbols-rounded sms-icon-left">language</i>
              <input type="url" name="sms_api_url" class="sms-input"
                     value="{{ \App\Models\Setting::get('sms_api_url', 'https://mystudents.tunisiesms.tn/api/sms') }}"
                     placeholder="ecrire l'url de l'api ici"
                     oninput="updateSmsPreview()">
            </div>
          </div>

          {{-- API Key + Sender --}}
          <div class="sms-card">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="sms-section-title">
                  <i class="material-symbols-rounded" style="font-size:15px;color:#6366f1;">key</i>
                  Clé API / Token <span class="text-danger ms-1">*</span>
                </div>
                <div class="sms-input-icon-wrap">
                  <i class="material-symbols-rounded sms-icon-left">vpn_key</i>
                  <input type="password" name="sms_api_key" id="smsApiKey" class="sms-input"
                         value="{{ \App\Models\Setting::get('sms_api_key', '') }}" placeholder="votre-cle-api">
                  <button type="button" class="sms-icon-right" onclick="toggleApiKey()">
                    <i class="material-symbols-rounded" id="toggleIcon" style="font-size:18px;">visibility</i>
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <div class="sms-section-title">
                  <i class="material-symbols-rounded" style="font-size:15px;color:#6366f1;">badge</i>
                  Nom expéditeur
                </div>
                <div class="sms-input-icon-wrap">
                  <i class="material-symbols-rounded sms-icon-left">person</i>
                  <input type="text" name="sms_sender" class="sms-input" maxlength="11"
                         value="{{ \App\Models\Setting::get('sms_sender', 'L2T') }}"
                         placeholder="L2T" oninput="updateSmsPreview()">
                  <button type="button" class="sms-icon-right" title="Copier" onclick="copySender()">
                    <i class="material-symbols-rounded" style="font-size:16px;">content_copy</i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          {{-- Triggers --}}
          <div class="sms-card">
            <div class="sms-section-title">
              <i class="material-symbols-rounded" style="font-size:15px;color:#6366f1;">notifications_active</i>
              Quand envoyer un SMS au client ?
            </div>
            @php
              $triggers = [
                ['sms_notify_new_ticket',    'Ticket créé (confirmation)',  'confirmation_number', '#eff6ff', '#3b82f6'],
                ['sms_notify_status_change', 'Changement de statut',        'sync_alt',            '#fefce8', '#d97706'],
                ['sms_notify_reply',         'Réponse de l\'admin',          'reply',               '#f0fdf4', '#16a34a'],
                ['sms_notify_resolved',      'Ticket résolu / clôturé',     'task_alt',            '#fdf4ff', '#9333ea'],
              ];
            @endphp
            @foreach($triggers as [$key, $label, $icon, $bg, $color])
            <div class="sms-toggle-row">
              <div class="sms-toggle-label">
                <div class="sms-trigger-icon" style="background:{{ $bg }};">
                  <i class="material-symbols-rounded" style="font-size:16px;color:{{ $color }};">{{ $icon }}</i>
                </div>
                <span class="text-sm" style="color:#374151;font-weight:500;">{{ $label }}</span>
              </div>
              <div class="form-check form-switch mb-0" style="padding-left:2.5rem;">
                <input class="form-check-input" type="checkbox" name="{{ $key }}" value="1"
                       style="width:42px;height:22px;cursor:pointer;"
                       {{ \App\Models\Setting::get($key, '0') === '1' ? 'checked' : '' }}>
              </div>
            </div>
            @endforeach
          </div>

          {{-- Test SMS --}}
          <div class="sms-card">
            <div class="sms-section-title">
              <i class="material-symbols-rounded" style="font-size:15px;color:#10b981;">send</i>
              Tester l'envoi SMS
            </div>
            <div style="margin-bottom:10px;">
              <label style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:6px;">Message de test</label>
              <textarea id="testSmsMessage" rows="2"
                        placeholder="Ecrire votre message de test ici"
                        style="width:100%;padding:10px 12px;border-radius:10px;border:1.5px solid #e2e8f0;font-size:13px;resize:none;outline:none;transition:border-color .2s;background:var(--bs-body-bg,#fff);color:inherit;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'"
                        maxlength="160" oninput="updateSmsTestPreview()"></textarea>
              <div style="text-align:right;font-size:10px;color:#94a3b8;margin-top:3px;">
                <span id="testSmsCharCount">0</span>/160 caractères
              </div>
            </div>
            <div class="sms-test-row">
              <div class="sms-test-input-wrap">
                <span class="sms-test-prefix">🇹🇳 +216</span>
                <input type="text" id="testSmsPhone" class="sms-test-input"
                       placeholder="********" maxlength="8"
                       oninput="this.value=this.value.replace(/\D/g,''); updateSmsTestPreview()">
              </div>
              <button type="button" onclick="testSms()" class="sms-test-btn">
                <i class="material-symbols-rounded" style="font-size:15px;">send</i>
                Test
              </button>
            </div>
            <div id="testSmsResult" style="display:none;margin-top:10px;padding:10px 12px;border-radius:10px;font-size:12px;"></div>
          </div>

          <button type="submit" class="sms-save-btn">
            <i class="material-symbols-rounded" style="font-size:18px;">save</i>
            Enregistrer la configuration SMS
          </button>

        </div>{{-- /sms-split-left --}}

        {{-- RIGHT — Phone preview --}}
        <div class="sms-split-right">
          <div class="sms-preview-panel">
            <div class="sms-preview-label">
              <i class="material-symbols-rounded" style="font-size:13px;">preview</i>
              Aperçu sur mobile
            </div>

            <div class="phone-frame">
              {{-- Side buttons --}}
              <div class="phone-side-btn" style="right:-3px;top:76px;width:3px;height:26px;"></div>
              <div class="phone-side-btn" style="left:-3px;top:64px;width:3px;height:18px;"></div>
              <div class="phone-side-btn" style="left:-3px;top:90px;width:3px;height:18px;"></div>

              <div class="phone-screen">
                <div class="phone-notch"></div>

                {{-- Status bar --}}
                <div class="phone-status">
                  <span>9:41</span>
                  <div class="d-flex gap-1">
                    <i class="material-symbols-rounded" style="font-size:10px;color:#6b7280;">signal_cellular_alt</i>
                    <i class="material-symbols-rounded" style="font-size:10px;color:#6b7280;">wifi</i>
                    <i class="material-symbols-rounded" style="font-size:10px;color:#6b7280;">battery_full</i>
                  </div>
                </div>

                {{-- Thread header --}}
                <div class="sms-thread-header">
                  <div class="sms-avatar">
                    <i class="material-symbols-rounded" style="font-size:13px;color:#fff;">sms</i>
                  </div>
                  <div>
                    <p id="previewSender" class="mb-0 font-weight-bold" style="color:#f1f5f9;font-size:11px;">L2T</p>
                    <p class="mb-0" style="color:#4b5563;font-size:9px;">SMS • maintenant</p>
                  </div>
                </div>

                {{-- Bubbles --}}
                <div class="px-1">
                  <p class="sms-bubble-label">Ticket créé</p>
                  <div class="sms-bubble blue">
                    <p class="sms-bubble-text" id="previewMsg1">Votre ticket a bien été reçu. Notre équipe vous répond dans les meilleurs délais.</p>
                  </div>

                  <p class="sms-bubble-label">Mise à jour statut</p>
                  <div class="sms-bubble green">
                    <p class="sms-bubble-text">Votre ticket est en cours de traitement. Connectez-vous pour suivre l'avancement.</p>
                  </div>

                  <p class="sms-bubble-label">Résolution</p>
                  <div class="sms-bubble amber">
                    <p class="sms-bubble-text">Votre ticket est résolu ✅. Connectez-vous pour confirmer la clôture.</p>
                  </div>

                  {{-- Test preview (appears when phone is filled) --}}
                  <div id="testPreviewContainer" style="display:none;">
                    <p class="sms-bubble-label" style="color:#8b5cf6;">Test en cours…</p>
                    <div class="sms-bubble purple">
                      <p class="sms-bubble-text" id="testPreviewMsg">Test SMS depuis le panneau admin. Si vous recevez ce message, la configuration est correcte ✓</p>
                      <p class="mb-0" style="color:#6b7280;font-size:8.5px;margin-top:4px;">→ <span id="testPreviewPhone">—</span></p>
                    </div>
                  </div>
                </div>

                <div class="phone-home-bar mt-2"><span></span></div>
              </div>{{-- /phone-screen --}}
            </div>{{-- /phone-frame --}}

            {{-- Live indicator --}}
            <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
              <span class="sms-status-dot ok" style="width:6px;height:6px;"></span>
              <span style="font-size:10px;color:#94a3b8;font-weight:600;">Aperçu en temps réel</span>
            </div>
          </div>
        </div>{{-- /sms-split-right --}}

      </div>{{-- /sms-split-layout --}}
    </form>

  </div>
  {{-- ===== FIN SMS ===== --}}

  {{-- ===== 8. VOICE AGENTS ===== --}}
  <div class="tab-pane fade {{ $activeTab === 'voice_agents' ? 'show active' : '' }}" id="voice_agents" role="tabpanel">
    @php
      $apiBase = rtrim((string) config('services.support_api.public_url'), '/');
    @endphp
    <style>
    .va-page{
      --va-bg:transparent;
      --va-panel:#ffffff;
      --va-panel-2:#f1f5f9;
      --va-border:#e2e8f0;
      --va-text:#1e293b;
      --va-muted:#64748b;
      --va-good:#10b981;
      --va-bad:#ef4444;
      --va-warn:#f59e0b;
      min-height:calc(100vh - 120px);
      background:var(--va-bg);
      border-radius:16px;
      color:var(--va-text);
      font-family:Inter,system-ui,sans-serif;
    }

    [data-bs-theme="dark"] .va-page {
      --va-bg: transparent;
      --va-panel: #1e293b;
      --va-panel-2: #334155;
      --va-border: #334155;
      --va-text: #f1f5f9;
      --va-muted: #94a3b8;
    }

    /* ── Header ── */
    .va-head-card{
      background: var(--va-panel);
      border-radius: 20px;
      padding: 32px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 24px;
      border: 1px solid var(--va-border);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .va-head-left{display:flex;align-items:center;gap:20px;}
    .va-head-icon{
      width:60px;height:60px;
      background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
      border-radius:16px;
      display:flex;align-items:center;justify-content:center;flex-shrink:0;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    .va-title{color:var(--va-text);font-weight:800;margin:0;font-size:26px;letter-spacing:-0.03em;}
    .va-sub{color:var(--va-muted);margin:0;font-size:14px;font-weight:500;}

    .va-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);gap:20px}

    .va-card{
      background: var(--va-panel);
      border: 1px solid var(--va-border);
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .va-card h3{
      font-size:16px;
      font-weight:800;
      color:var(--va-text);
      margin:0 0 20px;
      display:flex;
      align-items:center;
      gap:10px;
      letter-spacing:-0.01em;
    }

    .va-actions{display:flex;flex-wrap:wrap;gap:10px}
    .va-btn{
      border:2px solid var(--va-border);
      border-radius:12px;
      background:var(--va-panel);
      color:var(--va-muted);
      padding:10px 18px;
      font-size:13px;
      font-weight:700;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:8px;
      transition: all 0.2s ease;
    }
    .va-btn:hover{
      border-color:var(--color-primary);
      color:var(--color-primary);
      transform:translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .va-btn.primary{
      background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
      border-color:transparent;
      color:#fff;
    }
    .va-btn.primary:hover{
      color:#fff;
      opacity:0.95;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .va-btn.danger{
      background:rgba(225, 29, 72, 0.1);
      border-color:rgba(225, 29, 72, 0.2);
      color:#e11d48;
    }
    .va-btn.danger:hover{
      background:#e11d48;
      color:#fff;
      border-color:#e11d48;
    }

    .va-status{display:flex;gap:14px;align-items:center;margin-bottom:20px;padding:16px;background:var(--va-panel-2);border-radius:14px;border:1px solid var(--va-border);}
    .va-dot{width:12px;height:12px;border-radius:999px;background:var(--va-bad);box-shadow:0 0 0 5px rgba(239,68,68,.1)}
    .va-dot.on{background:var(--va-good);box-shadow:0 0 0 5px rgba(16,185,129,.1)}

    .va-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px}
    .va-kpi{border:1px solid var(--va-border);border-radius:14px;background:var(--va-panel);padding:14px;text-align:center;}
    .va-kpi span{display:block;font-size:11px;font-weight:800;color:var(--va-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px}
    .va-kpi strong{font-size:18px;color:var(--color-primary);font-family:JetBrains Mono,Consolas,monospace;font-weight:800;}

    .va-form{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .va-field{display:flex;flex-direction:column;gap:6px}
    .va-field.full{grid-column:1/-1}
    .va-field label{font-size:11px;font-weight:800;color:var(--va-muted);text-transform:uppercase;letter-spacing:.05em}
    .va-field input,.va-field select{
      height:44px;
      border-radius:12px;
      border:2px solid var(--va-border);
      background:var(--va-panel);
      color:var(--va-text);
      padding:0 14px;
      font-size:13px;
      font-weight:600;
      outline:none;
      transition: all 0.2s ease;
    }
    .va-field input:focus,.va-field select:focus{
      border-color:var(--color-primary);
      background: var(--va-panel) !important;
      color: var(--va-text) !important;
      box-shadow: 0 0 0 4px color-mix(in srgb,var(--color-primary) 10%,transparent)
    }

    .va-log{
      height:400px;
      overflow:auto;
      background:#0f172a;
      border:1px solid #1e293b;
      border-radius:14px;
      padding:16px;
      font:12px/1.6 JetBrains Mono,Consolas,monospace;
      color:#e2e8f0;
      white-space:pre-wrap;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }
    .va-alert{display:none;margin-bottom:16px;border-radius:12px;padding:12px 16px;font-size:14px;font-weight:600;border:1px solid}
    .va-alert.show{display:block}
    .va-alert.ok{background:rgba(22, 163, 74, 0.1);border-color:rgba(22, 163, 74, 0.2);color:#22c55e}
    .va-alert.err{background:rgba(239, 68, 68, 0.1);border-color:rgba(239, 68, 68, 0.2);color:#ef4444}

    .va-test{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end}
    .va-token{margin-top:16px;word-break:break-all;border:2px dashed var(--va-border);border-radius:12px;background:var(--va-panel-2);padding:16px;color:var(--va-muted);font-size:13px;font-family:monospace;}

    @media(max-width:1100px){.va-grid{grid-template-columns:1fr}.va-form{grid-template-columns:1fr}.va-kpis{grid-template-columns:1fr}}
    </style>

    <div class="va-page">
      <div class="va-head-card">
        <div class="va-head-left">
          <div class="va-head-icon">
            <i class="material-symbols-rounded text-white" style="font-size:32px;">graphic_eq</i>
          </div>
          <div>
            <h1 class="va-title">Voice Agents Runtime</h1>
            <div class="va-sub">Gestion et supervision en temps réel des agents vocaux IA</div>
          </div>
        </div>
        <div class="va-actions">
          <button class="va-btn" id="refreshBtn">
            <i class="material-symbols-rounded" style="font-size:18px;">refresh</i> Actualiser
          </button>
          <button class="va-btn primary" id="startDevBtn">
            <i class="material-symbols-rounded" style="font-size:18px;">code</i> Start Dev
          </button>
          <button class="va-btn primary" id="startProdBtn">
            <i class="material-symbols-rounded" style="font-size:18px;">play_arrow</i> Lancer
          </button>
          <button class="va-btn danger" id="stopBtn">
            <i class="material-symbols-rounded" style="font-size:18px;">stop</i> Arrêter
          </button>
        </div>
      </div>

      <div id="alertBox" class="va-alert"></div>

      <div class="va-grid">
        <section class="va-card">
          <h3><i class="material-symbols-rounded text-primary">monitoring</i> État du service</h3>
          <div class="va-status">
            <span class="va-dot" id="statusDot"></span>
            <div>
              <div id="statusText" style="font-weight:800;color:#1e293b;font-size:16px;">Vérification...</div>
              <div id="statusSub" style="font-size:12px;color:var(--va-muted);">En attente de réponse de l'API FastAPI.</div>
            </div>
          </div>
          <div class="va-kpis">
            <div class="va-kpi"><span>Mode</span><strong id="kpiMode">-</strong></div>
            <div class="va-kpi"><span>PID</span><strong id="kpiPid">-</strong></div>
            <div class="va-kpi"><span>Uptime</span><strong id="kpiUptime">-</strong></div>
          </div>
        </section>

        <section class="va-card">
          <h3><i class="material-symbols-rounded text-primary">vibration</i> LiveKit Smoke Test</h3>
          <div class="va-test">
            <div class="va-field">
              <label>Test room token</label>
              <input id="testRoomLabel" value="support-room" disabled>
            </div>
            <button class="va-btn primary" id="testTokenBtn">Générer un token</button>
          </div>
          <div class="va-token" id="tokenBox">Aucun token généré pour le moment.</div>
        </section>

        <section class="va-card">
          <h3><i class="material-symbols-rounded text-primary">settings</i> Configuration</h3>
          <form id="configForm" class="va-form">
            <div class="va-field"><label>LiveKit URL</label><input name="livekit_url"></div>
            <div class="va-field"><label>AI provider</label><select name="ai_response_provider"><option value="gemini">gemini</option><option value="openai">openai</option><option value="claude">claude</option><option value="local">local</option></select></div>
            <div class="va-field"><label>LiveKit API key</label><input name="livekit_api_key"></div>
            <div class="va-field"><label>LiveKit API secret</label><input name="livekit_api_secret" type="password"></div>
            <div class="va-field"><label>Gemini model</label><input name="gemini_model"></div>
            <div class="va-field"><label>OpenAI model</label><input name="openai_model"></div>
            <div class="va-field"><label>Backend API URL</label><input name="backend_api_url"></div>
            <div class="va-field"><label>Recordings dir</label><input name="voice_recordings_dir"></div>
            <div class="va-field full"><label>Gemini API key(s)</label><input name="gemini_api_key" type="password"></div>
            <div class="va-field"><label>Google API key</label><input name="google_api_key" type="password"></div>
            <div class="va-field"><label>OpenAI API key</label><input name="openai_api_key" type="password"></div>
            <div class="va-field"><label>Anthropic API key</label><input name="anthropic_api_key" type="password"></div>
            <div class="va-field"><label>Internal service key</label><input name="internal_service_key" type="password"></div>
            <div class="va-field full"><label>Database URL</label><input name="database_url"></div>
            <div class="va-field"><label>Use realtime</label><select name="use_realtime"><option value="true">true</option><option value="false">false</option></select></div>
            <div class="va-field" style="justify-content:end; grid-column: 2;"><button class="va-btn primary w-100" type="submit" style="justify-content:center;">Enregistrer la configuration</button></div>
          </form>
        </section>

        <section class="va-card">
          <h3><i class="material-symbols-rounded text-primary">terminal</i> Journaux d'exécution (Logs)</h3>
          <div class="va-log" id="logBox">Chargement des logs...</div>
        </section>
      </div>
    </div>
  </div>
  {{-- ===== FIN VOICE AGENTS ===== --}}

</div>
{{-- ═══════════════ FIN TAB-CONTENT ═══════════════ --}}

</div>{{-- /#superAdminSettingsPage --}}


{{-- ═══════════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════════ --}}
<script>
(function(){
  const $ = (id) => document.getElementById(id);
  let currentConfig = null;

  async function api(path, options) {
    options = options || {};
    if (window.supportBackendFetch) {
      return window.supportBackendFetch(path, options);
    }

    const headers = Object.assign({'Accept':'application/json'}, options.headers || {});
    if (options.body && !(options.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    const base = @json(rtrim((string) config('services.support_api.public_url'), '/'));
    const url = base.replace(/\/$/, '') + '/' + String(path || '').replace(/^\//, '');
    const res = await fetch(url, Object.assign({}, options, {headers}));
    const text = await res.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch (_) { data = {message:text}; }
    if (!res.ok) throw new Error(data.detail || data.message || res.statusText || 'Request failed');
    return data;
  }
  
  function vAlert(message, ok) {
    const box = $('alertBox');
    if(!box) return;
    box.textContent = message;
    box.className = 'va-alert show ' + (ok ? 'ok' : 'err');
    window.clearTimeout(vAlert._t);
    vAlert._t = window.setTimeout(() => box.className = 'va-alert', 4500);
  }

  function fmtUptime(sec) {
    if (!sec && sec !== 0) return '-';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = Math.floor(sec % 60);
    return h ? `${h}h ${m}m` : `${m}m ${s}s`;
  }

  function fillConfig(config) {
    currentConfig = config;
    const form = $('configForm');
    if(!form) return;
    Object.keys(config).forEach((key) => {
      const input = form.elements[key];
      if (!input) return;
      if (key === 'use_realtime') input.value = config[key] ? 'true' : 'false';
      else input.value = config[key] ?? '';
    });
  }

  function readConfig() {
    const form = $('configForm');
    const out = Object.assign({}, currentConfig || {});
    if(!form) return out;
    Array.from(form.elements).forEach((el) => {
      if (!el.name) return;
      out[el.name] = el.name === 'use_realtime' ? el.value === 'true' : el.value;
    });
    return out;
  }

  async function refreshStatus() {
    if(!$('statusDot')) return;
    const status = await api('/voice-agents/status');
    $('statusDot').classList.toggle('on', Boolean(status.running));
    $('statusText').textContent = status.running ? 'Running' : 'Stopped';
    $('statusSub').textContent = status.started_at ? `Started ${new Date(status.started_at).toLocaleString()}` : 'No active runtime process.';
    $('kpiMode').textContent = status.mode || '-';
    $('kpiPid').textContent = status.pid || '-';
    $('kpiUptime').textContent = fmtUptime(status.uptime_seconds);
  }

  async function refreshLogs() {
    if(!$('logBox')) return;
    const logs = await api('/voice-agents/logs?lines=220');
    $('logBox').textContent = (logs.lines || []).join('\n') || 'No logs yet.';
  }

  async function refreshConfig() {
    if(!$('configForm')) return;
    const data = await api('/voice-agents/config');
    fillConfig(data.config || {});
  }

  async function refreshAll() {
    try {
      await Promise.all([refreshStatus(), refreshLogs(), refreshConfig()]);
    } catch (err) {
      vAlert(err.message, false);
    }
  }

  async function start(mode) {
    try {
      await api('/voice-agents/start', {method:'POST', body: JSON.stringify({mode})});
      vAlert(`Voice agents started in ${mode} mode.`, true);
      await refreshAll();
    } catch (err) {
      vAlert(err.message, false);
    }
  }

  if($('refreshBtn')) $('refreshBtn').addEventListener('click', refreshAll);
  if($('startDevBtn')) $('startDevBtn').addEventListener('click', () => start('dev'));
  if($('startProdBtn')) $('startProdBtn').addEventListener('click', () => start('start'));
  if($('stopBtn')) $('stopBtn').addEventListener('click', async () => {
    try {
      await api('/voice-agents/stop', {method:'POST'});
      vAlert('Voice agents stopped.', true);
      await refreshAll();
    } catch (err) {
      vAlert(err.message, false);
    }
  });
  if($('testTokenBtn')) $('testTokenBtn').addEventListener('click', async () => {
    try {
      const data = await api('/voice-agents/test-token');
      $('tokenBox').textContent = `URL: ${data.url}\n\nTOKEN: ${data.token}`;
      vAlert('LiveKit test token generated.', true);
    } catch (err) {
      vAlert(err.message, false);
    }
  });
  if($('configForm')) $('configForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const data = await api('/voice-agents/config', {method:'PUT', body: JSON.stringify(readConfig())});
      fillConfig(data.config || readConfig());
      vAlert('Configuration saved.', true);
    } catch (err) {
      vAlert(err.message, false);
    }
  });

  if($('refreshBtn')) {
    refreshAll();
    window.setInterval(() => { refreshStatus().catch(() => {}); }, 10000);
  }
})();

function previewLogo(event) {
  const file = event.target.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert('Le fichier est trop volumineux (max 2MB)'); event.target.value = ''; return; }
  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('previewImage').src = e.target.result;
    document.getElementById('logoPreview').style.display = 'block';
    document.getElementById('uploadBtn').disabled = false;
  };
  reader.readAsDataURL(file);
}

/* ── SMS Preview ── */
function updateSmsPreview() {
  var sender = (document.querySelector('[name="sms_sender"]') || {value:'L2T'}).value.trim() || 'L2T';
  var el = document.getElementById('previewSender');
  if (el) el.textContent = sender;
}

function updateSmsTestPreview() {
  var phone   = (document.getElementById('testSmsPhone') || {value:''}).value.trim();
  var message = (document.getElementById('testSmsMessage') || {value:''}).value.trim();
  var container = document.getElementById('testPreviewContainer');
  var msgEl     = document.getElementById('testPreviewMsg');
  var phoneEl   = document.getElementById('testPreviewPhone');
  if (!container) return;
  if (phone) {
    container.style.display = 'block';
    if (phoneEl) phoneEl.textContent = '+216' + phone;
    if (msgEl && message) msgEl.textContent = message;
    else if (msgEl) msgEl.textContent = 'Test SMS depuis le panneau admin. Si vous recevez ce message, la configuration est correcte ✓';
  } else {
    container.style.display = 'none';
  }
}

function toggleApiKey() {
  var input = document.getElementById('smsApiKey');
  var icon  = document.getElementById('toggleIcon');
  input.type = input.type === 'password' ? 'text' : 'password';
  icon.textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
  icon.style.color = input.type === 'text' ? '#6366f1' : '#9ca3af';
}

function copySender() {
  var val = document.querySelector('[name="sms_sender"]').value;
  navigator.clipboard.writeText(val).then(function() {
    if (typeof showToast === 'function') showToast('Expéditeur copié !');
  });
}

function testSms() {
  var phone   = document.getElementById('testSmsPhone').value.trim();
  var message = (document.getElementById('testSmsMessage').value.trim()) || 'Test SMS valide';
  if (!phone || phone.length < 8) { showTestResult('⚠️ Entrez un numéro valide à 8 chiffres.', '#fef3c7', '#92400e'); return; }
  var btn = document.querySelector('.sms-test-btn');
  btn.disabled = true;
  btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="animation:sms-spin 1s linear infinite;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Envoi…';
  fetch('/super-admin/sms/test', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
    body: JSON.stringify({ phone: phone, message: message })
  })
  .then(function(r){ return r.json(); })
  .then(function(data) { showTestResult(data.success ? '✅ '+data.message : '❌ '+data.message, data.success ? '#f0fdf4' : '#fef2f2', data.success ? '#166534' : '#dc2626'); })
  .catch(function() { showTestResult('❌ Erreur réseau — vérifiez votre configuration.', '#fef2f2', '#dc2626'); })
  .finally(function() { btn.disabled = false; btn.innerHTML = '<i class="material-symbols-rounded" style="font-size:15px;">send</i> Test'; });
}

function showTestResult(msg, bg, color) {
  var el = document.getElementById('testSmsResult');
  el.style.display = 'block'; el.style.background = bg; el.style.color = color;
  el.style.border = '1px solid '+color+'44'; el.textContent = msg;
}

document.addEventListener('DOMContentLoaded', function() {
  /* Char counter */
  var msgEl   = document.getElementById('testSmsMessage');
  var countEl = document.getElementById('testSmsCharCount');
  if (msgEl && countEl) {
    msgEl.addEventListener('input', function() {
      countEl.textContent = this.value.length;
      countEl.style.color = this.value.length > 140 ? '#ef4444' : '#94a3b8';
    });
  }
  /* Init preview */
  updateSmsPreview();
  ['sms_api_url','sms_sender'].forEach(function(name) {
    var el = document.querySelector('[name="'+name+'"]');
    if (el) el.addEventListener('input', updateSmsPreview);
  });
});

/* ── GLPI ── */
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
async function glpiCall(targetId, url, method='GET', body=null) {
    const el = document.getElementById(targetId);
    if(el) el.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
    try {
        const opts = { method, headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN } };
        if(body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const res  = await fetch(url, opts);
        const data = await res.json();
        if(el) renderGlpiResult(el, data.data ?? data);
        return data;
    } catch(e) {
        if(el) el.innerHTML = `<p class="text-danger text-xs mb-0">❌ ${e.message}</p>`;
    }
}
function renderGlpiResult(el, data) {
    if(!data) { el.innerHTML='<p class="text-xs text-secondary">Aucune donnée</p>'; return; }
    if(Array.isArray(data)) {
        const keys = Object.keys(data[0]||{}).slice(0,6);
        let h = `<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:11px;"><thead><tr>${keys.map(k=>`<th class="text-secondary text-uppercase" style="font-size:10px;">${k}</th>`).join('')}</tr></thead><tbody>`;
        data.slice(0,20).forEach(r=>{h+=`<tr>${keys.map(k=>`<td>${r[k]??'-'}</td>`).join('')}</tr>`;});
        h+=`</tbody></table></div>`;
        if(data.length>20) h+=`<p class="text-xs text-secondary mt-1">${data.length} éléments total</p>`;
        el.innerHTML=h;
    } else {
        const entries = Object.entries(data).slice(0,12);
        el.innerHTML = entries.map(([k,v])=>`<div class="d-flex justify-content-between py-1 border-bottom"><span class="text-xs text-secondary">${k}</span><span class="text-xs font-weight-bold">${typeof v==='object'?JSON.stringify(v).slice(0,40):v}</span></div>`).join('');
    }
}
async function changeGlpiProfile() {
    const id = document.getElementById('profileId').value;
    if(!id) return;
    const data = await glpiCall('profile-result', '/glpi/profiles/change', 'POST', {profiles_id: parseInt(id)});
    document.getElementById('profile-result').innerHTML = data?.success ? '<p class="text-success text-xs mb-0">✅ Profil changé avec succès</p>' : '<p class="text-danger text-xs mb-0">❌ Erreur changement profil</p>';
}
async function changeGlpiEntity() {
    const id  = document.getElementById('entityId').value;
    const rec = document.getElementById('entityRecursive').checked;
    const data = await glpiCall('entity-result', '/glpi/entities/change', 'POST', {entities_id: id||'all', is_recursive: rec});
    document.getElementById('entity-result').innerHTML = data?.success ? '<p class="text-success text-xs mb-0">✅ Entité changée avec succès</p>' : '<p class="text-danger text-xs mb-0">❌ Erreur changement entité</p>';
}
async function runGlpiSearch() {
    const type  = document.getElementById('srchType').value;
    const value = document.getElementById('srchValue').value;
    await glpiCall('search-result', `/glpi/search/${type}?criteria[0][field]=1&criteria[0][searchtype]=contains&criteria[0][value]=${encodeURIComponent(value)}`);
}
async function uploadGlpiDoc() {
    const file = document.getElementById('glpiUploadFile').files[0];
    if(!file) { alert('Choisissez un fichier'); return; }
    const formData = new FormData();
    formData.append('file', file);
    const el = document.getElementById('upload-result');
    el.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    try {
        const res  = await fetch('/glpi/documents/upload', { method:'POST', headers:{'X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'}, body:formData });
        const data = await res.json();
        el.innerHTML = data.success ? `<p class="text-success text-xs mb-0">✅ Uploadé — ID GLPI: <strong>${data.data?.id??'N/A'}</strong></p>` : `<p class="text-danger text-xs mb-0">❌ ${data.error??'Erreur'}</p>`;
    } catch(e) { el.innerHTML = `<p class="text-danger text-xs mb-0">❌ ${e.message}</p>`; }
}
function buildDlLink(e) {
    const id = document.getElementById('dlDocId').value;
    if(!id) { e.preventDefault(); alert('Entrez un ID'); return; }
    document.getElementById('dlDocLink').href = `/glpi/documents/${id}/download`;
}
function showUserPic() {
    const id = document.getElementById('picUserId').value;
    if(!id) { alert('Entrez un ID user'); return; }
    document.getElementById('user-pic-result').innerHTML = `<img src="/glpi/users/${id}/picture" alt="Photo" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--color-primary);" onerror="this.parentElement.innerHTML='<p class=\\'text-xs text-secondary\\'>Aucune photo</p>'">`;
}
async function loadMassiveParams() {
    const type = document.getElementById('massiveType').value;
    const key  = document.getElementById('massiveActionKey').value;
    if(!key) { alert('Entrez une clé action'); return; }
    await glpiCall('massive-result', `/glpi/massive-actions/${type}/params/${encodeURIComponent(key)}`);
}
async function glpiPasswordRequest() {
    const email = document.getElementById('resetEmail').value;
    if(!email) return;
    const data = await glpiCall('reset-request-result', '/glpi/password/reset-request', 'POST', {email});
    document.getElementById('reset-request-result').innerHTML = data?.success ? '<p class="text-success text-xs mb-0">✅ Email de reset envoyé via GLPI</p>' : '<p class="text-danger text-xs mb-0">❌ Erreur envoi</p>';
}
async function glpiPasswordConfirm() {
    const body = { email: document.getElementById('resetEmail2').value, token: document.getElementById('resetToken').value, password: document.getElementById('resetPwd').value };
    const data = await glpiCall('reset-confirm-result', '/glpi/password/reset', 'POST', body);
    document.getElementById('reset-confirm-result').innerHTML = data?.success ? '<p class="text-success text-xs mb-0">✅ Mot de passe réinitialisé</p>' : '<p class="text-danger text-xs mb-0">❌ Erreur reset</p>';
}
async function importUsersFromGlpi() {
    const btn = document.getElementById('importUsersBtn');
    const resDiv = document.getElementById('importUsersResult');
    if(btn) { btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Import...'; }
    if(resDiv) resDiv.innerHTML='<div class="text-center py-3"><div class="spinner-border spinner-border-sm" style="color:#2dce89;"></div><p class="text-xs text-secondary mt-2">Import en cours...</p></div>';
    try {
        const response = await fetch('/super-admin/glpi/import-users', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'}, body:JSON.stringify({}) });
        const data = await response.json();
        if(data.success) {
            var statusColors={created:'#2e7d32',exists:'#1565c0',skip:'#757575',error:'#c62828'};
            var statusLabels={created:'Importe',exists:'Deja existant',skip:'Ignore',error:'Erreur'};
            var html='<div class="row mb-3">';
            html+='<div class="col-md-4"><div class="p-3 border-radius-md text-center" style="background:#e8f5e9;"><h5 class="font-weight-bolder mb-0" style="color:#2e7d32;">'+data.created+'</h5><p class="text-xs text-secondary mb-0">Importes</p></div></div>';
            html+='<div class="col-md-4"><div class="p-3 border-radius-md text-center" style="background:#e3f2fd;"><h5 class="font-weight-bolder mb-0" style="color:#1565c0;">'+data.exists+'</h5><p class="text-xs text-secondary mb-0">Deja existants</p></div></div>';
            html+='<div class="col-md-4"><div class="p-3 border-radius-md text-center" style="background:#f5f5f5;"><h5 class="font-weight-bolder mb-0" style="color:#757575;">'+data.skipped+'</h5><p class="text-xs text-secondary mb-0">Ignores</p></div></div></div>';
            if(data.results&&data.results.length){
                html+='<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th class="text-xs text-secondary">Login GLPI</th><th class="text-xs text-secondary">Email</th><th class="text-xs text-secondary">Role</th><th class="text-xs text-secondary">Statut</th><th class="text-xs text-secondary">GLPI ID</th></tr></thead><tbody>';
                data.results.forEach(function(r){
                    var color=statusColors[r.status]||'#757575';
                    var label=statusLabels[r.status]||r.status;
                    html+='<tr><td class="text-xs">'+(r.login||'--')+'</td><td class="text-xs">'+(r.email||'--')+'</td><td class="text-xs">'+(r.role||'--')+'</td><td class="text-xs" style="color:'+color+';">'+label+(r.reason?' ('+r.reason+')':'')+'</td><td class="text-xs font-weight-bold">'+(r.glpi_id||'--')+'</td></tr>';
                });
                html+='</tbody></table></div>';
            }
            if(resDiv) resDiv.innerHTML=html;
        } else {
            if(resDiv) resDiv.innerHTML='<div class="alert py-2 px-3" style="background:#fff3f3;border-left:4px solid #e53935;"><p class="text-xs mb-0" style="color:#c62828;">Erreur: '+(data.error||'Import echoue')+'</p></div>';
        }
    } catch(e) {
        if(resDiv) resDiv.innerHTML='<div class="alert py-2 px-3" style="background:#fff3f3;border-left:4px solid #e53935;"><p class="text-xs mb-0" style="color:#c62828;">Erreur reseau</p></div>';
    } finally {
        if(btn) { btn.disabled=false; btn.innerHTML='<i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">download</i> Importer maintenant'; }
    }
}
async function syncUsersToGlpi() {
    const btn = document.getElementById('syncUsersBtn');
    const resDiv = document.getElementById('syncUsersResult');
    if(btn) { btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Sync...'; }
    if(resDiv) resDiv.innerHTML='<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div><p class="text-xs text-secondary mt-2">Synchronisation en cours...</p></div>';
    try {
        const response = await fetch('/super-admin/glpi/sync-users', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'} });
        const data = await response.json();
        if(data.success){
            let html='<div class="alert mb-3 py-2 px-3" style="background:#e8f5e9;border-left:4px solid #4caf50;border-radius:6px;"><p class="text-xs mb-0" style="color:#2e7d32;"><strong>'+data.synced+' utilisateur(s)</strong> synchronise(s). '+data.failed+' echec(s).</p></div>';
            html+='<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th class="text-xs text-secondary">Email</th><th class="text-xs text-secondary">Statut</th><th class="text-xs text-secondary">GLPI ID</th></tr></thead><tbody>';
            (data.results||[]).forEach(function(r){
                var ok=r.status==='ok';
                html+='<tr><td class="text-xs">'+r.email+'</td><td class="text-xs" style="color:'+(ok?'#2e7d32':'#c62828')+';"> '+(ok?'Synchronise':'Echec')+'</td><td class="text-xs font-weight-bold">'+(r.glpi_id||'--')+'</td></tr>';
            });
            html+='</tbody></table></div>';
            if(resDiv) resDiv.innerHTML=html;
        } else {
            if(resDiv) resDiv.innerHTML='<div class="alert py-2 px-3" style="background:#fff3f3;border-left:4px solid #e53935;"><p class="text-xs mb-0" style="color:#c62828;">Erreur: '+(data.error||'Sync echoue')+'</p></div>';
        }
    } catch(e) {
        if(resDiv) resDiv.innerHTML='<div class="alert py-2 px-3" style="background:#fff3f3;border-left:4px solid #e53935;"><p class="text-xs mb-0" style="color:#c62828;">Erreur reseau</p></div>';
    } finally {
        if(btn) { btn.disabled=false; btn.innerHTML='<i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">sync</i> Synchroniser maintenant'; }
    }
}
</script>

@endsection

@push('page-scripts')
<script>
function updatePreview() {
    var p = document.getElementById('primary_color_picker');
    var s = document.getElementById('secondary_color_picker');
    if(!p || !s) return;
    document.getElementById('primaryPreview').style.background = p.value;
    document.getElementById('secondaryPreview').style.background = s.value;
    document.querySelectorAll('[style*="667eea"],[style*="764ba2"]').forEach(function(el) {
        el.style.background = 'linear-gradient(135deg,' + p.value + ',' + s.value + ')';
    });
}
</script>
<script>
function selectTeamsMethod(method) {
  ['general','category'].forEach(function(m) {
    var lbl = document.getElementById('lbl_' + m);
    var sec = document.getElementById('section_' + m);
    if(lbl) { lbl.style.borderColor = m === method ? '#6264A7' : ''; lbl.style.background = m === method ? '#f0f0ff' : ''; }
    if(sec) sec.style.display = m === method ? 'block' : 'none';
    var radio = document.getElementById('method_' + m);
    if(radio) radio.checked = m === method;
  });
}
document.addEventListener('DOMContentLoaded', function() {
  var checked = document.querySelector('input[name="teams_routing_method"]:checked');
  selectTeamsMethod(checked ? checked.value : 'general');
});
</script>
@endpush