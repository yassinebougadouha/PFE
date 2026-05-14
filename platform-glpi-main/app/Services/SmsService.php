<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $apiUrl;
    private string $apiKey;
    private string $sender;

    public function __construct()
    {
        $this->apiUrl  = Setting::get('sms_api_url',  '');
        $this->apiKey  = Setting::get('sms_api_key',  '');
        $this->sender  = Setting::get('sms_sender',   '');
    }

    // ── Envoi principal ───────────────────────────────────────────────────────

    public function send(string $to, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning("SmsService: non configuré — SMS non envoyé à {$to}");
            return false;
        }

        $phone   = $this->normalizePhone($to);
        $message = mb_substr(trim($message), 0, 160);

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withToken($this->apiKey)
                ->post($this->apiUrl, [
                    'type'   => '55',
                    'sender' => $this->sender,
                    'sms'    => [
                        ['mobile' => $phone, 'sms' => $message],
                    ],
                ]);

            $responseBody = trim($response->body());
            $json         = $response->json();
            $statusCode   = $json[0]['status'] ?? ($json['status'] ?? null);
            $success      = $response->successful() || $statusCode == 200;

            if ($success) {
                Log::info("SmsService: SMS envoyé à {$phone} | réponse: {$responseBody}");
                return true;
            }

            Log::warning("SmsService: échec à {$phone} | HTTP {$response->status()} | réponse: {$responseBody}");
            return false;

        } catch (\Exception $e) {
            Log::error("SmsService: exception — {$e->getMessage()}");
            return false;
        }
    }

    // ── Méthodes métier ───────────────────────────────────────────────────────

    public function sendOtp(string $phone, string $code, string $appName = 'L2T'): bool
    {
        $msg = "votre code de verification est {$code}. Valable 10 minutes.";
        return $this->send($phone, $msg);
    }

    public function sendTicketReply(string $phone, int $ticketId, string $status): bool
    {
        $messages = [
            'in_progress' => "votre ticket #{$ticketId} est en cours de traitement. Connectez-vous.",
            'resolved'    => "votre ticket #{$ticketId} est resolu. Connectez-vous pour confirmer la resolution.",
            'closed'      => "votre ticket #{$ticketId} a ete cloture. Merci de nous avoir contactes.",
            'pending'     => "votre ticket #{$ticketId} est en attente. Nous vous recontactons bientot.",
        ];
        $msg = $messages[$status] ?? "votre ticket #{$ticketId} a ete mis a jour. Connectez-vous.";
        return $this->send($phone, $msg);
    }

    public function notifyTicketAnswered(string $phone, int $ticketId, string $title, string $status): bool
    {
        return $this->sendTicketReply($phone, $ticketId, $status);
    }

    public function confirmTicketCreated(string $phone, int $ticketId, string $title): bool
    {
        $msg = "votre ticket a bien ete recu. Notre equipe vous repond bientot.";
        return $this->send($phone, $msg);
    }

    public function testConnection(string $testPhone, string $message = ''): array
    {
        $phone = $this->normalizePhone($testPhone);
        $msg   = !empty($message) ? $message : 'Test valide ';
        $ok    = $this->send($phone, $msg);
        return [
            'success' => $ok,
            'message' => $ok
                ? "SMS test envoye a {$phone}"
                : "Echec envoi — verifiez l'URL et la cle API",
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isConfigured(): bool
    {
        return !empty($this->apiUrl) && !empty($this->apiKey);
    }

public function normalizePhone(string $phone): string
{
    // 1. On garde seulement les chiffres
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // 2. Si commence par 00216 → enlever 00
    if (str_starts_with($phone, '00216')) {
        $phone = substr($phone, 2); // 0021658... → 21658...
    }

    // 3. Si commence par 216 → déjà bon
    if (str_starts_with($phone, '216') && strlen($phone) === 11) {
        return $phone; // 21658712610 ✅
    }

    // 4. Si 8 chiffres locaux → ajouter 216
    if (strlen($phone) === 8) {
        return '216' . $phone; // 58712610 → 21658712610 ✅
    }

    // 5. Si commence par 0 + 8 chiffres (09 chiffres total)
    if (str_starts_with($phone, '0') && strlen($phone) === 9) {
        return '216' . substr($phone, 1); // 058712610 → 21658712610 ✅
    }

    return $phone;
}
  public static function getBestPhone(\App\Models\User $user): ?string
{
    $mobile = trim($user->phone_mobile ?? '');
    if (!empty($mobile)) return $mobile;

    $phone = trim($user->phone ?? '');
    if (!empty($phone)) return $phone;

    return null;
}
}