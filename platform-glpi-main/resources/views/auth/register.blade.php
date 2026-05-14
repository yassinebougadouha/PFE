@extends('layouts.guest-material')

@section('title', 'Créer un compte')

@section('content')
<style>
*,*::before,*::after{box-sizing:border-box}
.reg-page{min-height:100vh;display:flex;align-items:stretch;background:rgba(0, 23, 44, 0.52);font-family:'Segoe UI',system-ui,-apple-system,sans-serif}
.reg-left{flex:1;display:none;flex-direction:column;justify-content:center;padding:60px 64px;background:linear-gradient(145deg, 0%,var(--color-secondary) 100%);position:relative;overflow:hidden}
@media(min-width:992px){.reg-left{display:flex}}
.reg-left::before{content:'';position:absolute;width:500px;height:500px;border-radius:50%;background:rgba(167, 223, 180, 0.52);top:-120px;right:-120px}
.reg-left::after{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(212, 108, 108, 0.71);bottom:-80px;left:-80px}
.reg-brand{display:flex;align-items:center;gap:14px;margin-bottom:56px}
.reg-brand-icon{width:52px;height:52px;background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.3);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;backdrop-filter:blur(6px)}
.reg-brand-name{font-size:22px;font-weight:800;color:#fff;letter-spacing:-.3px}
.reg-hero-title{font-size:38px;font-weight:800;color:#fff;line-height:1.15;letter-spacing:-.8px;margin-bottom:20px}
.reg-hero-sub{font-size:16px;color:rgba(255,255,255,.8);line-height:1.6;margin-bottom:48px;max-width:380px}
.reg-features{display:flex;flex-direction:column;gap:18px}
.reg-feat{display:flex;align-items:center;gap:14px;color:rgba(255,255,255,.9);font-size:14px;font-weight:500}
.reg-feat-icon{width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.reg-right{width:100%;max-width:520px;display:flex;flex-direction:column;justify-content:center;padding:36px 28px;background:#fff;overflow-y:auto}
@media(min-width:992px){.reg-right{padding:48px 52px}}
.reg-steps{display:flex;gap:6px;margin-bottom:22px}
.reg-step-dot{height:4px;border-radius:4px;background:#e4e6ea;flex:1;transition:background .3s}
.reg-step-dot.active{background:var(--color-primary)}
.reg-title{font-size:26px;font-weight:800;color:#1c1e21;letter-spacing:-.4px;margin-bottom:6px}
.reg-subtitle{font-size:15px;color:#65676b;line-height:1.5;margin-bottom:24px}
.reg-divider{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#90949c;margin:4px 0 12px;padding-bottom:6px;border-bottom:1.5px solid #e4e6ea}
.reg-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.reg-field{margin-bottom:12px}
.reg-label{display:block;font-size:12px;font-weight:600;color:#606770;letter-spacing:.02em;margin-bottom:5px}
.reg-label .req{color:#e53935;margin-left:2px}
.reg-input{width:100%;height:44px;border:1.5px solid #dddfe2;border-radius:8px;padding:0 14px;font-size:14px;color:#1c1e21;background:#f8f9fa;transition:border-color .18s,box-shadow .18s,background .18s;outline:none;-webkit-appearance:none}
.reg-input:focus{border-color:var(--color-primary);background:#fff;box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 14%,transparent)}
.reg-input.is-invalid{border-color:#e53935;background:#fff5f5}
.reg-input::placeholder{color:#bec3c9}
select.reg-input{cursor:pointer;padding-right:32px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2390949c' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;background-size:16px;background-color:#f8f9fa}
.reg-pwd-wrap{position:relative}
.reg-pwd-wrap .reg-input{padding-right:44px}
.reg-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#90949c;line-height:1;display:flex;transition:color .15s}
.reg-eye:hover{color:var(--color-primary)}
.reg-error{font-size:12px;color:#e53935;margin-top:4px;display:flex;align-items:center;gap:4px}
.reg-hint{font-size:11px;color:#90949c;margin-top:4px;line-height:1.4}
.gender-group{display:flex;gap:8px}
.gender-pill{flex:1;height:44px;border:1.5px solid #dddfe2;border-radius:8px;background:#f8f9fa;font-size:13px;font-weight:600;color:#65676b;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .18s;position:relative;-webkit-appearance:none;user-select:none}
.gender-pill input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.gender-pill:hover{border-color:var(--color-primary);color:var(--color-primary);background:#fff}
.gender-pill.selected{border-color:var(--color-primary);background:color-mix(in srgb,var(--color-primary) 8%,white);color:var(--color-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 12%,transparent)}
.reg-terms{display:flex;align-items:flex-start;gap:10px;margin:10px 0 18px}
.reg-terms input[type=checkbox]{width:18px;height:18px;flex-shrink:0;margin-top:2px;accent-color:var(--color-primary);cursor:pointer}
.reg-terms label{font-size:13px;color:#65676b;line-height:1.5;cursor:pointer}
.reg-terms a{color:var(--color-primary);font-weight:600;text-decoration:none}
.reg-submit{width:100%;height:48px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border:none;border-radius:8px;font-size:15px;font-weight:700;color:#fff;cursor:pointer;transition:opacity .18s,transform .1s;letter-spacing:.01em}
.reg-submit:hover{opacity:.93;transform:translateY(-1px)}
.reg-submit:active{transform:translateY(0)}
.reg-login{text-align:center;margin-top:16px;font-size:14px;color:#65676b}
.reg-login a{color:var(--color-primary);font-weight:700;text-decoration:none}
#pwdStrengthBar{height:3px;border-radius:4px;margin-top:6px;transition:width .3s,background .3s;width:0}
#pwdMatch{font-size:12px;margin-top:5px;display:none}
</style>

<div class="reg-page">

  {{-- LEFT --}}
  <div class="reg-left" >
    
    <h1 class="reg-hero-title">Rejoignez<br>notre plateforme L2T.</h1>
    <p class="reg-hero-sub">Créez votre compte en quelques secondes et accédez à un support dédié, rapide et sécurisé.</p>
    <div class="reg-features">
      <div class="reg-feat"><div class="reg-feat-icon">⚡</div><span>Suivi de tickets en temps réel</span></div>
     
    </div>
  </div>

  {{-- RIGHT --}}
  <div class="reg-right">
    <div class="reg-steps">
      <div class="reg-step-dot active" id="dot1"></div>
      <div class="reg-step-dot" id="dot2"></div>
      <div class="reg-step-dot" id="dot3"></div>
    </div>

    <h2 class="reg-title">Créer un compte</h2>
    <p class="reg-subtitle">C'est gratuit et ne prend que quelques instants.</p>

    @if($errors->any())
      <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:8px;font-size:13px;">
        <strong>Veuillez corriger les erreurs ci-dessous.</strong>
      </div>
    @endif

    <form method="POST" action="{{ route('register') }}" novalidate>
      @csrf

      {{-- Identité --}}
      <div class="reg-divider">👤 Identité</div>

      <div class="reg-row">
        <div class="reg-field">
          <label class="reg-label">Prénom <span class="req">*</span></label>
          <input type="text" name="first_name"
                 class="reg-input @error('first_name') is-invalid @enderror"
                 value="{{ old('first_name') }}" placeholder="ex: Ahmed"
                 autocomplete="given-name" autofocus>
          @error('first_name')<p class="reg-error">⚠ {{ $message }}</p>@enderror
        </div>
        <div class="reg-field">
          <label class="reg-label">Nom <span class="req">*</span></label>
          <input type="text" name="last_name"
                 class="reg-input @error('last_name') is-invalid @enderror"
                 value="{{ old('last_name') }}" placeholder="ex: Ben Ali"
                 autocomplete="family-name">
          @error('last_name')<p class="reg-error">⚠ {{ $message }}</p>@enderror
        </div>
      </div>

      <div class="reg-field">
        <label class="reg-label">Date de naissance <span class="req">*</span></label>
        <input type="date" name="birthday"
               class="reg-input @error('birthday') is-invalid @enderror"
               value="{{ old('birthday') }}"
               max="{{ date('Y-m-d', strtotime('-13 years')) }}">
        <p class="reg-hint">📅 Vous devez avoir au moins 13 ans.</p>
        @error('birthday')<p class="reg-error">⚠ {{ $message }}</p>@enderror
      </div>

      <div class="reg-field">
        <label class="reg-label">Genre <span class="req">*</span></label>
        <div class="gender-group">
          <label class="gender-pill @if(old('gender')=='male') selected @endif" id="gpill-male">
            <input type="radio" name="gender" value="male" {{ old('gender')=='male' ? 'checked' : '' }}>
            ♂ Homme
          </label>
          <label class="gender-pill @if(old('gender')=='female') selected @endif" id="gpill-female">
            <input type="radio" name="gender" value="female" {{ old('gender')=='female' ? 'checked' : '' }}>
            ♀ Femme
          </label>
          <label class="gender-pill @if(old('gender')=='other') selected @endif" id="gpill-other">
            <input type="radio" name="gender" value="other" {{ old('gender')=='other' ? 'checked' : '' }}>
            ⚧ Autre
          </label>
        </div>
        @error('gender')<p class="reg-error">⚠ {{ $message }}</p>@enderror
      </div>

      {{-- Contact --}}
      <div class="reg-divider" style="margin-top:4px;">📧 Contact</div>

      <div class="reg-field">
        <label class="reg-label">Adresse e-mail <span class="req">*</span></label>
        <input type="email" name="email"
               class="reg-input @error('email') is-invalid @enderror"
               value="{{ old('email') }}" placeholder="votre@email.com"
               autocomplete="email">
        @error('email')<p class="reg-error">⚠ {{ $message }}</p>@enderror
      </div>

      <div class="reg-field">
        <label class="reg-label">Téléphone <span style="color:#90949c;font-weight:400;">(optionnel)</span></label>
        <div style="position:relative;">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:16px;pointer-events:none;">📱</span>
          <input type="text" name="phone"
                 class="reg-input @error('phone') is-invalid @enderror"
                 value="{{ old('phone') }}" placeholder="ex: 98 123 456"
                 style="padding-left:40px;" autocomplete="tel">
        </div>
        @error('phone')<p class="reg-error">⚠ {{ $message }}</p>@enderror
      </div>

      {{-- Sécurité --}}
      <div class="reg-divider" style="margin-top:4px;">🔐 Sécurité</div>

      <div class="reg-field">
        <label class="reg-label">Mot de passe <span class="req">*</span></label>
        <div class="reg-pwd-wrap">
          <input type="password" name="password" id="pwd1"
                 class="reg-input @error('password') is-invalid @enderror"
                 placeholder="Min. 8 caractères" autocomplete="new-password">
          <button type="button" class="reg-eye" onclick="toggleEye('pwd1','eye1')">
            <svg id="eye1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
        <div id="pwdStrengthBar"></div>
        <p id="pwdStrengthText" style="font-size:11px;color:#90949c;margin-top:3px;display:none;"></p>
        @error('password')<p class="reg-error">⚠ {{ $message }}</p>@enderror
      </div>

      <div class="reg-field">
        <label class="reg-label">Confirmer le mot de passe <span class="req">*</span></label>
        <div class="reg-pwd-wrap">
          <input type="password" name="password_confirmation" id="pwd2"
                 class="reg-input" placeholder="Répétez le mot de passe"
                 autocomplete="new-password">
          <button type="button" class="reg-eye" onclick="toggleEye('pwd2','eye2')">
            <svg id="eye2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
        <p id="pwdMatch"></p>
      </div>

      <div class="reg-terms">
        <input type="checkbox" id="terms" required>
        <label for="terms">
          J'accepte les <a href="javascript:;">Conditions d'utilisation</a>
          et la <a href="javascript:;">Politique de confidentialité</a> de L2T Support.
        </label>
      </div>

      <button type="submit" class="reg-submit">Créer mon compte →</button>

      <p class="reg-login">Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
    </form>
  </div>
</div>

<script>
var svgOpen  = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
var svgClose = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';

function toggleEye(inputId, svgId) {
  var input = document.getElementById(inputId);
  var svg   = document.getElementById(svgId);
  if (input.type === 'password') {
    input.type = 'text'; svg.innerHTML = svgOpen; svg.style.color = 'var(--color-primary)';
  } else {
    input.type = 'password'; svg.innerHTML = svgClose; svg.style.color = '';
  }
}

// Gender pills
document.querySelectorAll('.gender-pill').forEach(function(pill) {
  pill.addEventListener('click', function() {
    document.querySelectorAll('.gender-pill').forEach(function(p) { p.classList.remove('selected'); });
    this.classList.add('selected');
  });
});

// Password strength
var strengthBar  = document.getElementById('pwdStrengthBar');
var strengthText = document.getElementById('pwdStrengthText');

function checkStrength(pwd) {
  var score = 0;
  if (pwd.length >= 8)         score++;
  if (pwd.length >= 12)        score++;
  if (/[A-Z]/.test(pwd))      score++;
  if (/[0-9]/.test(pwd))      score++;
  if (/[^A-Za-z0-9]/.test(pwd)) score++;
  return score;
}

document.getElementById('pwd1').addEventListener('input', function() {
  var val = this.value;
  if (!val) { strengthBar.style.width = '0'; strengthText.style.display = 'none'; return; }
  var s = checkStrength(val);
  var colors = ['#e53935','#ff7043','#ffd600','#66bb6a','#2e7d32'];
  var labels = ['Très faible','Faible','Moyen','Fort','Très fort'];
  strengthBar.style.width = (s/5*100)+'%';
  strengthBar.style.background = colors[Math.min(s-1,4)];
  strengthText.textContent = '🔒 ' + labels[Math.min(s-1,4)];
  strengthText.style.color = colors[Math.min(s-1,4)];
  strengthText.style.display = 'block';
  // Progress dots
  document.getElementById('dot1').classList.toggle('active', s >= 1);
  document.getElementById('dot2').classList.toggle('active', s >= 3);
  document.getElementById('dot3').classList.toggle('active', s >= 5);
  checkMatch();
});

// Confirm match
var pwdMatchEl = document.getElementById('pwdMatch');
function checkMatch() {
  var p1 = document.getElementById('pwd1').value;
  var p2 = document.getElementById('pwd2').value;
  if (!p2) { pwdMatchEl.style.display = 'none'; return; }
  pwdMatchEl.style.display = 'block';
  pwdMatchEl.style.fontSize = '12px'; pwdMatchEl.style.marginTop = '5px';
  if (p1 === p2) {
    pwdMatchEl.textContent = '✅ Les mots de passe correspondent';
    pwdMatchEl.style.color = '#2e7d32';
    document.getElementById('pwd2').style.borderColor = '#66bb6a';
  } else {
    pwdMatchEl.textContent = '❌ Ne correspondent pas';
    pwdMatchEl.style.color = '#e53935';
    document.getElementById('pwd2').style.borderColor = '#e53935';
  }
}
document.getElementById('pwd1').addEventListener('input', checkMatch);
document.getElementById('pwd2').addEventListener('input', checkMatch);
</script>
@endsection