<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA - L2T Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 16px; padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .icon { text-align: center; font-size: 52px; margin-bottom: 16px; }
        h2 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 8px; text-align: center; }
        p.subtitle { color: #718096; font-size: 14px; text-align: center; margin-bottom: 30px; line-height: 1.6; }
        .badge { display: inline-block; background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 20px; }
        .badge-wrap { text-align: center; }
        .otp-inputs { display: flex; gap: 10px; justify-content: center; margin-bottom: 24px; }
        .otp-inputs input { width: 52px; height: 60px; border: 2px solid #e2e8f0; border-radius: 10px; text-align: center; font-size: 24px; font-weight: 700; color: #1a1a2e; transition: all 0.2s; outline: none; }
        .otp-inputs input:focus { border-color: #0f3460; box-shadow: 0 0 0 3px rgba(15,52,96,0.15); }
        .btn { width: 100%; background: linear-gradient(135deg,#1a1a2e,#0f3460); color: #fff; border: none; padding: 14px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; margin-bottom: 16px; }
        .btn:hover { opacity: 0.9; }
        .resend { text-align: center; font-size: 14px; color: #718096; }
        .resend a { color: #0f3460; text-decoration: none; font-weight: 600; }
        .alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">🔐</div>
    <div class="badge-wrap"><span class="badge">Super Admin — 2FA</span></div>
    <h2>Vérification de sécurité</h2>
    <p class="subtitle">Un code à 6 chiffres a été envoyé à votre email. Entrez-le pour accéder au panneau Super Admin.</p>

    @if(session('success'))
        <div class="alert-success">✅ {{ session('success') }}</div>
    @endif
    @if($errors->has('code'))
        <div class="alert-error">❌ {{ $errors->first('code') }}</div>
    @endif

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf
        <input type="hidden" name="code" id="hiddenCode">
        <div class="otp-inputs">
            @for($i = 0; $i < 6; $i++)
                <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
            @endfor
        </div>
        <button type="submit" class="btn">Confirmer l'accès →</button>
    </form>

    <div class="resend">
        Pas reçu le code ? <a href="{{ route('2fa.resend') }}">Renvoyer</a>
    </div>
</div>

<script>
const digits = document.querySelectorAll('.otp-digit');
const hiddenCode = document.getElementById('hiddenCode');
digits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < 5) digits[idx + 1].focus();
        updateHidden();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) digits[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        paste.split('').forEach((c, i) => { if (digits[i]) digits[i].value = c; });
        updateHidden();
    });
});
function updateHidden() {
    hiddenCode.value = Array.from(digits).map(d => d.value).join('');
}
</script>
</body>
</html>