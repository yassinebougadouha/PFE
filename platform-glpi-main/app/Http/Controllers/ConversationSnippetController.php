<?php

namespace App\Http\Controllers;

use App\Models\ConversationSnippet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConversationSnippetController extends Controller
{
    public function index(Request $request)
    {
        $query = ConversationSnippet::query();

        if (!$request->boolean('include_inactive', false)) {
            $query->where('is_active', true);
        }

        if ($channel = $request->query('channel')) {
            $query->where('channel', $this->normalizeChannel($channel));
        }

        $snippets = $query->orderByDesc('updated_at')->get();

        return response()->json(['snippets' => $snippets]);
    }

    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);
        $payload['id'] = Str::uuid()->toString();
        $snippet = ConversationSnippet::create($payload);

        return response()->json($snippet, 201);
    }

    public function update(Request $request, string $id)
    {
        $snippet = ConversationSnippet::findOrFail($id);
        $payload = $this->validatePayload($request, false);
        $snippet->update($payload);

        return response()->json($snippet);
    }

    public function destroy(string $id)
    {
        $snippet = ConversationSnippet::findOrFail($id);
        $snippet->update(['is_active' => false]);
        return response()->json(['ok' => true]);
    }

    private function validatePayload(Request $request, bool $required = true): array
    {
        $rules = [
            'title' => $required ? 'required|string|max:120' : 'sometimes|string|max:120',
            'body' => $required ? 'required|string' : 'sometimes|string',
            'description' => 'nullable|string|max:300',
            'shortcut' => 'nullable|string|max:32',
            'channel' => 'nullable|string|in:CHAT,WHATSAPP,EMAIL',
            'is_active' => 'sometimes|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request) {
            if ($request->has('channel') && $request->input('channel')) {
                $channel = strtoupper($request->input('channel'));
                if (!in_array($channel, ['CHAT', 'WHATSAPP', 'EMAIL'], true)) {
                    $validator->errors()->add('channel', 'Channel must be CHAT, WHATSAPP or EMAIL.');
                }
            }
        });

        return $validator->validate() + [
            'channel' => $this->normalizeChannel($request->input('channel')),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function normalizeChannel(?string $channel): string
    {
        $channel = strtoupper(trim((string) $channel));
        if (in_array($channel, ['CHAT', 'WHATSAPP', 'EMAIL'], true)) {
            return $channel;
        }
        return 'CHAT';
    }
}
