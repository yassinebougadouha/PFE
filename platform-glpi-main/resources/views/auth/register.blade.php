@extends('layouts.guest-material')

@section('title', 'Créer un compte')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  html, body {
    height: 100% !important;
    overflow: hidden !important;
    background: #0f0c29 !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  body .main-content {
    padding: 0 !important;
    margin: 0 !important;
    min-height: unset !important;
    border-radius: 0 !important;
    background: transparent !important;
  }

  .rp-bg {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, #0f0c29 0%, #1a1a4e 40%, #24243e 70%, #0f2027 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    padding: 16px;
  }

  /* Animated background orbs */
  .rp-bg::before {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, transparent 70%);
    top: -200px; left: -200px;
    animation: orb1 8s ease-in-out infinite alternate;
  }
  .rp-bg::after {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16,185,129,0.2) 0%, transparent 70%);
    bottom: -150px; right: -150px;
    animation: orb2 10s ease-in-out infinite alternate;
  }
  @keyframes orb1 { from { transform: translate(0,0) scale(1); } to { transform: translate(80px,60px) scale(1.2); } }
  @keyframes orb2 { from { transform: translate(0,0) scale(1); } to { transform: translate(-60px,-40px) scale(1.15); } }

  /* Card container */
  .rp-card {
    position: relative;
    z-index: 10;
    display: flex;
    width: min(1100px, 100%);
    height: min(680px, 96vh);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 32px 80px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.06);
  }

  /* LEFT PANEL */
  .rp-left {
    flex: 0 0 340px;
    background: linear-gradient(160deg, #6366f1 0%, #4f46e5 45%, #3730a3 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 40px 36px;
    position: relative;
    overflow: hidden;
  }
  .rp-left::before {
    content: '';
    position: absolute;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    top: -80px; right: -80px;
  }
  .rp-left::after {
    content: '';
    position: absolute;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    bottom: -60px; left: -60px;
  }
  .rp-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 1;
  }
  .rp-logo-icon {
    width: 44px; height: 44px;
    background: rgba(255,255,255,0.2);
    border: 1.5px solid rgba(255,255,255,0.35);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    backdrop-filter: blur(8px);
  }
  .rp-logo-text { font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
  .rp-left-body { position: relative; z-index: 1; }
  .rp-left-title {
    font-size: 30px; font-weight: 800;
    color: #fff; line-height: 1.2;
    letter-spacing: -0.5px;
    margin-bottom: 14px;
  }
  .rp-left-sub {
    font-size: 14px; color: rgba(255,255,255,0.75);
    line-height: 1.65; margin-bottom: 32px;
  }
  .rp-feat {
    display: flex; align-items: center;
    gap: 12px; margin-bottom: 14px;
    color: rgba(255,255,255,0.9); font-size: 13px; font-weight: 500;
  }
  .rp-feat-ic {
    width: 34px; height: 34px;
    background: rgba(255,255,255,0.15);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
  }
  .rp-left-footer {
    position: relative; z-index: 1;
    font-size: 12px; color: rgba(255,255,255,0.45);
  }

  /* RIGHT PANEL - FORM */
  .rp-right {
    flex: 1;
    background: #0f172a;
    overflow-y: auto;
    padding: 36px 44px;
    scrollbar-width: thin;
    scrollbar-color: rgba(99,102,241,0.3) transparent;
  }
  .rp-right::-webkit-scrollbar { width: 4px; }
  .rp-right::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.35); border-radius: 4px; }

  .rp-steps {
    display: flex; gap: 5px;
    margin-bottom: 24px;
  }
  .rp-dot {
    height: 3px; border-radius: 3px;
    background: rgba(255,255,255,0.1); flex: 1;
    transition: background 0.35s;
  }
  .rp-dot.on { background: #6366f1; }

  .rp-form-title {
    font-size: 24px; font-weight: 800;
    color: #f1f5f9; letter-spacing: -0.4px;
    margin-bottom: 4px;
  }
  .rp-form-sub {
    font-size: 13px; color: #64748b;
    margin-bottom: 22px;
  }

  .rp-alert {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 12.5px; color: #fca5a5;
    margin-bottom: 16px;
  }

  /* Section dividers */
  .rp-section {
    font-size: 10.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: #475569;
    margin: 14px 0 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex; align-items: center; gap: 6px;
  }

  /* Grid */
  .rp-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

  /* Fields */
  .rp-field { margin-bottom: 10px; }
  .rp-lbl {
    display: block; font-size: 11.5px; font-weight: 600;
    color: #94a3b8; letter-spacing: 0.02em; margin-bottom: 5px;
  }
  .rp-lbl .req { color: #f87171; margin-left: 2px; }
  .rp-inp {
    width: 100%; height: 42px;
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.09);
    border-radius: 9px;
    padding: 0 13px;
    font-size: 13.5px; color: #e2e8f0;
    outline: none;
    transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
    -webkit-appearance: none;
  }
  .rp-inp::placeholder { color: #334155; }
  .rp-inp:focus {
    border-color: #6366f1;
    background: rgba(99,102,241,0.07);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.18);
  }
  .rp-inp.bad { border-color: #ef4444; background: rgba(239,68,68,0.07); }
  select.rp-inp {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 11px center;
    background-size: 14px;
    padding-right: 34px;
    background-color: rgba(255,255,255,0.05);
    color: #e2e8f0;
  }
  select.rp-inp option { background: #1e293b; color: #e2e8f0; }

  .rp-err { font-size: 11px; color: #f87171; margin-top: 4px; }
  .rp-hint { font-size: 10.5px; color: #475569; margin-top: 3px; }

  /* Gender */
  .rp-gender { display: flex; gap: 7px; }
  .rp-gp {
    flex: 1; height: 42px;
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.09);
    border-radius: 9px;
    font-size: 12.5px; font-weight: 600; color: #64748b;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    transition: all 0.18s;
    position: relative; user-select: none;
  }
  .rp-gp input { position: absolute; opacity: 0; width: 0; height: 0; }
  .rp-gp:hover { border-color: #6366f1; color: #a5b4fc; }
  .rp-gp.on {
    border-color: #6366f1;
    background: rgba(99,102,241,0.15);
    color: #a5b4fc;
    box-shadow: 0 0 0 2px rgba(99,102,241,0.2);
  }

  /* Password */
  .rp-pwd-wrap { position: relative; }
  .rp-pwd-wrap .rp-inp { padding-right: 44px; }
  .rp-eye {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #475569; padding: 0; display: flex;
    transition: color 0.15s;
  }
  .rp-eye:hover { color: #6366f1; }

  /* Strength bar */
  #rp-sbar { height: 3px; border-radius: 3px; margin-top: 6px; transition: width 0.3s, background 0.3s; width: 0; }
  #rp-stxt { font-size: 10.5px; color: #475569; margin-top: 3px; display: none; }

  /* Terms */
  .rp-terms {
    display: flex; align-items: flex-start;
    gap: 9px; margin: 12px 0 16px;
  }
  .rp-terms input[type=checkbox] {
    width: 16px; height: 16px;
    flex-shrink: 0; margin-top: 1px;
    accent-color: #6366f1; cursor: pointer;
  }
  .rp-terms label { font-size: 12px; color: #64748b; line-height: 1.5; cursor: pointer; }
  .rp-terms a { color: #818cf8; font-weight: 600; text-decoration: none; }

  /* Submit */
  .rp-submit {
    width: 100%; height: 46px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    border: none; border-radius: 10px;
    font-size: 14.5px; font-weight: 700;
    color: #fff; cursor: pointer;
    transition: opacity 0.18s, transform 0.12s, box-shadow 0.18s;
    letter-spacing: 0.01em;
    box-shadow: 0 4px 20px rgba(99,102,241,0.35);
  }
  .rp-submit:hover { opacity: 0.93; transform: translateY(-1px); box-shadow: 0 8px 28px rgba(99,102,241,0.45); }
  .rp-submit:active { transform: translateY(0); }

  .rp-login-link {
    text-align: center; margin-top: 14px;
    font-size: 13px; color: #475569;
  }
  .rp-login-link a { color: #818cf8; font-weight: 700; text-decoration: none; }
  .rp-login-link a:hover { color: #a5b4fc; }

  /* Hide left panel on small screens */
  @media(max-width: 800px) {
    .rp-left { display: none; }
    .rp-right { padding: 28px 24px; }
  }
</style>

<div class="rp-bg">
  <div class="rp-card">

    {{-- LEFT --}}
    <div class="rp-left">
      <div class="rp-logo">
        <div class="rp-logo-icon">🎯</div>
        <span class="rp-logo-text">L2T Support</span>
      </div>

      <div class="rp-left-body">
        <h1 class="rp-left-title">Rejoignez<br>notre plateforme.</h1>
        <p class="rp-left-sub">Créez votre compte en quelques secondes et accédez à un support dédié, rapide et sécurisé.</p>
        <div class="rp-feat"><div class="rp-feat-ic">⚡</div><span>Suivi de tickets en temps réel</span></div>
        <div class="rp-feat"><div class="rp-feat-ic">🔐</div><span>Données protégées & chiffrées</span></div>
        <div class="rp-feat"><div class="rp-feat-ic">🚀</div><span>Accès instantané après inscription</span></div>
      </div>

      <div class="rp-left-footer">© 2025 L2T Support — Tous droits réservés</div>
    </div>

    {{-- RIGHT --}}
    <div class="rp-right">
      <div class="rp-steps">
        <div class="rp-dot on" id="dot1"></div>
        <div class="rp-dot" id="dot2"></div>
        <div class="rp-dot" id="dot3"></div>
      </div>

      <h2 class="rp-form-title">Créer un compte</h2>
      <p class="rp-form-sub">C'est gratuit et ne prend que quelques instants.</p>

      @if($errors->any())
        <div class="rp-alert">⚠ Veuillez corriger les erreurs ci-dessous.</div>
      @endif

      <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        {{-- IDENTITÉ --}}
        <div class="rp-section">👤 Identité</div>

        <div class="rp-row">
          <div class="rp-field">
            <label class="rp-lbl">Prénom <span class="req">*</span></label>
            <input type="text" name="first_name"
                   class="rp-inp @error('first_name') bad @enderror"
                   value="{{ old('first_name') }}" placeholder="ex: Ahmed"
                   autocomplete="given-name" autofocus>
            @error('first_name')<p class="rp-err">⚠ {{ $message }}</p>@enderror
          </div>
          <div class="rp-field">
            <label class="rp-lbl">Nom <span class="req">*</span></label>
            <input type="text" name="last_name"
                   class="rp-inp @error('last_name') bad @enderror"
                   value="{{ old('last_name') }}" placeholder="ex: Ben Ali"
                   autocomplete="family-name">
            @error('last_name')<p class="rp-err">⚠ {{ $message }}</p>@enderror
          </div>
        </div>

        <div class="rp-row">
          <div class="rp-field">
            <label class="rp-lbl">Date de naissance <span class="req">*</span></label>
            <input type="date" name="birthday"
                   class="rp-inp @error('birthday') bad @enderror"
                   value="{{ old('birthday') }}"
                   max="{{ date('Y-m-d', strtotime('-13 years')) }}">
            <p class="rp-hint">📅 Minimum 13 ans.</p>
            @error('birthday')<p class="rp-err">⚠ {{ $message }}</p>@enderror
          </div>
          <div class="rp-field">
            <label class="rp-lbl">Genre <span class="req">*</span></label>
            <div class="rp-gender">
              <label class="rp-gp @if(old('gender')=='male') on @endif" id="gp-m">
                <input type="radio" name="gender" value="male" {{ old('gender')=='male' ? 'checked' : '' }}>
                ♂ Homme
              </label>
              <label class="rp-gp @if(old('gender')=='female') on @endif" id="gp-f">
                <input type="radio" name="gender" value="female" {{ old('gender')=='female' ? 'checked' : '' }}>
                ♀ Femme
              </label>
              <label class="rp-gp @if(old('gender')=='other') on @endif" id="gp-o">
                <input type="radio" name="gender" value="other" {{ old('gender')=='other' ? 'checked' : '' }}>
                ⚧ Autre
              </label>
            </div>
            @error('gender')<p class="rp-err">⚠ {{ $message }}</p>@enderror
          </div>
        </div>

        {{-- CONTACT --}}
        <div class="rp-section">📧 Contact</div>

        <div class="rp-row">
          <div class="rp-field">
            <label class="rp-lbl">Adresse e-mail <span class="req">*</span></label>
            <input type="email" name="email"
                   class="rp-inp @error('email') bad @enderror"
                   value="{{ old('email') }}" placeholder="votre@email.com"
                   autocomplete="email">
            @error('email')<p class="rp-err">⚠ {{ $message }}</p>@enderror
          </div>
          <div class="rp-field">
            <label class="rp-lbl">Téléphone <span style="color:#475569;font-weight:400;">(optionnel)</span></label>
            <div style="position:relative;">
              <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:15px;pointer-events:none;">📱</span>
              <input type="text" name="phone"
                     class="rp-inp @error('phone') bad @enderror"
                     value="{{ old('phone') }}" placeholder="ex: 98 123 456"
                     style="padding-left:36px;" autocomplete="tel">
            </div>
            @error('phone')<p class="rp-err">⚠ {{ $message }}</p>@enderror
          </div>
        </div>

        {{-- SÉCURITÉ --}}
        <div class="rp-section">🔐 Sécurité</div>

        <div class="rp-row">
          <div class="rp-field">
            <label class="rp-lbl">Mot de passe <span class="req">*</span></label>
            <div class="rp-pwd-wrap">
              <input type="password" name="password" id="pwd1"
                     class="rp-inp @error('password') bad @enderror"
                     placeholder="Min. 8 caractères" autocomplete="new-password">
              <button type="button" class="rp-eye" onclick="toggleEye('pwd1','eye1')">
                <svg id="eye1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
            <div id="rp-sbar"></div>
            <p id="rp-stxt"></p>
            @error('password')<p class="rp-err">⚠ {{ $message }}</p>@enderror
          </div>
          <div class="rp-field">
            <label class="rp-lbl">Confirmer le mot de passe <span class="req">*</span></label>
            <div class="rp-pwd-wrap">
              <input type="password" name="password_confirmation" id="pwd2"
                     class="rp-inp" placeholder="Répétez le mot de passe"
                     autocomplete="new-password">
              <button type="button" class="rp-eye" onclick="toggleEye('pwd2','eye2')">
                <svg id="eye2" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
            <p id="pwdMatch" style="font-size:11px;margin-top:4px;display:none;"></p>
          </div>
        </div>

        <div class="rp-terms">
          <input type="checkbox" id="terms" required>
          <label for="terms">
            J'accepte les <a href="javascript:;">Conditions d'utilisation</a>
            et la <a href="javascript:;">Politique de confidentialité</a> de L2T Support.
          </label>
        </div>

        <button type="submit" class="rp-submit">Créer mon compte →</button>

        <p class="rp-login-link">Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
      </form>
    </div>

  </div>
</div>

<script>
var svgOpen  = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
var svgClose = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';

function toggleEye(inputId, svgId) {
  var inp = document.getElementById(inputId);
  var svg = document.getElementById(svgId);
  if (inp.type === 'password') {
    inp.type = 'text'; svg.innerHTML = svgOpen; svg.style.color = '#6366f1';
  } else {
    inp.type = 'password'; svg.innerHTML = svgClose; svg.style.color = '';
  }
}

// Gender pills
document.querySelectorAll('.rp-gp').forEach(function(pill) {
  pill.addEventListener('click', function() {
    document.querySelectorAll('.rp-gp').forEach(function(p) { p.classList.remove('on'); });
    this.classList.add('on');
  });
});

// Password strength
var sbar = document.getElementById('rp-sbar');
var stxt = document.getElementById('rp-stxt');
function checkStrength(pwd) {
  var s = 0;
  if (pwd.length >= 8) s++;
  if (pwd.length >= 12) s++;
  if (/[A-Z]/.test(pwd)) s++;
  if (/[0-9]/.test(pwd)) s++;
  if (/[^A-Za-z0-9]/.test(pwd)) s++;
  return s;
}
document.getElementById('pwd1').addEventListener('input', function() {
  var val = this.value;
  if (!val) { sbar.style.width = '0'; stxt.style.display = 'none'; return; }
  var s = checkStrength(val);
  var colors = ['#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
  var labels = ['Très faible','Faible','Moyen','Fort','Très fort'];
  sbar.style.width = (s/5*100)+'%';
  sbar.style.background = colors[Math.min(s-1,4)];
  stxt.textContent = '🔒 ' + labels[Math.min(s-1,4)];
  stxt.style.color = colors[Math.min(s-1,4)];
  stxt.style.display = 'block';
  document.getElementById('dot1').classList.toggle('on', s >= 1);
  document.getElementById('dot2').classList.toggle('on', s >= 3);
  document.getElementById('dot3').classList.toggle('on', s >= 5);
  checkMatch();
});

// Confirm match
var pmEl = document.getElementById('pwdMatch');
function checkMatch() {
  var p1 = document.getElementById('pwd1').value;
  var p2 = document.getElementById('pwd2').value;
  if (!p2) { pmEl.style.display = 'none'; return; }
  pmEl.style.display = 'block';
  if (p1 === p2) {
    pmEl.textContent = '✅ Les mots de passe correspondent';
    pmEl.style.color = '#22c55e';
    document.getElementById('pwd2').style.borderColor = '#22c55e';
  } else {
    pmEl.textContent = '❌ Ne correspondent pas';
    pmEl.style.color = '#ef4444';
    document.getElementById('pwd2').style.borderColor = '#ef4444';
  }
}
document.getElementById('pwd1').addEventListener('input', checkMatch);
document.getElementById('pwd2').addEventListener('input', checkMatch);
</script>
@endsection