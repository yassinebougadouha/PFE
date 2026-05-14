<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppQrProxyController extends Controller
{
    public function show(Request $request)
    {
        $base = (string) config('services.whatsapp.qr_bridge_url', 'http://localhost:3000/qr');
        $url = $this->pngUrl($base);
        if ($request->query('_v')) {
            $url .= (str_contains($url, '?') ? '&' : '?') . '_v=' . urlencode((string) $request->query('_v'));
        }

        $response = Http::timeout(10)->accept('*/*')->get($url);

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'image/png'))
            ->header('Cache-Control', 'no-store');
    }

    private function pngUrl(string $base): string
    {
        $base = strtok($base, '?') ?: $base;
        $base = rtrim($base, '/');

        if (str_ends_with($base, '/qr')) {
            return $base . '.png';
        }

        if (str_ends_with($base, '/qr.json')) {
            return substr($base, 0, -5) . 'png';
        }

        if (str_ends_with($base, '/qr.png')) {
            return $base;
        }

        return $base . '/qr.png';
    }
}
