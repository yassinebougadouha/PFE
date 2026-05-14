<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Vérification OTP - L2T Support</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--color-primary, #667eea) 0%, var(--color-secondary, #764ba2) 100%);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .card { background: #fff; border-radius: 20px; padding: 40px; width: 100%; max-width: 460px; box-shadow: 0 25px 60px rgba(0,0,0,0.18); }
    .logo { text-align: center; margin-bottom: 28px; }
    .logo h1 { font-size: 26px; font-weight: 700; background: linear-gradient(135deg,var(--color-primary,#667eea),var(--color-secondary,#764ba2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    h2 { font-size: 20px; font-weight: 700; color: #1a202c; margin-bottom: 6px; text-align: center; }
    p.subtitle { color: #718096; font-size: 13px; text-align: center; margin-bottom: 28px; line-height: 1.6; }

    /* OTP inputs */
    .otp-block { margin-bottom: 24px; }
    .otp-label {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .05em;
      margin-bottom: 12px;
    }
    .otp-label .badge-channel {
      padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700;
    }
    .badge-email { background: #EEF2FF; color: #4338CA; }
    .badge-sms   { background: #ECFDF5; color: #065F46; }
    .sms-block   { display: none; }

    .otp-inputs { display: flex; gap: 8px; justify-content: center; }
    .otp-inputs input {
      width: 50px; height: 58px; border: 2px solid #e2e8f0; border-radius: 12px;
      text-align: center; font-size: 22px; font-weight: 700; color: #1a202c;
      transition: all .2s; outline: none;
    }
    .otp-inputs input:focus { border-color: var(--color-primary, #667eea); box-shadow: 0 0 0 3px rgba(102,126,234,.15); }
    .otp-inputs input.filled { border-color: #10b981; background: #f0fdf4; }
    input[name="email_code"] { display: none; }

    /* Divider */
    .divider { border: none; border-top: 1px solid #f1f5f9; margin: 20px 0; }

    .btn-submit {
      width: 100%; background: linear-gradient(135deg,var(--color-primary,#667eea),var(--color-secondary,#764ba2));
      color: #fff; border: none; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 600;
      cursor: pointer; margin-bottom: 16px; transition: opacity .2s;
    }
    .btn-submit:hover { opacity: .9; }
    .btn-submit:disabled { opacity: .5; cursor: not-allowed; }

    .resend { text-align: center; font-size: 13px; color: #718096; }
    .resend a { color: var(--color-primary,#667eea); text-decoration: none; font-weight: 600; }

    .alert-success { background: #d1fae5; border:1px solid #6ee7b7; color: #065f46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; }
    .alert-error   { background: #fee2e2; border:1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; }

    /* Progress */
    .progress-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 24px; }
    .dot { width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0; }
    .dot.active { background: var(--color-primary, #667eea); }
    .dot.done { background: #10b981; }


  </style>
</head>
<body>
<div class="card">

  <div class="logo">
    <h1>L2T</h1>
    <p style="font-size:12px;color:#94a3b8;margin-top:2px;">Support Platform</p>
  </div>

  <div class="progress-dots">
    <div class="dot done"></div>
    <div class="dot active"></div>
    <div class="dot"></div>
  </div>

  <h2>Vérifiez votre identité</h2>
  <p class="subtitle">
    Entrez les codes de vérification envoyés à votre email
    @if(session('otp_register') && !empty(session('otp_register')['phone']))
      et par SMS
    @endif
  </p>

  @if(session('success'))
    <div class="alert-success">✅ {{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert-error">
      @foreach($errors->all() as $error) • {{ $error }}<br> @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
    @csrf

    {{-- ── OTP EMAIL ──────────────────────────────────────────────── --}}
    <div class="otp-block">
      <div class="otp-label">
        <span>📧 Code email</span>
        <span class="badge-channel badge-email">Gmail</span>
      </div>
      <div class="otp-inputs" id="emailOtpInputs">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-target="email_code" data-index="0" autofocus>
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-target="email_code" data-index="1">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-target="email_code" data-index="2">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-target="email_code" data-index="3">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-target="email_code" data-index="4">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-target="email_code" data-index="5">
      </div>
      <input type="hidden" name="email_code" id="email_code_hidden">
    </div>


    @if(session('otp_register') && !empty(session('otp_register')['phone']))
    {{-- ── OTP SMS ────────────────────────────────────────────────── --}}
    <div class="otp-block" style="margin-top:20px;">
      <div class="otp-label">
        <span>📱 Code SMS</span>
        <span class="badge-channel badge-sms">TunSMS</span>
      </div>
      <div class="otp-inputs" id="smsOtpInputs">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit sms-digit" data-target="sms_code" data-index="0">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit sms-digit" data-target="sms_code" data-index="1">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit sms-digit" data-target="sms_code" data-index="2">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit sms-digit" data-target="sms_code" data-index="3">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit sms-digit" data-target="sms_code" data-index="4">
        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit sms-digit" data-target="sms_code" data-index="5">
      </div>
      <input type="hidden" name="sms_code" id="sms_code_hidden">
      @error('sms_code')
        <p style="color:#dc2626;font-size:12px;margin-top:6px;">{{ $message }}</p>
      @enderror
    </div>
    @endif

    <hr class="divider">

    <button type="submit" class="btn-submit" id="submitBtn">
      ✅ Vérifier et créer mon compte
    </button>

    <p class="resend">
      Vous n'avez pas reçu les codes ?
      <a href="{{ route('otp.resend') }}">Renvoyer</a>
    </p>
  </form>

</div>

<script>
var emailDigits = new Array(6).fill('');
var smsDigits   = new Array(6).fill('');

document.querySelectorAll('.otp-digit').forEach(function(input) {
  input.addEventListener('input', function() {
    var val = this.value.replace(/\D/g, '');
    this.value = val.slice(-1);
    var target = this.dataset.target;
    var idx    = parseInt(this.dataset.index);

    if (target === 'email_code') emailDigits[idx] = this.value;
    if (target === 'sms_code')   smsDigits[idx]   = this.value;

    if (this.value) this.classList.add('filled');
    else            this.classList.remove('filled');

    // Sync hidden input
    if (target === 'email_code') {
      document.getElementById('email_code_hidden').value = emailDigits.join('');
    } else if (target === 'sms_code') {
      document.getElementById('sms_code_hidden').value = smsDigits.join('');
    }

    // Auto-focus next
    if (this.value) {
      var inputs = this.closest('.otp-inputs').querySelectorAll('.otp-digit');
      if (idx < 5) inputs[idx + 1].focus();
    }
  });

  input.addEventListener('keydown', function(e) {
    if (e.key === 'Backspace' && !this.value) {
      var inputs = this.closest('.otp-inputs').querySelectorAll('.otp-digit');
      var idx = parseInt(this.dataset.index);
      if (idx > 0) inputs[idx - 1].focus();
    }
    if (e.key === 'ArrowLeft') {
      var inputs = this.closest('.otp-inputs').querySelectorAll('.otp-digit');
      var idx = parseInt(this.dataset.index);
      if (idx > 0) inputs[idx - 1].focus();
    }
    if (e.key === 'ArrowRight') {
      var inputs = this.closest('.otp-inputs').querySelectorAll('.otp-digit');
      var idx = parseInt(this.dataset.index);
      if (idx < 5) inputs[idx + 1].focus();
    }
  });

  // Paste support
  input.addEventListener('paste', function(e) {
    e.preventDefault();
    var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
    if (!pasted) return;
    var inputs = this.closest('.otp-inputs').querySelectorAll('.otp-digit');
    var target = this.dataset.target;
    var arr = (target === 'sms_code') ? smsDigits : emailDigits;
    pasted.split('').slice(0, 6).forEach(function(char, i) {
      inputs[i].value = char;
      inputs[i].classList.add('filled');
      arr[i] = char;
    });
    document.getElementById(target + '_hidden').value = arr.join('');
    inputs[Math.min(pasted.length, 5)].focus();
  });
});
</script>
</body>
</html>