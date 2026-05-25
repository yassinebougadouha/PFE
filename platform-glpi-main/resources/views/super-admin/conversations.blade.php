@extends('layouts.dashboard')
@section('title', 'Conversations – Supervision')
@section('content')
<style>
*{box-sizing:border-box;}
.sv-wrap{display:flex;height:calc(100vh - 120px);border-radius:18px;overflow:hidden;border:1px solid rgba(0,0,0,.08);background:var(--bs-body-bg,#fff);box-shadow:0 4px 24px -6px rgba(0,0,0,.08);}

/* PANEL 1 */
.sv-admins{width:240px;min-width:240px;display:flex;flex-direction:column;border-right:1px solid rgba(0,0,0,.06);background:#f8fafc;}
.sv-admins-hdr{padding:14px 12px 8px;flex-shrink:0;}
.sv-admins-hdr h6{margin:0 0 2px;font-size:13px;font-weight:700;color:#0f172a;letter-spacing:-.2px;}
.sv-admins-hdr p{margin:0;font-size:10px;color:#94a3b8;}
.sv-user-tabs{display:flex;gap:4px;padding:0 10px 8px;flex-shrink:0;}
.sv-user-tab{flex:1;display:flex;align-items:center;justify-content:center;gap:4px;padding:5px 6px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;border:none;background:#e8edf3;color:#64748b;transition:.15s;}
.sv-user-tab:hover{background:#dde4ec;color:#334155;}
.sv-user-tab.active{background:var(--color-primary);color:#fff;}
.sv-user-tab .sv-ut-cnt{background:rgba(255,255,255,.25);color:inherit;font-size:9px;padding:1px 5px;border-radius:99px;font-weight:800;}
.sv-user-tab:not(.active) .sv-ut-cnt{background:rgba(0,0,0,.1);}
.sv-admins-search{padding:0 10px 8px;flex-shrink:0;}
.sv-admins-search input{width:100%;height:32px;border-radius:8px;border:1.5px solid #e2e8f0;padding:0 10px 0 28px;font-size:12px;background:#fff;outline:none;color:#1e293b;}
.sv-admins-search input:focus{border-color:var(--color-primary);}
.sv-admins-search-wrap{position:relative;}
.sv-admins-search-wrap i{position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:14px;color:#94a3b8;pointer-events:none;}
.sv-admins-list{flex:1;overflow-y:auto;padding:0 8px 8px;}
.sv-admins-list::-webkit-scrollbar{width:3px;}
.sv-admins-list::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.sv-admin-item{display:flex;align-items:center;gap:10px;padding:9px 8px;border-radius:10px;cursor:pointer;transition:.15s;margin-bottom:2px;}
.sv-admin-item:hover{background:#f1f5f9;}
.sv-admin-item.active{background:color-mix(in srgb,var(--color-primary) 10%,transparent);}
.sv-admin-avatar{width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary,#7c3aed));position:relative;}
.sv-admin-avatar img{width:100%;height:100%;object-fit:cover;border-radius:10px;}
.sv-admin-dot{position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;border-radius:50%;background:#22c55e;border:2px solid #f8fafc;}
.sv-admin-info{min-width:0;flex:1;}
.sv-admin-name{font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sv-admin-status{font-size:10px;color:#22c55e;font-weight:500;}

/* PANEL 2 */
.sv-convs{width:280px;min-width:280px;display:flex;flex-direction:column;border-right:1px solid rgba(0,0,0,.06);background:#fff;}
.sv-convs-hdr{padding:16px 14px 8px;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;}
.sv-convs-hdr h6{margin:0;font-size:13px;font-weight:700;color:#0f172a;}
.sv-convs-refresh{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--color-primary);cursor:pointer;border:1.5px solid var(--color-primary);background:color-mix(in srgb,var(--color-primary) 8%,transparent);transition:.2s;padding:0;flex-shrink:0;}
.sv-convs-refresh:hover{background:var(--color-primary);color:#fff;}
.sv-convs-refresh.spinning i{animation:sv-spin .5s linear;}
@keyframes sv-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.sv-tabs{display:flex;gap:4px;padding:0 10px 10px;flex-shrink:0;}
.sv-tab{flex:1;text-align:center;padding:5px 4px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;border:none;background:#f1f5f9;color:#64748b;transition:.15s;}
.sv-tab:hover{background:#e2e8f0;color:#334155;}
.sv-tab.active{background:var(--color-primary);color:#fff;}
.sv-tab .cnt{font-size:9px;opacity:.8;margin-left:2px;}
.sv-convs-search{padding:0 10px 8px;flex-shrink:0;}
.sv-convs-search input{width:100%;height:32px;border-radius:8px;border:1.5px solid #e2e8f0;padding:0 10px 0 28px;font-size:12px;background:#f8fafc;outline:none;color:#1e293b;}
.sv-convs-search input:focus{border-color:var(--color-primary);background:#fff;}
.sv-convs-search-wrap{position:relative;}
.sv-convs-search-wrap i{position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:14px;color:#94a3b8;pointer-events:none;}
.sv-conv-list{flex:1;overflow-y:auto;}
.sv-conv-list::-webkit-scrollbar{width:3px;}
.sv-conv-list::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.sv-conv-item{padding:11px 14px;border-bottom:1px solid #f8fafc;cursor:pointer;transition:.1s;border-left:3px solid transparent;}
.sv-conv-item:hover{background:#f8fafc;}
.sv-conv-item.active{background:color-mix(in srgb,var(--color-primary) 6%,transparent);border-left-color:var(--color-primary);}
.sv-conv-row1{display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;}
.sv-conv-subject{font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;margin-right:6px;}
.sv-conv-time{font-size:10px;color:#94a3b8;flex-shrink:0;}
.sv-conv-row2{display:flex;align-items:center;gap:6px;}
.sv-conv-user{font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;}
.sv-conv-ch{font-size:9px;font-weight:700;padding:1px 6px;border-radius:99px;flex-shrink:0;}
.sv-conv-ch.chat{background:#e0e7ff;color:#4338ca;}
.sv-conv-ch.whatsapp{background:#dcfce7;color:#166534;}
.sv-conv-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;color:#94a3b8;gap:8px;}

/* PANEL 3 */
.sv-msgs-panel{flex:1;display:flex;flex-direction:column;min-width:0;background:#f8fafc;}
.sv-msgs-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:#94a3b8;background:radial-gradient(ellipse at 60% 40%,rgba(99,102,241,.04) 0%,transparent 70%);}
.sv-msgs-empty-icon{width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary,#7c3aed));display:flex;align-items:center;justify-content:center;box-shadow:0 12px 30px -8px color-mix(in srgb,var(--color-primary) 40%,transparent);}
.sv-msgs-empty-icon i{font-size:36px;color:#fff;}
.sv-msgs-empty h5{margin:0;font-size:15px;font-weight:700;color:#475569;}
.sv-msgs-empty p{margin:0;font-size:12px;text-align:center;max-width:200px;line-height:1.5;}
.sv-ro-badge{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #e2e8f0;border-radius:99px;padding:5px 14px;font-size:11px;font-weight:700;color:#64748b;box-shadow:0 2px 8px rgba(0,0,0,.04);}
.sv-ro-badge i{font-size:14px;color:var(--color-primary);}
.sv-msgs-hdr{padding:12px 18px;border-bottom:1px solid rgba(0,0,0,.06);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;}
.sv-msgs-hdr-left{display:flex;align-items:center;gap:10px;}
.sv-msgs-hdr-avatar{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,#6366f1,#8b5cf6);flex-shrink:0;}
.sv-msgs-hdr-info h6{margin:0;font-size:13px;font-weight:700;color:#0f172a;}
.sv-msgs-hdr-info p{margin:0;font-size:11px;color:#64748b;display:flex;align-items:center;gap:4px;}
.sv-live-dot{width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.sv-msgs-hdr-right{display:flex;align-items:center;gap:8px;}
.sv-hdr-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:8px;font-size:11px;font-weight:700;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;}
.sv-msgs-body{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px;}
.sv-msgs-body::-webkit-scrollbar{width:4px;}
.sv-msgs-body::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.sv-day-sep{display:flex;align-items:center;gap:10px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin:6px 0;}
.sv-day-sep::before,.sv-day-sep::after{content:'';flex:1;height:1px;background:#e2e8f0;}
.sv-bubble-wrap{display:flex;flex-direction:column;max-width:75%;}
.sv-bubble-wrap.client{align-self:flex-start;}
.sv-bubble-wrap.agent{align-self:flex-end;align-items:flex-end;}
.sv-bubble-wrap.internal{align-self:stretch;max-width:100%;}
.sv-bubble-sender{font-size:10px;font-weight:700;margin-bottom:3px;}
.sv-bubble-sender.client{color:var(--color-primary);}
.sv-bubble-sender.agent{color:#6366f1;}
.sv-bubble-sender.internal{color:#f59e0b;}
.sv-bubble{padding:10px 14px;border-radius:14px;font-size:13px;line-height:1.55;color:#334155;white-space:pre-wrap;word-break:break-word;}
.sv-bubble.client{background:#fff;border-bottom-left-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.sv-bubble.agent{background:color-mix(in srgb,var(--color-primary) 12%,transparent);border-bottom-right-radius:4px;}
.sv-bubble.internal{background:#fffbeb;border-left:3px solid #f59e0b;border-radius:0 12px 12px 0;width:100%;}
.sv-bubble-time{font-size:10px;color:#94a3b8;margin-top:3px;display:flex;align-items:center;gap:4px;}
.sv-bubble-wrap.agent .sv-bubble-time{justify-content:flex-end;}
.sv-loading{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;}
.sv-dots{display:flex;gap:6px;}
.sv-dots span{width:8px;height:8px;border-radius:50%;background:var(--color-primary);animation:sv-bounce .6s infinite alternate;}
.sv-dots span:nth-child(2){animation-delay:.15s;}
.sv-dots span:nth-child(3){animation-delay:.3s;}
@keyframes sv-bounce{from{opacity:.3;transform:scale(.7)}to{opacity:1;transform:scale(1)}}
</style>

<div class="sv-wrap">

  {{-- ═══ PANEL 1 : USERS ═══ --}}
  @php
    $allAdmins  = \App\Models\User::where('role','admin')->orderBy('name')->get();
    $allClients = \App\Models\User::where('role','client')->orderBy('name')->get();
  @endphp
  <aside class="sv-admins">
    <div class="sv-admins-hdr">
      <h6>Utilisateurs</h6>
      <p>Sélectionnez un utilisateur</p>
    </div>
    <div class="sv-user-tabs">
      <button class="sv-user-tab active" data-type="admin" onclick="switchUserType(this,'admin')">
        <i class="material-symbols-rounded" style="font-size:13px;">shield_person</i>
        Admins
        <span class="sv-ut-cnt" id="adminCnt">{{ $allAdmins->count() }}</span>
      </button>
      <button class="sv-user-tab" data-type="client" onclick="switchUserType(this,'client')">
        <i class="material-symbols-rounded" style="font-size:13px;">person</i>
        Clients
        <span class="sv-ut-cnt" id="clientCnt">{{ $allClients->count() }}</span>
      </button>
    </div>
    <div class="sv-admins-search">
      <div class="sv-admins-search-wrap">
        <i class="material-symbols-rounded">search</i>
        <input type="text" id="adminSearch" placeholder="Rechercher...">
      </div>
    </div>
    <div class="sv-admins-list" id="adminList">
      @foreach($allAdmins as $admin)
      <div class="sv-admin-item" data-id="{{ $admin->id }}" data-type="admin"
           data-name="{{ $admin->name }}"
           data-search="{{ strtolower($admin->name.' '.$admin->email) }}"
           onclick="selectUser(this)">
        <div class="sv-admin-avatar" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary,#7c3aed));">
          @if($admin->avatar)<img src="{{ asset('storage/'.$admin->avatar) }}" alt="">
          @else{{ strtoupper(substr($admin->name,0,2)) }}@endif
          <span class="sv-admin-dot"></span>
        </div>
        <div class="sv-admin-info">
          <div class="sv-admin-name">{{ $admin->name }}</div>
          <div class="sv-admin-status">● Admin</div>
        </div>
      </div>
      @endforeach

      @foreach($allClients as $client)
      <div class="sv-admin-item" data-id="{{ $client->id }}" data-type="client"
           data-name="{{ $client->name }}"
           data-search="{{ strtolower($client->name.' '.$client->email) }}"
           style="display:none;" onclick="selectUser(this)">
        <div class="sv-admin-avatar" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
          @if($client->avatar)<img src="{{ asset('storage/'.$client->avatar) }}" alt="">
          @else{{ strtoupper(substr($client->name,0,2)) }}@endif
          <span class="sv-admin-dot" style="background:#0ea5e9;"></span>
        </div>
        <div class="sv-admin-info">
          <div class="sv-admin-name">{{ $client->name }}</div>
          <div class="sv-admin-status" style="color:#0ea5e9;">● Client</div>
        </div>
      </div>
      @endforeach
    </div>
  </aside>

  {{-- ═══ PANEL 2 : CONVERSATIONS ═══ --}}
  <section class="sv-convs">
    <div class="sv-convs-hdr">
      <h6 id="convsPanelTitle">Sélectionnez un utilisateur</h6>
      <button class="sv-convs-refresh" id="refreshBtn" onclick="refreshConvs()" title="Rafraîchir">
        <i class="material-symbols-rounded" style="font-size:18px;vertical-align:middle;">refresh</i>
      </button>
    </div>
    <div class="sv-tabs" id="channelTabs">
      {{-- Rempli par renderTabs() --}}
    </div>
    <div class="sv-convs-search">
      <div class="sv-convs-search-wrap">
        <i class="material-symbols-rounded">search</i>
        <input type="text" id="convSearch" placeholder="Rechercher..." oninput="filterConvs(this.value)">
      </div>
    </div>
    <div class="sv-conv-list" id="convList">
      <div class="sv-conv-empty">
        <i class="material-symbols-rounded" style="font-size:32px;opacity:.3;">forum</i>
        <p style="font-size:12px;margin:0;">Sélectionnez un utilisateur</p>
      </div>
    </div>
  </section>

  {{-- ═══ PANEL 3 : MESSAGES ═══ --}}
  <section class="sv-msgs-panel" id="msgsPanel">
    <div id="stateEmpty" class="sv-msgs-empty">
      <div class="sv-msgs-empty-icon"><i class="material-symbols-rounded">visibility</i></div>
      <h5>Mode Supervision</h5>
      <p>Sélectionnez un utilisateur puis une conversation</p>
      <span class="sv-ro-badge"><i class="material-symbols-rounded">lock</i> Lecture seule</span>
    </div>
    <div id="stateLoading" style="display:none;" class="sv-loading">
      <div class="sv-dots"><span></span><span></span><span></span></div>
      <p style="font-size:12px;color:#94a3b8;margin:0;">Chargement des messages…</p>
    </div>
    <div id="stateMessages" style="display:none;flex-direction:column;height:100%;">
      <div class="sv-msgs-hdr">
        <div class="sv-msgs-hdr-left">
          <div class="sv-msgs-hdr-avatar" id="hdrAvatar">–</div>
          <div class="sv-msgs-hdr-info">
            <h6 id="hdrTitle">–</h6>
            <p id="hdrMeta">–</p>
          </div>
        </div>
        <div class="sv-msgs-hdr-right">
          <span class="sv-hdr-chip" id="hdrCh">
            <i class="material-symbols-rounded" style="font-size:14px;">chat</i> Chat
          </span>
          <span class="sv-ro-badge" style="font-size:10px;">
            <i class="material-symbols-rounded" style="font-size:12px;">lock</i> Lecture seule
          </span>
        </div>
      </div>
      <div class="sv-msgs-body" id="msgsBody"></div>
    </div>
  </section>
</div>

<script>
var CSRF           = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var activeChannel  = '';
var activeUserType = 'admin';
var selectedConvId = null;
var selectedUserId = null;

/* ──────────────────────────────────────────
   TABS : admin → Chat + WhatsApp
          client → Chat uniquement
────────────────────────────────────────── */
function renderTabs(type, chatCount, waCount){
  var c = document.getElementById('channelTabs');
  if(type === 'client'){
    activeChannel = 'chat';
    c.innerHTML =
      '<button class="sv-tab active" data-ch="chat" onclick="switchTab(this,\'chat\')">'
      +'Chat <span class="cnt" id="chatCnt">'+(chatCount||0)+'</span></button>';
  } else {
    activeChannel = '';
    c.innerHTML =
      '<button class="sv-tab active" data-ch="" onclick="switchTab(this,\'\')">'
      +'Chat <span class="cnt" id="chatCnt">'+(chatCount||0)+'</span></button>'
      +'<button class="sv-tab" data-ch="whatsapp" onclick="switchTab(this,\'whatsapp\')">'
      +'WhatsApp <span class="cnt" id="waCnt">'+(waCount||0)+'</span></button>';
  }
}

/* ── USER TYPE SWITCH ── */
function switchUserType(btn, type){
  document.querySelectorAll('.sv-user-tab').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
  activeUserType = type;
  document.querySelectorAll('.sv-admin-item').forEach(function(el){
    el.style.display = (el.dataset.type === type) ? 'flex' : 'none';
  });
  document.getElementById('adminSearch').value = '';
  selectedUserId = null; selectedConvId = null;
  document.getElementById('convsPanelTitle').textContent = 'Sélectionnez un utilisateur';
  document.getElementById('convList').innerHTML =
    '<div class="sv-conv-empty"><i class="material-symbols-rounded" style="font-size:32px;opacity:.3;">forum</i><p style="font-size:12px;margin:0;">Sélectionnez un utilisateur</p></div>';
  renderTabs(type, 0, 0);
  setState('empty');
}

document.getElementById('adminSearch').addEventListener('input', function(){
  var q = this.value.toLowerCase().trim();
  document.querySelectorAll('.sv-admin-item[data-type="'+activeUserType+'"]').forEach(function(el){
    el.style.display = (!q || el.dataset.search.includes(q)) ? 'flex' : 'none';
  });
});

/* ── SELECT USER → appel Laravel proxy ── */
function selectUser(el){
  document.querySelectorAll('.sv-admin-item').forEach(function(e){ e.classList.remove('active'); });
  el.classList.add('active');
  selectedUserId = el.dataset.id;
  document.getElementById('convsPanelTitle').textContent = el.dataset.name || 'Utilisateur';
  selectedConvId = null;
  setState('empty');
  renderTabs(activeUserType, 0, 0);
  loadUserConversations(selectedUserId, activeUserType);
}

/* ── LOAD CONVERSATIONS via Laravel (filtre côté serveur) ── */
function loadUserConversations(userId, type){
  var list = document.getElementById('convList');
  list.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:30px;gap:8px;color:#94a3b8;"><div class="sv-dots"><span></span><span></span><span></span></div><p style="font-size:11px;margin:0;">Chargement…</p></div>';

  // ← Route Laravel qui fait le proxy + filtre par user
  var url = '/super-admin/conversations/user/' + encodeURIComponent(userId) + '?type=' + encodeURIComponent(type);

  fetch(url, { headers:{ 'Accept':'application/json','X-CSRF-TOKEN':CSRF } })
  .then(function(r){ return r.ok ? r.json() : {conversations:[]}; })
  .then(function(data){
    var convs = data.conversations || data.data || data.items || [];
    renderConvList(convs);
  })
  .catch(function(){
    list.innerHTML = '<div class="sv-conv-empty"><i class="material-symbols-rounded" style="font-size:32px;opacity:.3;">error_outline</i><p style="font-size:12px;margin:0;">Erreur de chargement</p></div>';
  });
}

/* ── RENDER CONV LIST ── */
function renderConvList(convs){
  var list = document.getElementById('convList');
  if(!convs || !convs.length){
    list.innerHTML = '<div class="sv-conv-empty"><i class="material-symbols-rounded" style="font-size:32px;opacity:.3;">forum</i><p style="font-size:12px;margin:0;">Aucune conversation</p></div>';
    renderTabs(activeUserType, 0, 0);
    return;
  }
  var chat = 0, wa = 0;
  list.innerHTML = convs.map(function(c){
    var ch   = (c.channel || c.channel_source || '').toLowerCase();
    var isWa = ch.includes('whatsapp');

    // Sujet — plusieurs champs possibles
    var subject = c.subject || c.title || c.name || '';
    if(!subject && c.last_message) subject = String(c.last_message).substring(0,60);
    if(!subject) subject = 'Conversation #'+(c.id||c._id||'');

    // Nom client — plusieurs champs possibles
    var user = c.user_name || c.customer_name || c.contact_name || c.username || c.from_name || '';
    if(!user && c.user && typeof c.user==='object') user = c.user.name || c.user.email || '';
    if(!user) user = c.user_email || c.contact_email || c.from_email || '';
    if(!user) user = '—';

    var date = c.created_at || c.date || c.updated_at || '';
    var dateStr = '';
    if(date){ try{ dateStr = new Date(date).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}); }catch(e){} }

    if(isWa) wa++; else chat++;
    return '<div class="sv-conv-item" data-id="'+esc(c.id||c._id||'')+'" data-ch="'+esc(ch)+'" data-search="'+esc((subject+' '+user).toLowerCase())+'" onclick="selectConv(this)">'
      +'<div class="sv-conv-row1"><span class="sv-conv-subject">'+esc(subject)+'</span><span class="sv-conv-time">'+esc(dateStr)+'</span></div>'
      +'<div class="sv-conv-row2"><span class="sv-conv-user">'+esc(user)+'</span>'
      +'<span class="sv-conv-ch '+(isWa?'whatsapp':'chat')+'">'+(isWa?'WhatsApp':'Chat')+'</span>'
      +'</div></div>';
  }).join('');

  renderTabs(activeUserType, chat, wa);
  filterConvs(document.getElementById('convSearch').value);
}

/* ── CHANNEL TABS ── */
function switchTab(btn, ch){
  document.querySelectorAll('#channelTabs .sv-tab').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
  activeChannel = ch;
  filterConvs(document.getElementById('convSearch').value);
}

/* ── FILTER ── */
function filterConvs(q){
  q = (q||'').toLowerCase().trim();
  document.querySelectorAll('.sv-conv-item').forEach(function(el){
    var chMatch;
    if(!activeChannel){ chMatch = true; }
    else if(activeChannel === 'chat'){ chMatch = !el.dataset.ch.includes('whatsapp'); }
    else { chMatch = el.dataset.ch.includes(activeChannel); }
    el.style.display = (chMatch && (!q || el.dataset.search.includes(q))) ? 'block' : 'none';
  });
}

/* ── SELECT CONV ── */
function selectConv(el){
  document.querySelectorAll('.sv-conv-item').forEach(function(e){ e.classList.remove('active'); });
  el.classList.add('active');
  selectedConvId = el.dataset.id;
  setState('loading');
  loadConversation(el.dataset.id);
}

/* ── LOAD MESSAGES ── */
function loadConversation(id){
  var detailP = fetch('/api/v1/conversations/'+encodeURIComponent(id), {
    headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}
  }).then(function(r){ return r.ok?r.json():null; }).catch(function(){ return null; });

  var msgsP = fetch('/api/v1/conversations/'+encodeURIComponent(id)+'/messages?skip=0&limit=200', {
    headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}
  }).then(function(r){ return r.ok?r.json():[]; }).catch(function(){ return []; });

  Promise.all([detailP, msgsP]).then(function(res){
    if(selectedConvId !== id) return;
    renderConv(res[0], res[1]);
    setState('messages');
  }).catch(function(){ setState('empty'); });
}

/* ── RENDER MESSAGES ── */
function renderConv(conv, msgs){
  var title  = (conv&&(conv.subject||conv.title||conv.name))||'Conversation';
  var ch     = (conv&&(conv.channel||conv.channel_source))||'chat';
  var isWa   = ch.toLowerCase().includes('whatsapp');
  var isOpen = conv&&(conv.status==='open'||conv.is_active);

  document.getElementById('hdrAvatar').textContent = title.substring(0,2).toUpperCase();
  document.getElementById('hdrTitle').textContent  = title;
  document.getElementById('hdrMeta').innerHTML = (isOpen?'<span class="sv-live-dot"></span> Actif · ':'')+esc(ch);
  document.getElementById('hdrCh').innerHTML =
    '<i class="material-symbols-rounded" style="font-size:14px;">'+(isWa?'smartphone':'chat')+'</i> '+(isWa?'WhatsApp':'Chat');

  var body = document.getElementById('msgsBody');
  body.innerHTML = '';
  var msgArr = Array.isArray(msgs) ? msgs : (msgs.messages||msgs.items||msgs.data||[]);

  if(!msgArr.length){
    body.innerHTML = '<div style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">Aucun message</div>';
    return;
  }

  var lastDay = '';
  msgArr.forEach(function(m){
    var isInt   = m.is_internal;
    var isAgent = !isInt&&(m.sender_type==='agent'||m.is_agent||m.role==='agent');
    var role    = isInt?'internal':(isAgent?'agent':'client');
    var sender  = m.sender_name||m.sender_id||(isInt?'Agent':isAgent?'Agent':'Client');
    var content = m.content||m.body||m.text||m.message||'';
    var dateObj = (m.created_at||m.timestamp||m.date) ? new Date(m.created_at||m.timestamp||m.date) : null;
    var dayStr  = dateObj ? dateObj.toLocaleDateString('fr-FR',{day:'2-digit',month:'long',year:'numeric'}) : '';
    var timeStr = dateObj ? dateObj.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : '';

    if(dayStr && dayStr !== lastDay){
      lastDay = dayStr;
      var sep = document.createElement('div');
      sep.className = 'sv-day-sep'; sep.textContent = dayStr;
      body.appendChild(sep);
    }
    var wrap = document.createElement('div');
    wrap.className = 'sv-bubble-wrap '+role;
    wrap.innerHTML =
      '<div class="sv-bubble-sender '+role+'">'+esc(sender)
      +(isInt?'<span style="font-size:9px;font-weight:700;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:99px;margin-left:4px;">Interne</span>':'')
      +'</div>'
      +'<div class="sv-bubble '+role+'">'+esc(content)
      +(m.attachment_filename?'<div class="sv-attach"><i class="material-symbols-rounded" style="font-size:14px;">attach_file</i>'+esc(m.attachment_filename)+'</div>':'')
      +'</div>'
      +'<div class="sv-bubble-time">'+timeStr+'</div>';
    body.appendChild(wrap);
  });
  body.scrollTop = body.scrollHeight;
}

/* ── STATE ── */
function setState(s){
  document.getElementById('stateEmpty').style.display    = s==='empty'    ? 'flex':'none';
  document.getElementById('stateLoading').style.display  = s==='loading'  ? 'flex':'none';
  document.getElementById('stateMessages').style.display = s==='messages' ? 'flex':'none';
}

/* ── REFRESH ── */
function refreshConvs(){
  var btn = document.getElementById('refreshBtn');
  btn.classList.add('spinning');
  setTimeout(function(){ btn.classList.remove('spinning'); }, 500);
  if(selectedUserId) loadUserConversations(selectedUserId, activeUserType);
  if(selectedConvId){ setState('loading'); loadConversation(selectedConvId); }
}

function esc(s){
  if(!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('convSearch').addEventListener('input', function(){ filterConvs(this.value); });
renderTabs('admin', 0, 0);
</script>
@endsection