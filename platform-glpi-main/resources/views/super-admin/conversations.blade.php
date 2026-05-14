@extends('layouts.dashboard')
@section('title', 'Conversations')
@section('content')
<style>
*{box-sizing:border-box;}
.c-wrap{display:flex;height:calc(100vh - 140px);border-radius:16px;overflow:hidden;border:1px solid var(--bs-border-color,#e2e8f0);background:var(--bs-body-bg,#fff);}
.c-sidebar{width:320px;min-width:320px;display:flex;flex-direction:column;border-right:1px solid var(--bs-border-color,#e2e8f0);background:var(--bs-body-bg,#fff);}
.c-sidebar-hdr{padding:14px 16px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.c-sidebar-hdr h6{margin:0;font-size:14px;font-weight:700;}
.c-sidebar-hdr p{margin:0;font-size:11px;color:#94a3b8;}
.c-tabs{display:flex;gap:4px;padding:10px 16px 6px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);flex-shrink:0;}
.c-tab{flex:1;text-align:center;padding:5px 0;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;background:transparent;color:#94a3b8;transition:.15s;text-decoration:none;}
.c-tab:hover{background:#f1f5f9;color:#334155;}
.c-tab.active{background:var(--color-primary);color:#fff;}
.c-tab .count{font-size:10px;opacity:.7;margin-left:3px;}
.c-search{padding:8px 16px 12px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);flex-shrink:0;position:relative;}
.c-search input{width:100%;padding:7px 10px 7px 30px;border-radius:8px;border:1.5px solid var(--bs-border-color,#e2e8f0);font-size:12px;outline:none;background:var(--bs-tertiary-bg,#f8fafc);}
.c-search input:focus{border-color:var(--color-primary);}
.c-search .si{position:absolute;left:26px;top:20px;font-size:16px;color:#94a3b8;pointer-events:none;}
.c-list{flex:1;overflow-y:auto;min-height:0;}
.c-list::-webkit-scrollbar{width:4px;}
.c-list::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.c-item{padding:12px 16px;border-bottom:1px solid var(--bs-border-color,#f1f5f9);cursor:pointer;transition:.1s;text-decoration:none;display:block;color:inherit;border-left:3px solid transparent;}
.c-item:hover{background:var(--bs-tertiary-bg,#f8fafc);}
.c-item.selected{background:color-mix(in srgb,var(--color-primary) 8%,transparent);border-left-color:var(--color-primary);}
.c-item-title{font-size:12px;font-weight:600;color:#0f172a;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.c-item-meta{display:flex;align-items:center;justify-content:space-between;gap:6px;}
.c-item-user{font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.c-item-channel{font-size:9px;font-weight:700;padding:1px 6px;border-radius:99px;background:#e0e7ff;color:#4338ca;flex-shrink:0;}
.c-item-date{font-size:10px;color:#94a3b8;flex-shrink:0;}
.c-main{flex:1;display:flex;flex-direction:column;min-width:0;}
.c-main-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;gap:10px;}
.c-main-hdr{padding:14px 20px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:color-mix(in srgb,var(--color-primary) 4%,transparent);}
.c-main-hdr h6{margin:0;font-size:14px;font-weight:700;}
.c-main-hdr p{margin:0;font-size:11px;color:#64748b;}
.c-msgs{flex:1;overflow-y:auto;padding:16px 20px;min-height:0;display:flex;flex-direction:column;}
.c-msgs::-webkit-scrollbar{width:4px;}
.c-msgs::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.c-msg{margin-bottom:14px;padding:12px 16px;border-radius:12px;max-width:80%;}
.c-msg.client{background:#f1f5f9;border-bottom-left-radius:4px;align-self:flex-start;}
.c-msg.agent{background:color-mix(in srgb,var(--color-primary) 8%,transparent);border-bottom-right-radius:4px;align-self:flex-end;margin-left:auto;}
.c-msg.internal{background:#eef2ff;border-left:3px solid #6366f1;max-width:100%;width:100%;}
.c-msg-from{font-size:11px;font-weight:700;margin-bottom:3px;}
.c-msg-from.client{color:var(--color-primary);}
.c-msg-from.agent{color:#6366f1;}
.c-msg-body{font-size:13px;color:#334155;line-height:1.55;white-space:pre-wrap;margin:0;}
.c-msg-time{font-size:10px;color:#94a3b8;margin-top:4px;text-align:right;}
.loading-dots{display:flex;align-items:center;justify-content:center;gap:6px;padding:40px;}
.loading-dots span{width:8px;height:8px;border-radius:50%;background:var(--color-primary);animation:dotPulse .8s infinite alternate;}
.loading-dots span:nth-child(2){animation-delay:.2s;}
.loading-dots span:nth-child(3){animation-delay:.4s;}
@keyframes dotPulse{0%{opacity:.3;transform:scale(.8)}100%{opacity:1;transform:scale(1)}}
</style>

<div class="c-wrap">
  {{-- SIDEBAR --}}
  <aside class="c-sidebar">
    <div class="c-sidebar-hdr">
      <div>
        <h6>Conversations</h6>
        <p>Supervision lecture seule</p>
      </div>
      <a href="{{ route('super-admin.conversations') }}" style="color:var(--color-primary);font-size:20px;text-decoration:none;">↻</a>
    </div>
    <div class="c-tabs">
      <a href="{{ route('super-admin.conversations', ['channel' => '']) }}" class="c-tab {{ !$channel ? 'active' : '' }}">Toutes</a>
      <a href="{{ route('super-admin.conversations', ['channel' => 'chat']) }}" class="c-tab {{ $channel === 'chat' ? 'active' : '' }}">Chat <span class="count">{{ $chatCount }}</span></a>
      <a href="{{ route('super-admin.conversations', ['channel' => 'whatsapp']) }}" class="c-tab {{ $channel === 'whatsapp' ? 'active' : '' }}">WhatsApp <span class="count">{{ $wappCount }}</span></a>
    </div>
    <div class="c-search">
      <span class="si material-symbols-rounded">search</span>
      <input type="text" id="convSearch" placeholder="Rechercher..." oninput="filterConvs(this.value)">
    </div>
    <div class="c-list" id="convList">
      @forelse($convs as $i => $c)
        <div class="c-item" data-id="{{ $c['id'] }}" data-search="{{ strtolower(($c['subject'] ?? '').' '.($c['user_name'] ?? $c['customer_name'] ?? '')) }}" onclick="selectConv(this)">
          <div class="c-item-title">{{ $c['subject'] ?? $c['title'] ?? 'Sans sujet' }}</div>
          <div class="c-item-meta">
            <span class="c-item-user">{{ $c['user_name'] ?? $c['customer_name'] ?? $c['user_id'] ?? '-' }}</span>
            <span class="c-item-channel">{{ $c['channel'] ?? $c['channel_source'] ?? '-' }}</span>
          </div>
          <div class="c-item-date">{{ \Carbon\Carbon::parse($c['created_at'] ?? $c['date'] ?? now())->format('d/m H:i') }}</div>
        </div>
      @empty
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
          <div style="font-size:36px;margin-bottom:8px;">💬</div>
          <p style="font-size:13px;margin:0;">Aucune conversation</p>
        </div>
      @endforelse
    </div>
  </aside>

  {{-- MAIN --}}
  <section class="c-main">
    <div id="mainEmpty" class="c-main-empty">
      <span style="font-size:48px;">💬</span>
      <p style="font-size:14px;font-weight:600;color:#64748b;margin:0;">Sélectionnez une conversation</p>
      <p style="font-size:12px;margin:0;">Choisissez une conversation dans la liste pour voir les messages</p>
    </div>
    <div id="mainMessages" style="display:none;flex-direction:column;height:100%;">
      <div class="c-main-hdr">
        <div>
          <h6 id="msgTitle"></h6>
          <p id="msgMeta"></p>
        </div>
      </div>
      <div class="c-msgs" id="msgContainer"></div>
    </div>
    <div id="mainLoading" style="display:none;" class="c-main-empty">
      <div class="loading-dots"><span></span><span></span><span></span></div>
      <p style="font-size:13px;color:#64748b;">Chargement des messages…</p>
    </div>
  </section>
</div>

<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function filterConvs(q) {
  q = q.toLowerCase().trim();
  document.querySelectorAll('.c-item').forEach(function(el) {
    el.style.display = (!q || el.dataset.search.includes(q)) ? 'block' : 'none';
  });
}

function selectConv(el) {
  document.querySelectorAll('.c-item').forEach(function(e) { e.classList.remove('selected'); });
  el.classList.add('selected');

  var id = el.dataset.id;
  document.getElementById('mainEmpty').style.display   = 'none';
  document.getElementById('mainMessages').style.display = 'none';
  document.getElementById('mainLoading').style.display  = 'flex';

  // Fetch conversation detail
  fetch('/api/v1/conversations/' + encodeURIComponent(id), {
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
  })
  .then(function(r) { if (!r.ok) throw new Error('Erreur'); return r.json(); })
  .then(function(conv) {
    document.getElementById('msgTitle').textContent = conv.subject || conv.title || 'Conversation';
    document.getElementById('msgMeta').textContent = (conv.channel || conv.channel_source || '-')
      + ((conv.status || '').toLowerCase() === 'open' || conv.is_active ? ' · Actif' : '');
  })
  .catch(function() {});

  // Fetch messages
  fetch('/api/v1/conversations/' + encodeURIComponent(id) + '/messages?skip=0&limit=100', {
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
  })
  .then(function(r) { if (!r.ok) throw new Error('Erreur'); return r.json(); })
  .then(function(msgs) {
    var container = document.getElementById('msgContainer');
    container.innerHTML = '';
    if (!msgs || msgs.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:40px;color:#94a3b8;"><p style="font-size:13px;margin:0;">Aucun message</p></div>';
    } else {
      msgs.forEach(function(m) {
        var isInt = m.is_internal;
        var sender = m.sender_name || m.sender_id || (isInt ? 'Agent' : 'Client');
        var div = document.createElement('div');
        div.className = 'c-msg ' + (isInt ? 'internal' : 'client');
        var badge = isInt ? '<span style="font-size:9px;font-weight:600;background:#e0e7ff;color:#4338ca;padding:1px 5px;border-radius:99px;margin-left:4px;">Interne</span>' : '';
        var attach = m.attachment_filename ? '<div style="margin-top:6px;"><span style="font-size:11px;color:#6366f1;">📎 ' + esc(m.attachment_filename) + '</span></div>' : '';
        var time = m.created_at ? new Date(m.created_at).toLocaleString('fr-FR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '';
        div.innerHTML = '<div class="c-msg-from ' + (isInt ? 'agent' : 'client') + '">' + esc(sender) + badge + '</div>'
          + '<p class="c-msg-body">' + esc(m.content || m.body || m.text || '') + '</p>'
          + attach
          + '<div class="c-msg-time">' + time + '</div>';
        container.appendChild(div);
      });
    }
    document.getElementById('mainLoading').style.display  = 'none';
    document.getElementById('mainMessages').style.display  = 'flex';
  })
  .catch(function() {
    document.getElementById('mainLoading').style.display  = 'none';
    document.getElementById('mainEmpty').style.display     = 'flex';
  });
}

function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endsection
