<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SupportOpsPageController extends Controller
{
    public function show(?string $page = null): View
    {
        $page = $page ?: $this->pageFromRequest();
        $allowed = [
            'conversations',
            'email',
            'whatsapp',
            'supervision',
            'decisions',
            'decision-config',
            'rag',
            'visual-ai',
            'escalations',
            'voice-calls',
            'test-agent',
            'support-call',
            'qr',
            'voice-agents',
            'ai-draft-snippets',
            'notifications',
        ];

        abort_unless(in_array($page, $allowed, true), 404);

        return view('support.' . $page, [
            'reactRole' => $this->reactRole((string) auth()->user()->role),
        ]);
    }

    private function pageFromRequest(): string
    {
        $segments = request()->segments();
        array_shift($segments);
        $path = implode('/', $segments);

        return match ($path) {
            'decisions/config' => 'decision-config',
            'ai-draft-snippets' => 'ai-draft-snippets',
            'support-call' => 'support-call',
            default => str_replace('/', '-', $path ?: 'conversations'),
        };
    }

    private function reactRole(string $laravelRole): string
    {
        return match ($laravelRole) {
            'super_admin' => 'admin',
            'admin' => 'agent',
            default => 'client',
        };
    }
}
