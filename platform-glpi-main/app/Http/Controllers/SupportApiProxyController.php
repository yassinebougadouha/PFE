<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupportApiProxyController extends Controller
{
    public function __invoke(Request $request, string $path = '')
    {
        $baseUrl = rtrim((string) config('services.support_api.base_url'), '/');
        $prefix = '/' . trim((string) config('services.support_api.prefix', '/api/v1'), '/');
        $timeout = (int) config('services.support_api.timeout', 30);

        $target = $baseUrl . $prefix . ($path ? '/' . ltrim($path, '/') : '');
        if ($request->getQueryString()) {
            $target .= '?' . $request->getQueryString();
        }

        $headers = $this->forwardHeaders($request);
        $configuredToken = config('services.support_api.bearer_token');
        if ($configuredToken && empty($headers['Authorization'])) {
            $headers['Authorization'] = 'Bearer ' . $configuredToken;
        }

        $client = Http::timeout($timeout)->withHeaders($headers);

        if ($request->files->count() > 0) {
            $headers = array_diff_key($headers, ['Content-Type' => true]);
            $client = Http::timeout($timeout)->withHeaders($headers);
            $client = $client->asMultipart();
            foreach ($this->multipartFields($request) as $field) {
                $client = $client->attach($field['name'], $field['contents']);
            }
            foreach ($this->multipartFiles($request->allFiles()) as $file) {
                $client = $client->attach(
                    $file['name'],
                    fopen($file['path'], 'r'),
                    $file['filename'],
                    ['Content-Type' => $file['mime']]
                );
            }
            $response = $client->send($request->method(), $target);
        } else {
            $contentType = (string) $request->header('Content-Type', 'application/json');
            $response = $client
                ->withBody($request->getContent() ?: '', $contentType)
                ->send($request->method(), $target);
        }

        return response($response->body(), $response->status())
            ->withHeaders($this->responseHeaders($response->headers()));
    }

    private function forwardHeaders(Request $request): array
    {
        $headers = [];
        foreach (['Accept', 'Content-Type', 'Authorization'] as $name) {
            $value = $request->header($name);
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        foreach (['X-Request-ID', 'X-Trace-ID'] as $name) {
            $value = $request->header($name);
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    private function responseHeaders(array $headers): array
    {
        $safe = [];
        foreach ($headers as $name => $values) {
            $lower = Str::lower($name);
            if (in_array($lower, ['transfer-encoding', 'connection', 'content-length'], true)) {
                continue;
            }
            if (in_array($lower, ['content-type', 'content-disposition', 'cache-control'], true)) {
                $safe[$name] = is_array($values) ? implode(', ', $values) : (string) $values;
            }
        }
        return $safe;
    }

    private function multipartFields(Request $request): array
    {
        $fields = [];
        foreach ($request->except(array_keys($request->allFiles())) as $name => $value) {
            foreach ((array) $value as $item) {
                $fields[] = ['name' => $name, 'contents' => (string) $item];
            }
        }
        return $fields;
    }

    private function multipartFiles(array $files, string $prefix = ''): array
    {
        $out = [];
        foreach ($files as $name => $file) {
            $field = $prefix ?: (string) $name;
            if (is_array($file)) {
                foreach ($file as $child) {
                    $out = array_merge($out, $this->multipartFiles([(string) $name => $child], $field));
                }
                continue;
            }

            $out[] = [
                'name' => $field,
                'path' => $file->getRealPath(),
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
            ];
        }
        return $out;
    }
}
