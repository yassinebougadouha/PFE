@extends('layouts.dashboard')
@section('title', 'Conversation')
@section('content')
<style>
*{box-sizing:border-box;}
.c-wrap{display:flex;height:calc(100vh - 140px);border-radius:16px;overflow:hidden;border:1px solid var(--bs-border-color,#e2e8f0);background:var(--bs-body-bg,#fff);}
.c-sidebar{width:320px;min-width:320px;display:flex;flex-direction:column;border-right:1px solid var(--bs-border-color,#e2e8f0);background:var(--bs-body-bg,#fff);}
.c-sidebar-hdr{padding:14px 16px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.c-sidebar-hdr h6{margin:0;font-size:14px;font-weight:700;}
.c-sidebar-hdr p{margin:0;font-size:11px;color:#94a3b8;}
.c-list{flex:1;overflow-y:auto;min-height:0;}
.c-list::-webkit-scrollbar{width:4px;}
.c-list::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.c-item{padding:12px 16px;border-bottom:1px solid var(--bs-border-color,#f1f5f9);cursor:pointer;transition:.1s;text-decoration:none;display:block;color:inherit;}
.c-item:hover{background:var(--bs-tertiary-bg,#f8fafc);}
.c-item.selected{background:color-mix(in srgb,var(--color-primary) 8%,transparent);border-left:3px solid var(--color-primary);}
.c-item-title{font-size:12px;font-weight:600;color:#0f172a;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.c-item-meta{display:flex;align-items:center;gap:6px;}
.c-item-user{font-size:11px;color:#64748b;}
.c-item-channel{font-size:9px;font-weight:700;padding:1px 6px;border-radius:99px;background:#e0e7ff;color:#4338ca;}
.c-item-date{font-size:10px;color:#94a3b8;}

.c-main{flex:1;display:flex;flex-direction:column;min-width:0;}
.c-main-hdr{padding:14px 20px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:color-mix(in srgb,var(--color-primary) 4%,transparent);}
.c-main-hdr h6{margin:0;font-size:14px;font-weight:700;}
.c-main-hdr p{margin:0;font-size:11px;color:#64748b;}
.c-msgs{flex:1;overflow-y:auto;padding:16px 20px;min-height:0;display:flex;flex-direction:column;}
.c-msgs::-webkit-scrollbar{width:4px;}
.c-msgs::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.c-msg{margin-bottom:14px;padding:12px 16px;border-radius:12px;max-width:80%;position:relative;}
.c-msg.client{background:#f1f5f9;border-bottom-left-radius:4px;align-self:flex-start;}
.c-msg.agent{background:color-mix(in srgb,var(--color-primary) 8%,transparent);border-bottom-right-radius:4px;align-self:flex-end;margin-left:auto;}
.c-msg.internal{background:#eef2ff;border-left:3px solid #6366f1;max-width:100%;width:100%;}
.c-msg-from{font-size:11px;font-weight:700;margin-bottom:3px;}
.c-msg-from.client{color:var(--color-primary);}
.c-msg-from.agent{color:#6366f1;}
.c-msg-body{font-size:13px;color:#334155;line-height:1.55;white-space:pre-wrap;margin:0;}
.c-msg-time{font-size:10px;color:#94a3b8;margin-top:4px;text-align:right;}
</style>

<div class="c-wrap">
  {{-- SIDEBAR --}}
  <aside class="c-sidebar">
    <div class="c-sidebar-hdr">
      <div>
        <h6>Conversations</h6>
        <p>Lecture seule</p>
      </div>
      <a href="{{ route('super-admin.conversations') }}" style="color:var(--color-primary);font-size:20px;text-decoration:none;">↻</a>
    </div>

    <div class="c-list">
      @forelse(($convs ?? []) as $c)
        <a href="{{ route('super-admin.conversations.detail', $c['id']) }}" class="c-item {{ ($c['id'] ?? '') == $id ? 'selected' : '' }}">
          <div class="c-item-title">{{ $c['subject'] ?? $c['title'] ?? 'Sans sujet' }}</div>
          <div class="c-item-meta">
            <span class="c-item-user">{{ $c['user_name'] ?? $c['customer_name'] ?? $c['user_id'] ?? '-' }}</span>
            <span class="c-item-channel">{{ $c['channel'] ?? $c['channel_source'] ?? '-' }}</span>
          </div>
          <div class="c-item-date">{{ \Carbon\Carbon::parse($c['created_at'] ?? $c['date'] ?? now())->format('d/m/Y H:i') }}</div>
        </a>
      @empty
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
          <div style="font-size:36px;margin-bottom:8px;">💬</div>
          <p style="font-size:13px;margin:0;">Aucune conversation</p>
        </div>
      @endforelse
    </div>
  </aside>

  {{-- MAIN: messages --}}
  <section class="c-main">
    <div class="c-main-hdr">
      <div>
        <h6>{{ $conv['subject'] ?? $conv['title'] ?? 'Conversation' }}</h6>
        <p>
          {{ $conv['channel'] ?? $conv['channel_source'] ?? '-' }}
          @if(($conv['status'] ?? '') === 'open' || ($conv['is_active'] ?? false))
            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#22c55e;margin-left:6px;"></span> Actif
          @endif
        </p>
      </div>
      <a href="{{ route('super-admin.conversations') }}" style="color:var(--color-primary);text-decoration:none;font-size:13px;">← Retour</a>
    </div>

    <div class="c-msgs">
      @forelse($messages as $msg)
        @php
          $isInternal = $msg['is_internal'] ?? false;
          $isClient = !$isInternal;
          $sender = $msg['sender_name'] ?? $msg['sender_id'] ?? ($isInternal ? 'Agent' : 'Client');
        @endphp
        <div class="c-msg {{ $isInternal ? 'internal' : ($isClient ? 'client' : 'agent') }}">
          <div class="c-msg-from {{ $isInternal ? 'agent' : 'client' }}">
            {{ $sender }}
            @if($isInternal)
              <span style="font-size:9px;font-weight:600;background:#e0e7ff;color:#4338ca;padding:1px 5px;border-radius:99px;margin-left:4px;">Interne</span>
            @endif
          </div>
          <p class="c-msg-body">{{ $msg['content'] ?? $msg['body'] ?? $msg['text'] ?? '' }}</p>
          @if(!empty($msg['attachment_filename']))
            <div style="margin-top:6px;">
              <span style="font-size:11px;color:#6366f1;">📎 {{ $msg['attachment_filename'] }}</span>
            </div>
          @endif
          <div class="c-msg-time">{{ \Carbon\Carbon::parse($msg['created_at'] ?? $msg['timestamp'] ?? now())->format('d/m/Y H:i') }}</div>
        </div>
      @empty
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
          <span style="font-size:36px;">📝</span>
          <p style="font-size:13px;margin:0;">Aucun message</p>
        </div>
      @endforelse
    </div>
  </section>
</div>
@endsection
