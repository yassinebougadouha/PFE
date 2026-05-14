<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailService
{
    protected Client $client;
    protected Gmail $gmail;

    public function __construct()
    {
        // ✅ Lire credentials depuis DB (settings) ou fallback vers .env
        $clientId     = \App\Models\Setting::get('gmail_client_id')     ?? config('services.gmail.client_id');
        $clientSecret = \App\Models\Setting::get('gmail_client_secret') ?? null;
        $refreshToken = \App\Models\Setting::get('gmail_refresh_token') ?? null;

        if ($clientSecret) {
            try { $clientSecret = decrypt($clientSecret); } catch (\Exception $e) { $clientSecret = null; }
        }
        if ($refreshToken) {
            try { $refreshToken = decrypt($refreshToken); } catch (\Exception $e) { $refreshToken = null; }
        }

        // Fallback vers .env si DB vide
        $clientSecret = $clientSecret ?: config('services.gmail.client_secret');
        $refreshToken = $refreshToken ?: config('services.gmail.refresh_token');

        $this->client = new Client();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setAccessType('offline');
        $this->client->setScopes([Gmail::MAIL_GOOGLE_COM]);

        // Set credentials with refresh token
        $this->client->setAccessToken([
            'access_token'  => 'placeholder',
            'refresh_token' => $refreshToken,
            'expires_in'    => 0,
            'created'       => 0,
        ]);

        // Force refresh to get valid access token
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        $this->gmail = new Gmail($this->client);
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $from     = \App\Models\Setting::get('gmail_from_email') ?: config('mail.from.address');
        $fromName = \App\Models\Setting::get('smtp_from_name')   ?: config('mail.from.name');

        $rawMessage = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n"
            . "To: {$to}\r\n"
            . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($htmlBody));

        $encoded = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $message = new Message();
        $message->setRaw($encoded);

        try {
            $this->gmail->users_messages->send('me', $message);
            return true;
        } catch (\Exception $e) {
            \Log::error('GmailService send error: ' . $e->getMessage());
            return false;
        }
    }

    // ─── Exposer le client Google pour la lecture de la boîte ────────────────
    public function getClient(): \Google\Client
    {
        return $this->client;
    }

    // ─── Lire les emails non lus (pour le Mail Collector) ────────────────────
    public function getUnreadMessages(int $maxResults = 20): array
    {
        $gmail    = $this->gmail;
        $messages = $gmail->users_messages->listUsersMessages('me', [
            'q'          => 'is:unread in:inbox',
            'maxResults' => $maxResults,
        ]);
        return $messages->getMessages() ?? [];
    }

}