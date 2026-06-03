@extends('layouts.auth')

@section('title', 'Se connecter — L2T Support')

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
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif !important;
  }

  /* Kill layout conflicts */
  body .main-content,
  body .page-header,
  body .container,
  body .row {
    all: unset !important;
    display: block !important;
  }
  body > div.container.position-sticky {
    display: none !important;
  }

  /* ─── Background ─── */
  .lp-bg {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, #0f0c29 0%, #1a1a4e 40%, #24243e 70%, #0f2027 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    overflow: hidden;
  }

  .lp-bg::before {
    content: '';
    position: absolute;
    width: 700px; height: 700px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99,102,241,0.22) 0%, transparent 70%);
    top: -250px; left: -250px;
    animation: lpOrb1 9s ease-in-out infinite alternate;
    pointer-events: none;
  }
  .lp-bg::after {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16,185,129,0.18) 0%, transparent 70%);
    bottom: -180px; right: -180px;
    animation: lpOrb2 11s ease-in-out infinite alternate;
    pointer-events: none;
  }
  @keyframes lpOrb1 { from{transform:translate(0,0) scale(1)} to{transform:translate(90px,70px) scale(1.25)} }
  @keyframes lpOrb2 { from{transform:translate(0,0) scale(1)} to{transform:translate(-70px,-50px) scale(1.2)} }

  /* Floating particles */
  .lp-particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(99,102,241,0.15);
    animation: float linear infinite;
    pointer-events: none;
  }
  @keyframes float {
    0%   { transform: translateY(100vh) scale(0); opacity: 0; }
    10%  { opacity: 1; }
    90%  { opacity: 1; }
    100% { transform: translateY(-100px) scale(1); opacity: 0; }
  }

  /* ─── Main card ─── */
  .lp-card {
    position: relative;
    z-index: 10;
    display: flex;
    width: min(900px, 100%);
    height: min(580px, 94vh);
    border-radius: 26px;
    overflow: hidden;
    box-shadow: 0 40px 100px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.07);
    animation: cardIn 0.5s cubic-bezier(.22,.68,0,1.2) both;
  }
  @keyframes cardIn {
    from { opacity: 0; transform: scale(0.92) translateY(24px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
  }

  /* ─── Left panel ─── */
  .lp-left {
    flex: 0 0 360px;
    background: linear-gradient(160deg, #6366f1 0%, #4f46e5 45%, #3730a3 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 44px 40px;
    position: relative;
    overflow: hidden;
  }
  .lp-left::before {
    content: '';
    position: absolute;
    width: 320px; height: 320px; border-radius: 50%;
    background: rgba(255,255,255,0.09);
    top: -100px; right: -100px;
  }
  .lp-left::after {
    content: '';
    position: absolute;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,0.06);
    bottom: -70px; left: -70px;
  }

  .lp-logo {
    display: flex; align-items: center; gap: 12px;
    position: relative; z-index: 1;
  }
  .lp-logo-ic {
    width: 46px; height: 46px;
    background: rgba(255,255,255,0.2);
    border: 1.5px solid rgba(255,255,255,0.35);
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    backdrop-filter: blur(8px);
  }
  .lp-logo-txt { font-size: 21px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }

  .lp-left-mid { position: relative; z-index: 1; }
  .lp-left-title {
    font-size: 32px; font-weight: 800; color: #fff;
    line-height: 1.2; letter-spacing: -0.5px; margin-bottom: 14px;
  }
  .lp-left-sub {
    font-size: 14px; color: rgba(255,255,255,0.72);
    line-height: 1.65; margin-bottom: 36px;
  }
  .lp-feat {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 14px; color: rgba(255,255,255,0.88);
    font-size: 13px; font-weight: 500;
  }
  .lp-feat-ic {
    width: 34px; height: 34px;
    background: rgba(255,255,255,0.15);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
  }

  .lp-left-foot {
    position: relative; z-index: 1;
    font-size: 11.5px; color: rgba(255,255,255,0.4);
  }

  /* ─── Right panel ─── */
  .lp-right {
    flex: 1;
    background: #0f172a;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 52px 56px;
    position: relative;
    overflow: hidden;
  }

  .lp-right-title {
    font-size: 28px; font-weight: 800;
    color: #f1f5f9; letter-spacing: -0.4px;
    margin-bottom: 6px;
  }
  .lp-right-sub {
    font-size: 13.5px; color: #64748b;
    margin-bottom: 32px; line-height: 1.5;
  }

  /* Alerts */
  .lp-alert-success {
    background: rgba(34,197,94,0.12);
    border: 1px solid rgba(34,197,94,0.3);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 12.5px; color: #86efac;
    margin-bottom: 16px;
  }
  .lp-alert-err {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 12.5px; color: #fca5a5;
    margin-bottom: 16px;
  }

  /* Field */
  .lp-field { margin-bottom: 16px; }
  .lp-lbl {
    display: block; font-size: 12px; font-weight: 600;
    color: #94a3b8; letter-spacing: 0.03em; margin-bottom: 6px;
  }
  .lp-inp {
    width: 100%; height: 46px;
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.09);
    border-radius: 11px;
    padding: 0 14px;
    font-size: 14px; color: #e2e8f0;
    outline: none;
    transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
    -webkit-appearance: none;
    font-family: inherit;
  }
  .lp-inp::placeholder { color: #334155; }
  .lp-inp:focus {
    border-color: #6366f1;
    background: rgba(99,102,241,0.08);
    box-shadow: 0 0 0 3.5px rgba(99,102,241,0.2);
  }
  .lp-inp.bad { border-color: #ef4444; background: rgba(239,68,68,0.07); }

  /* Password wrapper */
  .lp-pwd { position: relative; }
  .lp-pwd .lp-inp { padding-right: 46px; }
  .lp-eye {
    position: absolute; right: 13px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #475569; display: flex; padding: 0;
    transition: color 0.15s;
  }
  .lp-eye:hover { color: #6366f1; }

  /* Remember + forgot */
  .lp-row2 {
    display: flex; align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
  }
  .lp-remember {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #64748b; cursor: pointer;
  }
  .lp-remember input[type=checkbox] {
    width: 16px; height: 16px;
    accent-color: #6366f1; cursor: pointer; flex-shrink: 0;
  }
  .lp-forgot {
    font-size: 12.5px; color: #818cf8; font-weight: 600;
    text-decoration: none;
  }
  .lp-forgot:hover { color: #a5b4fc; }

  /* Submit */
  .lp-submit {
    width: 100%; height: 48px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    border: none; border-radius: 11px;
    font-size: 15px; font-weight: 700; color: #fff;
    cursor: pointer; letter-spacing: 0.01em;
    box-shadow: 0 6px 22px rgba(99,102,241,0.38);
    transition: opacity 0.18s, transform 0.12s, box-shadow 0.18s;
    font-family: inherit;
  }
  .lp-submit:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 10px 30px rgba(99,102,241,0.48); }
  .lp-submit:active { transform: translateY(0); }

  .lp-register {
    text-align: center; margin-top: 18px;
    font-size: 13px; color: #475569;
  }
  .lp-register a { color: #818cf8; font-weight: 700; text-decoration: none; }
  .lp-register a:hover { color: #a5b4fc; }

  /* Hide left on small screens */
  @media(max-width: 760px) {
    .lp-left { display: none; }
    .lp-right { padding: 36px 28px; }
  }
</style>

<div class="lp-bg">
  <!-- Floating particles -->
  <div class="lp-particle" style="width:8px;height:8px;left:15%;animation-duration:12s;animation-delay:0s;"></div>
  <div class="lp-particle" style="width:5px;height:5px;left:35%;animation-duration:15s;animation-delay:3s;"></div>
  <div class="lp-particle" style="width:10px;height:10px;left:60%;animation-duration:10s;animation-delay:1s;"></div>
  <div class="lp-particle" style="width:6px;height:6px;left:80%;animation-duration:13s;animation-delay:5s;"></div>
  <div class="lp-particle" style="width:4px;height:4px;left:50%;animation-duration:17s;animation-delay:7s;"></div>

  <div class="lp-card">

    {{-- LEFT --}}
    <div class="lp-left">
      <div class="lp-logo">
        <div class="lp-logo-ic">🎯</div>
        <span class="lp-logo-txt">L2T Support</span>
      </div>

      <div class="lp-left-mid">
        <h1 class="lp-left-title">Bon retour<br>parmi nous !</h1>
        <p class="lp-left-sub">Connectez-vous pour accéder à votre espace support et suivre vos tickets en temps réel.</p>
        <div class="lp-feat"><div class="lp-feat-ic">⚡</div><span>Suivi de tickets en temps réel</span></div>
        <div class="lp-feat"><div class="lp-feat-ic">🔐</div><span>Connexion sécurisée & chiffrée</span></div>
        <div class="lp-feat"><div class="lp-feat-ic">🚀</div><span>Accès instantané à votre espace</span></div>
      </div>

      <div class="lp-left-foot">© 2025 L2T Support — Tous droits réservés</div>
    </div>

    {{-- RIGHT --}}
    <div class="lp-right">
      <h2 class="lp-right-title">Se connecter</h2>
      <p class="lp-right-sub">Entrez vos identifiants pour accéder à votre compte.</p>

      @if(session('status'))
        <div class="lp-alert-success">✅ {{ session('status') }}</div>
      @endif

      @error('email')
        <div class="lp-alert-err">⚠ {{ $message }}</div>
      @enderror

      <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="lp-field">
          <label class="lp-lbl">Adresse e-mail</label>
          <input type="email" name="email" id="emailInput"
                 class="lp-inp @error('email') bad @enderror"
                 value="{{ old('email') }}"
                 placeholder="votre@email.com"
                 autocomplete="email" autofocus>
        </div>

        <div class="lp-field">
          <label class="lp-lbl">Mot de passe</label>
          <div class="lp-pwd">
            <input type="password" name="password" id="passwordInput"
                   class="lp-inp"
                   placeholder="••••••••"
                   autocomplete="current-password" required>
            <button type="button" class="lp-eye" onclick="togglePassword()">
              <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="19" height="19"
                   viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="lp-row2">
          <label class="lp-remember">
            <input type="checkbox" name="remember" id="rememberMe">
            Se souvenir de moi
          </label>
          @if(Route::has('password.request'))
            <a class="lp-forgot" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
          @endif
        </div>

        <button type="submit" class="lp-submit">Se connecter →</button>

        <p class="lp-register">
          Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a>
        </p>
      </form>
    </div>

  </div>
</div>

<script>
function togglePassword() {
  var inp  = document.getElementById('passwordInput');
  var icon = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    icon.style.color = '#6366f1';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    icon.style.color = '';
  }
}
</script>
@endsection