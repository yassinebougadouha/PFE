@extends('layouts.dashboard')
@section('title', 'Changer votre mot de passe')
@section('page-title', 'Sécurité')

@section('content')
<div class="row justify-content-center mt-5">
  <div class="col-md-6">

    {{-- Alerte obligatoire --}}
    <div class="alert mb-4" style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:8px;">
      <div class="d-flex align-items-center">
        <i class="material-symbols-rounded me-2" style="color:#f59e0b;font-size:24px;">lock_reset</i>
        <div>
          <strong style="color:#92400e;">Changement de mot de passe obligatoire</strong>
          <p class="mb-0 text-sm" style="color:#78350f;">
            Pour sécuriser votre compte, définissez un nouveau mot de passe
            @if($needsPhone) et vérifiez votre numéro de téléphone @endif
            avant de continuer.
          </p>
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    <div class="card shadow-lg">
      <div class="card-header pb-0 pt-4 px-4"
           style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
        <h5 class="text-white font-weight-bolder mb-1">
          <i class="material-symbols-rounded me-2" style="font-size:20px;vertical-align:middle;">lock</i>
          Définir votre mot de passe
        </h5>
        <p class="text-white opacity-8 text-sm mb-3">
          @if($needsPhone)
           vérifiez votre numéro de téléphone et choisissez un mot de passe sécurisé
           pour protéger votre compte.
            
          @else
            Choisissez un mot de passe sécurisé
          @endif
        </p>
      </div>
      <div class="card-body px-4 pb-4 pt-3">

        <form method="POST" action="{{ route('password.change.update') }}" id="pwdForm">
          @csrf

          {{-- ══ SECTION TÉLÉPHONE (si requis) ═════════════════════════════════ --}}
          @if($needsPhone)
          <div class="mb-4 p-3 border-radius-md" style="background:#f0fdf4;border:1.5px solid #86efac;">
            <p class="text-sm font-weight-bold mb-2" style="color:#166534;">
              <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">phone_iphone</i>
              Vérification du numéro de téléphone
            </p>

            {{-- Phone input + Send OTP button --}}
            <div class="d-flex gap-2 mb-2">
              <div class="flex-grow-1">
                <input type="tel" name="phone" id="phoneInput" maxlength="8" pattern="[0-9]*"
                       class="form-control @error('phone') is-invalid @enderror"
                       placeholder="** *** ***"
                       value="{{ old('phone', auth()->user()->phone) }}"
                       style="height:42px;border-radius:8px;">
                @error('phone')
                  <p class="text-danger text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <button type="button" id="sendSmsBtn" onclick="sendSmsOtp()"
                      class="btn mb-0 text-white flex-shrink-0"
                      style="background:linear-gradient(135deg,#10b981,#059669);height:42px;padding:0 16px;font-size:13px;white-space:nowrap;">
                <i class="material-symbols-rounded me-1" style="font-size:15px;vertical-align:middle;">send</i>
                Envoyer code SMS
              </button>
            </div>

            {{-- SMS code input (hidden until sent) --}}
            <div id="smsCodeWrap" style="display:none;">
              <label class="form-label text-xs font-weight-bold mb-1">
                Code reçu par SMS (6 chiffres)
              </label>
              <div class="d-flex gap-2 align-items-center">
                <input type="text" name="sms_code" id="smsCodeInput"
                       class="form-control @error('sms_code') is-invalid @enderror"
                       placeholder="123456" maxlength="6"
                       style="height:42px;border-radius:8px;max-width:140px;font-size:18px;letter-spacing:4px;font-weight:700;"
                       autocomplete="off">
                <button type="button" id="verifySmsBtn" onclick="verifySmsCode()"
                        class="btn btn-sm mb-0 text-white"
                        style="background:linear-gradient(135deg,#0ea5e9,#0284c7);height:42px;padding:0 14px;">
                  <i class="material-symbols-rounded" style="font-size:15px;vertical-align:middle;">verified</i>
                  Vérifier
                </button>
                <button type="button" onclick="sendSmsOtp()"
                        class="btn btn-sm mb-0 btn-outline-secondary"
                        style="height:42px;">Renvoyer</button>
              </div>
              @error('sms_code')
                <p class="text-danger text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>

            {{-- Verified state --}}
            <div id="phoneVerifiedBadge" style="display:none;">
              <span style="color:#166534;font-size:13px;font-weight:600;">
                <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;color:#22c55e;">check_circle</i>
                Numéro vérifié ✅
              </span>
            </div>

            <div id="smsMsg" class="text-xs mt-2" style="display:none;"></div>
          </div>
          @endif

          {{-- ══ NOUVEAU MOT DE PASSE ══════════════════════════════════════════ --}}
          <div class="mb-3">
            <label class="form-label text-sm font-weight-bold">Nouveau mot de passe</label>
            <div class="position-relative">
              <input type="password" name="password" id="newPwd"
                     class="form-control @error('password') is-invalid @enderror"
                     placeholder="Minimum 8 caractères"
                     style="height:45px;border-radius:8px;padding-right:44px;">
              <button type="button" onclick="togglePwd('newPwd', 'eye1')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                <i class="material-symbols-rounded" id="eye1" style="color:#9ca3af;font-size:20px;">visibility_off</i>
              </button>
            </div>
            @error('password')
              <p class="text-danger text-xs mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div class="mb-4">
            <label class="form-label text-sm font-weight-bold">Confirmer le mot de passe</label>
            <div class="position-relative">
              <input type="password" name="password_confirmation" id="confPwd"
                     class="form-control"
                     placeholder="Répétez votre mot de passe"
                     style="height:45px;border-radius:8px;padding-right:44px;">
              <button type="button" onclick="togglePwd('confPwd', 'eye2')"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                <i class="material-symbols-rounded" id="eye2" style="color:#9ca3af;font-size:20px;">visibility_off</i>
              </button>
            </div>
            <p id="pwdMatch" class="text-xs mt-1" style="display:none;"></p>
          </div>

          <button type="submit" id="submitBtn" class="btn w-100 mb-0 text-white"
                  @if($needsPhone) disabled @endif
                  style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));height:46px;font-size:15px;font-weight:600;border-radius:10px;opacity:{{ $needsPhone ? '.5' : '1' }};">
            <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;">lock_reset</i>
            Mettre à jour mon compte
          </button>

        </form>
      </div>
    </div>
  </div>
</div>

<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// منع كتابة أي شيء غير أرقام في الـ input
document.getElementById('phoneInput').addEventListener('keypress', function(e) {
  if (!/[0-9]/.test(e.key)) {
    e.preventDefault();
  }
});

// منع اللصق بحروف
document.getElementById('phoneInput').addEventListener('paste', function(e) {
  var pasted = (e.clipboardData || window.clipboardData).getData('text');
  if (!/^[0-9]+$/.test(pasted)) {
    e.preventDefault();
  }
});

// تنظيف لو دخل شيء غريب
document.getElementById('phoneInput').addEventListener('input', function() {
  this.value = this.value.replace(/[^0-9]/g, '');
});

var phoneVerified = {{ $needsPhone ? 'false' : 'true' }};

function togglePwd(id, eyeId) {
  var input = document.getElementById(id);
  var eye   = document.getElementById(eyeId);
  if (input.type === 'password') {
    input.type = 'text';
    eye.textContent = 'visibility';
    eye.style.color = 'var(--color-primary)';
  } else {
    input.type = 'password';
    eye.textContent = 'visibility_off';
    eye.style.color = '#9ca3af';
  }
}

// Password match
document.getElementById('confPwd').addEventListener('input', function() {
  var msg = document.getElementById('pwdMatch');
  var pwd1 = document.getElementById('newPwd').value;
  if (!this.value) { msg.style.display = 'none'; return; }
  msg.style.display = 'block';
  if (pwd1 === this.value) {
    msg.textContent = '✅ Les mots de passe correspondent';
    msg.style.color = '#16a34a';
  } else {
    msg.textContent = '❌ Les mots de passe ne correspondent pas';
    msg.style.color = '#dc2626';
  }
});

function showSmsMsg(msg, color) {
  var el = document.getElementById('smsMsg');
  el.textContent = msg;
  el.style.color = color || '#475569';
  el.style.display = 'block';
}

function sendSmsOtp() {
  var phone = document.getElementById('phoneInput').value.trim();
  if (!phone) { showSmsMsg('Entrez votre numéro de téléphone.', '#dc2626'); return; }
if (!/^[0-9]+$/.test(phone)) { showSmsMsg('Le numéro doit contenir uniquement des chiffres.', '#dc2626'); return; }
if (phone.length !== 8) { showSmsMsg('Le numéro doit contenir exactement 8 chiffres.', '#dc2626'); return; }

  var btn = document.getElementById('sendSmsBtn');
  btn.disabled = true;
  btn.textContent = '⏳ Envoi...';

  fetch('/auth/sms-otp/send', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ phone: phone })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('smsCodeWrap').style.display = 'block';
      showSmsMsg('✅ ' + data.message, '#166534');
    } else {
      showSmsMsg('❌ ' + data.message, '#dc2626');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:15px;vertical-align:middle;">refresh</i> Renvoyer';
  })
  .catch(() => {
    showSmsMsg('Erreur réseau.', '#dc2626');
    btn.disabled = false;
    btn.textContent = 'Envoyer code SMS';
  });
}

function verifySmsCode() {
  var code  = document.getElementById('smsCodeInput').value.trim();
  var phone = document.getElementById('phoneInput').value.trim();
  if (!code || code.length !== 6) { showSmsMsg('Entrez le code à 6 chiffres.', '#dc2626'); return; }

  var btn = document.getElementById('verifySmsBtn');
  btn.disabled = true;

  fetch('/auth/sms-otp/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ phone: phone, sms_code: code })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('smsCodeWrap').style.display = 'none';
      document.getElementById('phoneVerifiedBadge').style.display = 'block';
      document.getElementById('phoneInput').readOnly = true;
      showSmsMsg('');
      // Activer le bouton submit
      var submitBtn = document.getElementById('submitBtn');
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      phoneVerified = true;
    } else {
      showSmsMsg('❌ ' + data.message, '#dc2626');
      btn.disabled = false;
    }
  })
  .catch(() => {
    showSmsMsg('Erreur réseau.', '#dc2626');
    btn.disabled = false;
  });
}

// Bloquer submit si téléphone non vérifié
document.getElementById('pwdForm').addEventListener('submit', function(e) {
  @if($needsPhone)
  if (!phoneVerified) {
    e.preventDefault();
    showSmsMsg('⚠️ Vérifiez votre numéro de téléphone avant de continuer.', '#d97706');
    document.getElementById('phoneInput').focus();
    return false;
  }
  @endif
});
</script>
@endsection