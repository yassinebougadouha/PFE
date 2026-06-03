<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppQrProxyController extends Controller
{
    public function show(Request $request)
    {
        $base = (string) config('services.whatsapp.internal_bridge_url');
        if (empty($base)) {
            $base = (string) config('services.whatsapp.qr_bridge_url', 'http://localhost:8602/qr');
        }
        if (!str_contains($base, '/qr') && !str_ends_with($base, '/qr-proxy')) {
            $base = rtrim($base, '/') . '/qr';
        }

        $version = (string) $request->query('_v', '');
        $response = null;

        foreach ($this->qrUrlCandidates($base) as $url) {
            if ($version !== '') {
                $url .= (str_contains($url, '?') ? '&' : '?') . '_v=' . urlencode($version);
            }

            try {
                $candidate = Http::timeout(5)->accept('*/*')->get($url);
                if ($candidate->successful() && $candidate->body() !== '') {
                    $response = $candidate;
                    break;
                }
                $response = $candidate;
            } catch (\Exception $e) {
                // Suppress exception to continue testing candidates
            }
        }

        if (!$response) {
            return response('WhatsApp Bridge QR Code Unavailable', 503)
                ->header('Content-Type', 'text/plain')
                ->header('Cache-Control', 'no-store');
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'image/png'))
            ->header('Cache-Control', 'no-store');
    }

    private function qrUrlCandidates(string $base): array
    {
        $base = strtok($base, '?') ?: $base;
        $base = rtrim($base, '/');

        $candidates = [$base];

        if (str_ends_with($base, '/qr')) {
            $candidates[] = $base . '.png';
        } elseif (str_ends_with($base, '/qr.json')) {
            $candidates[] = substr($base, 0, -5) . 'png';
        } elseif (!str_ends_with($base, '/qr.png')) {
            $candidates[] = $base . '/qr';
            $candidates[] = $base . '/qr.png';
        }

        return array_values(array_unique($candidates));
    }
}
