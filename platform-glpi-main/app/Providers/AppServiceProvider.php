<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ✅ Ajouter APP_PORT à APP_URL une seule fois
        $port = env('APP_PORT');
        if ($port && $port != 80 && $port != 443) {
            $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
            if (!preg_match('/:\d+$/', $baseUrl)) {
                \Illuminate\Support\Facades\Config::set('app.url', $baseUrl . ':' . $port);
            }
        }
    }

    public function boot(): void
    {
        // ✅ forceRootUrl depuis config (déjà corrigé dans register)
        url()->forceRootUrl(config('app.url'));

        // ✅ Pagination custom Material Dashboard
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.custom');

        // ✅ Appliquer la locale et les settings à chaque requête
        if ($this->app->runningInConsole()) return;

        try {
            if (Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::getAllAsArray();

                // Locale: session a priorité sur DB
                $locale = session('locale', $settings['locale'] ?? 'fr');
                if (in_array($locale, ['fr', 'en', 'ar'])) {
                    App::setLocale($locale);
                }

                // Timezone
                if (!empty($settings['timezone'])) {
                    Config::set('app.timezone', $settings['timezone']);
                    date_default_timezone_set($settings['timezone']);
                }

                // App name
                if (!empty($settings['app_name'])) {
                    Config::set('app.name', $settings['app_name']);
                }

                // Partager avec toutes les vues
                view()->share('appSettings', $settings);
            }
        } catch (\Exception $e) {
            // Silencieux si DB non disponible (migrations, etc.)
        }
    }
}