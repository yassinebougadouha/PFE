<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function conversations()
    {
        return $this->jsonProxy('GET', '/conversations');
    }

    public function messages(string $id)
    {
        return $this->jsonProxy('GET', "/conversations/{$id}/messages");
    }

    public function deleteConversation(string $id)
    {
        return $this->jsonProxy('DELETE', "/conversations/{$id}");
    }

    public function updateConversation(Request $request, string $id)
    {
        $subject = trim((string) $request->input('subject', ''));
        if ($subject === '') {
            return response()->json(['message' => 'Subject is required'], 422);
        }

        $response = $this->apiClient($request->bearerToken())
            ->withBody(json_encode(['subject' => $subject]), 'application/json')
            ->patch($this->apiUrl("/conversations/{$id}"));

        if (!$response->successful()) {
            return response()->json($response->json() ?: ['message' => $response->body()], $response->status());
        }

        return response()->json($response->json(), $response->status());
    }

    public function send(Request $request)
    {
        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json(['message' => 'Message is required'], 422);
        }

        $response = $this->apiClient($request->bearerToken())
            ->withBody(json_encode([
                'content' => $message,
                'conversation_id' => $request->input('conversation_id'),
            ]), 'application/json')
            ->send('POST', $this->apiUrl('/conversations/stream'));

        if (!$response->successful()) {
            return response()->json($response->json() ?: ['message' => $response->body()], $response->status());
        }

        $parsed = $this->parseStream($response->body());

        return response()->json([
            'conversation_id' => $parsed['conversation_id'] ?? $request->input('conversation_id'),
            'response' => $parsed['response'] ?? 'Message recu.',
            'message' => $parsed['response'] ?? 'Message recu.',
        ]);
    }

    private function jsonProxy(string $method, string $path)
    {
        $response = $this->apiClient()->send($method, $this->apiUrl($path));
        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    private function apiClient(?string $requestToken = null)
{
    $headers = ['Accept' => 'application/json'];
    $token = $requestToken 
        ?: session('python_token') 
        ?: config('services.support_api.bearer_token');
    if ($token) {
        $headers['Authorization'] = 'Bearer ' . $token;
    }
    return Http::timeout((int) config('services.support_api.timeout', 30))
        ->withHeaders($headers);
}
    private function apiUrl(string $path): string
    {
        return rtrim((string) config('services.support_api.base_url'), '/')
            . '/'
            . trim((string) config('services.support_api.prefix', '/api/v1'), '/')
            . '/'
            . ltrim($path, '/');
    }

    private function parseStream(string $body): array
    {
        $conversationId = null;
        $reply = null;

        foreach (preg_split("/\r?\n\r?\n/", trim($body)) ?: [] as $chunk) {
            $event = 'message';
            $dataLines = [];
            foreach (preg_split("/\r?\n/", $chunk) ?: [] as $line) {
                if (str_starts_with($line, 'event:')) {
                    $event = trim(substr($line, 6));
                } elseif (str_starts_with($line, 'data:')) {
                    $dataLines[] = ltrim(substr($line, 5));
                }
            }
            if (!$dataLines) {
                continue;
            }
            $data = json_decode(implode("\n", $dataLines), true);
            if (!is_array($data)) {
                continue;
            }
            if ($event === 'meta') {
                $conversationId = $data['conversation']['id'] ?? $conversationId;
            }
            if ($event === 'done') {
                $reply = $data['assistant_message']['content'] ?? $reply;
            }
        }

        return ['conversation_id' => $conversationId, 'response' => $reply];
    }
}
