<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class ApplySettings
{
    public function handle(Request $request, Closure $next)
    {
        if (Schema::hasTable('settings')) {
            $settings = \App\Models\Setting::getAllAsArray();

            // ✅ Locale: session a priorité sur DB
            $locale = session('locale', $settings['locale'] ?? 'fr');
            if (in_array($locale, ['fr', 'en', 'ar'])) {
                App::setLocale($locale);
            }

            // ✅ Timezone
            if (!empty($settings['timezone'])) {
                Config::set('app.timezone', $settings['timezone']);
                date_default_timezone_set($settings['timezone']);
            }

            // ✅ App name
            if (!empty($settings['app_name'])) {
                Config::set('app.name', $settings['app_name']);
            }

            // ✅ Session timeout dynamique
            if (!empty($settings['session_timeout'])) {
                $minutes = (int) $settings['session_timeout'];
                config(['session.lifetime' => $minutes]);
            }

            // ✅ SMTP dynamique depuis DB
            $mailMode = $settings['mail_mode'] ?? 'gmail';
            if ($mailMode === 'smtp' && !empty($settings['smtp_host'])) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp.host',       $settings['smtp_host']);
                Config::set('mail.mailers.smtp.port',       $settings['smtp_port'] ?? 587);
                Config::set('mail.mailers.smtp.encryption', $settings['smtp_encryption'] ?? 'tls');
                Config::set('mail.mailers.smtp.username',   $settings['smtp_username'] ?? '');
                Config::set('mail.from.name',               $settings['smtp_from_name'] ?? config('app.name'));
                Config::set('mail.from.address',            $settings['smtp_from_email'] ?? $settings['smtp_username'] ?? '');
                if (!empty($settings['smtp_password'])) {
                    try {
                        Config::set('mail.mailers.smtp.password', decrypt($settings['smtp_password']));
                    } catch (\Exception $e) {}
                }
            } elseif (!empty($settings['smtp_from_name'])) {
                Config::set('mail.from.name', $settings['smtp_from_name']);
            }

            // ✅ Partage avec toutes les vues
            view()->share('appSettings', $settings);
        }

        return $next($request);
    }
}