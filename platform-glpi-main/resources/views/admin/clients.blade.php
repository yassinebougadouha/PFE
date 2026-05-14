@extends('layouts.dashboard')
@section('title','Clients')
@section('page-title','Clients')

@section('content')

@php
  $countAll    = \App\Models\User::where('role','client')->count();
  $countClient = \App\Models\User::where('role','client')->where('client_type','client')->count();
  $countNew    = \App\Models\User::where('role','client')->where(fn($q) => $q->where('client_type','user')->orWhereNull('client_type'))->count();
  $activeFilter = request('client_type', 'all');
@endphp

<style>
.ctype { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap; }
.ctype-client { background:#EDE9FE;color:#6D28D9;border:1.5px solid #DDD6FE; }
.ctype-new    { background:#FFF7ED;color:#C2410C;border:1.5px solid #FED7AA; }
.ctype-none   { background:#F9FAFB;color:#9CA3AF;border:1.5px solid #E5E7EB; }

.seg-tabs { display:flex;gap:6px;background:#f1f5f9;border-radius:14px;padding:5px; }
.seg-tab { display:inline-flex;align-items:center;gap:7px;padding:8px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:none;background:transparent;color:#64748b;transition:all .18s;white-space:nowrap; }
.seg-tab:hover { background:rgba(255,255,255,.7);color:#1e293b; }
.seg-tab.active { background:#fff;color:var(--color-primary);box-shadow:0 2px 8px rgba(0,0,0,.1); }
.seg-tab .tab-count { background:rgba(0,0,0,.08);border-radius:20px;padding:1px 8px;font-size:11px;font-weight:800; }
.seg-tab.active .tab-count { background:color-mix(in srgb,var(--color-primary) 15%,transparent); }

.cl-search { display:flex;align-items:center;gap:8px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:9px 14px;transition:border-color .2s,box-shadow .2s; }
.cl-search:focus-within { border-color:var(--color-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 12%,transparent); }
.cl-search input { border:none;outline:none;background:transparent;font-size:13px;color:#1a202c;width:100%; }
.cl-search input::placeholder { color:#a0aec0; }

.cl-item { display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s; }
.cl-item:hover { background:#f8faff; }
.cl-item:last-child { border-bottom:none; }
.cl-avatar { width:42px;height:42px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:white;overflow:hidden; }
.cl-info { flex:1;min-width:0; }
.cl-name { font-size:14px;font-weight:600;color:#1e293b;margin-bottom:2px; }
.cl-email { font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.cl-right { text-align:right;flex-shrink:0; }
.cl-tickets { font-size:11px;color:#94a3b8;margin-top:2px; }

.type-option { border:2px solid #e2e8f0;border-radius:12px;padding:14px 16px;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:12px; }
.type-option:hover { border-color:var(--color-primary);background:#fafbff; }
.type-option.selected { border-color:var(--color-primary);background:color-mix(in srgb,var(--color-primary) 6%,transparent); }
.type-option .ti { width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:20px; }
.type-option .tl { font-size:13px;font-weight:700; }
.type-option .td { font-size:11px;color:#64748b;margin-top:2px; }

#drawerOverlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9990;backdrop-filter:blur(3px); }
#drawerOverlay.open { display:block; }
#clientDrawer { position:fixed;top:0;right:0;height:100vh;width:min(480px,100vw);background:#fff;z-index:9995;display:flex;flex-direction:column;transform:translateX(110%);transition:transform .3s cubic-bezier(.4,0,.2,1);box-shadow:-8px 0 40px rgba(0,0,0,0.18);overflow:hidden; }
#clientDrawer.open { transform:translateX(0); }

.drawer-body { flex:1;overflow-y:auto;padding:20px; }
.drawer-section { background:#f8fafc;border-radius:12px;padding:14px;margin-bottom:14px;border:1px solid #e2e8f0; }
.drawer-section-title { font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;display:flex;align-items:center;gap:5px; }
.info-row { display:flex;align-items:flex-start;gap:10px;margin-bottom:8px; }
.info-row:last-child { margin-bottom:0; }
.info-row .ir-icon { font-size:15px;flex-shrink:0;margin-top:2px; }
.info-row .ir-label { font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin:0; }
.info-row .ir-value { font-size:13px;color:#374151;margin:0;word-break:break-all; }

.ticket-card { display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid #e2e8f0;text-decoration:none;transition:all .15s;margin-bottom:6px; }
.ticket-card:hover { background:#f0f4ff;border-color:var(--color-primary); }
.ticket-card:last-child { margin-bottom:0; }
</style>

{{-- HEADER --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background:linear-gradient(135deg,var(--color-primary) 0%,var(--color-secondary) 100%);">
      <div class="d-flex align-items-center">
        <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow">
          <i class="material-symbols-rounded" style="font-size:36px;color:var(--color-primary);">group</i>
        </div>
        <div>
          <h5 class="text-white font-weight-bolder mb-0">Clients</h5>
          <p class="text-white text-sm mb-0 opacity-8">{{ $countAll }} clients enregistrés</p>
        </div>
      </div>
    </div>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
  <i class="material-symbols-rounded me-2">check_circle</i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
  <div class="card-body p-3">

    {{-- TABS + SEARCH --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="seg-tabs">
          <a href="{{ route('admin.clients') }}"
             class="seg-tab {{ $activeFilter === 'all' || !request('client_type') ? 'active' : '' }}">
            <i class="material-symbols-rounded" style="font-size:16px;">group</i>
            Tous <span class="tab-count">{{ $countAll }}</span>
          </a>
          <a href="{{ route('admin.clients') }}?client_type=client"
             class="seg-tab {{ $activeFilter === 'client' ? 'active' : '' }}">
            <i class="material-symbols-rounded" style="font-size:16px;">verified_user</i>
            Client <span class="tab-count">{{ $countClient }}</span>
          </a>
          <a href="{{ route('admin.clients') }}?client_type=user"
             class="seg-tab {{ $activeFilter === 'user' ? 'active' : '' }}">
            <i class="material-symbols-rounded" style="font-size:16px;">new_releases</i>
            Nouveau <span class="tab-count">{{ $countNew }}</span>
          </a>
        </div>
      </div>

      <div class="cl-search" style="min-width:240px;max-width:320px;">
        <i class="material-symbols-rounded" style="color:#a0aec0;font-size:20px;">search</i>
        <input type="text" id="clientSearch"
               placeholder="Nom, email, téléphone..."
               value="{{ request('search') }}"
               autocomplete="off">
        <button id="clearBtn" type="button" onclick="clearSearch()"
                style="background:none;border:none;cursor:pointer;padding:0;display:{{ request('search') ? 'block' : 'none' }}">
          <i class="material-symbols-rounded" style="font-size:18px;color:#a0aec0;">close</i>
        </button>
      </div>
    </div>

    <p class="text-xs text-secondary mb-2 ps-1">
      {{ $clients->total() }} client(s)
      @if($activeFilter === 'client') &middot; <span style="color:#6D28D9;font-weight:700;">🟣 Clients classifiés</span>
      @elseif($activeFilter === 'user') &middot; <span style="color:#C2410C;font-weight:700;">🟠 Non classifiés</span>
      @endif
    </p>

    {{-- LIST --}}
    <div style="border:1px solid #f1f5f9;border-radius:12px;overflow:hidden;">
      @forelse($clients as $client)
      @php
        $ti = $client->getClientTypeInfo();
        $ctypeCss = $client->client_type === 'client' ? 'client' : 'new';
      @endphp
      <div class="cl-item" onclick="window.location.href='{{ route('admin.clients.show', $client->id) }}'" style="cursor:pointer;">
        <div class="cl-avatar">
          @if($client->avatar)
            <img src="{{ asset('storage/'.$client->avatar) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
          @else
            {{ strtoupper(substr($client->name,0,2)) }}
          @endif
        </div>
        <div class="cl-info">
          <div class="cl-name">{{ $client->name }}</div>
          <div class="cl-email">{{ $client->email }}</div>
        </div>
        <div class="cl-right">
          <span class="ctype ctype-{{ $ctypeCss }}">{{ $ti['icon'] }} {{ $ti['label'] }}</span>
          <div class="cl-tickets">{{ $client->tickets_count }} ticket(s)</div>
        </div>
      </div>
      @empty
      <div class="text-center py-5">
        <i class="material-symbols-rounded text-secondary" style="font-size:48px;">group_off</i>
        <p class="text-secondary mt-2 mb-2">Aucun client trouvé</p>
        @if(request('search') || request('client_type'))
          <a href="{{ route('admin.clients') }}" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
        @endif
      </div>
      @endforelse
    </div>

    <div class="pt-3">{{ $clients->appends(request()->query())->links() }}</div>
  </div>
</div>

{{-- CLIENTS DATA --}}
<script>
var CLIENTS_DATA = {
  @foreach($clients as $c)
  {{ $c->id }}: {
    id:           {{ $c->id }},
    name:         @json($c->name),
    email:        @json($c->email),
    phone:        @json($c->phone ?? null),
    phone_mobile: @json($c->phone_mobile ?? null),
    whatsapp:     @json($c->whatsapp ?? null),
    teams_email:  @json($c->teams_email ?? null),
    type:         @json($c->client_type),
    glpi_id:      @json($c->glpi_user_id ?? null),
    tickets:      {{ $c->tickets_count }},
    active:       {{ $c->is_active ? 'true' : 'false' }},
    date:         @json($c->created_at->format('d/m/Y')),
    last_login:   @json($c->last_login_at ? \Carbon\Carbon::parse($c->last_login_at)->format('d/m/Y H:i') : null),
    avatar:       @json($c->avatar ? asset('storage/'.$c->avatar) : null),
    first_name:   @json($c->first_name ?? null),
    last_name:    @json($c->last_name ?? null),
    birthday:     @json($c->birthday ? \Carbon\Carbon::parse($c->birthday)->format('d/m/Y') : null),
    age:          @json($c->birthday ? \Carbon\Carbon::parse($c->birthday)->age : null),
    gender:       @json($c->gender ?? null),
    hasChat:      @php try { echo \DB::table('chat_access_grants')->where('admin_id',auth()->id())->where('client_id',$c->id)->exists() ? 'true' : 'false'; } catch(\Exception $e) { echo 'false'; } @endphp,
  },
  @endforeach
};
</script>

{{-- DRAWER --}}
<div id="drawerOverlay" onclick="closeDrawer()"></div>
<div id="clientDrawer">
  <div style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));padding:20px;flex-shrink:0;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
      <div style="display:flex;align-items:center;gap:12px;">
        <div id="drawerAvatar" style="width:56px;height:56px;border-radius:50%;border:3px solid rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:white;background:rgba(255,255,255,0.2);flex-shrink:0;overflow:hidden;"></div>
        <div>
          <h5 id="drawerName" style="color:white;margin:0;font-weight:700;"></h5>
          <p id="drawerEmail" style="color:rgba(255,255,255,0.8);margin:0;font-size:13px;"></p>
          <span id="drawerType" style="display:inline-block;background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;margin-top:4px;"></span>
        </div>
      </div>
      <button onclick="closeDrawer()" style="background:rgba(255,255,255,0.2);border:none;width:32px;height:32px;border-radius:50%;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">✕</button>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
      <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:10px;text-align:center;">
        <p id="drawerTickets" style="color:white;font-size:22px;font-weight:800;margin:0;"></p>
        <p style="color:rgba(255,255,255,0.7);font-size:10px;margin:0;">Tickets</p>
      </div>
      <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:10px;text-align:center;">
        <p id="drawerStatus" style="color:white;font-size:14px;font-weight:700;margin:0;"></p>
        <p style="color:rgba(255,255,255,0.7);font-size:10px;margin:0;">Statut</p>
      </div>
      <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:10px;text-align:center;">
        <p id="drawerDate" style="color:white;font-size:12px;font-weight:600;margin:0;"></p>
        <p style="color:rgba(255,255,255,0.7);font-size:10px;margin:0;">Inscrit le</p>
      </div>
    </div>
  </div>

  <div class="drawer-body">
    <div class="drawer-section">
      <p class="drawer-section-title"><i class="material-symbols-rounded" style="font-size:13px;">contacts</i> Contact</p>
      <div id="drawerContact"></div>
    </div>
    <div class="drawer-section">
      <p class="drawer-section-title"><i class="material-symbols-rounded" style="font-size:13px;">info</i> Informations</p>
      <div id="drawerInfo"></div>
    </div>

    {{-- Classifier --}}
    <div class="drawer-section">
      <p class="drawer-section-title"><i class="material-symbols-rounded" style="font-size:13px;">sell</i> Classifier ce client</p>
      <div class="row g-2 mb-3">
        <div class="col-6">
          <div class="type-option" id="opt-client" onclick="selectType('client')">
            <div class="ti" style="background:#EDE9FE;">🟣</div>
            <div><div class="tl" style="color:#6D28D9;">Client</div><div class="td">Déjà en base</div></div>
          </div>
        </div>
        <div class="col-6">
          <div class="type-option" id="opt-user" onclick="selectType('user')">
            <div class="ti" style="background:#FFF7ED;">🟠</div>
            <div><div class="tl" style="color:#C2410C;">Nouveau</div><div class="td">Non classifié</div></div>
          </div>
        </div>
      </div>
      <div id="type-feedback" style="display:none;" class="alert alert-success py-2 px-3 mb-3 text-sm"></div>
      <button type="button" id="saveTypeBtn" onclick="saveClientType()"
              class="btn w-100 mb-0 text-white"
              style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));padding:10px;border-radius:10px;font-weight:600;"
              disabled>
        <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i>
        Enregistrer le type
      </button>
    </div>

    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <p class="drawer-section-title mb-0"><i class="material-symbols-rounded" style="font-size:13px;">confirmation_number</i> Tickets récents</p>
        <a id="drawerTicketsLink" href="#" target="_blank" style="font-size:11px;color:var(--color-primary);text-decoration:none;font-weight:600;">Voir tous →</a>
      </div>
      <div id="drawerTicketsList" style="max-height:320px;overflow-y:auto;" onwheel="event.stopPropagation();">
        <div style="text-align:center;padding:20px;color:#94a3b8;">
          <div style="width:24px;height:24px;border:3px solid var(--color-primary);border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 8px;"></div>
          Chargement…
        </div>
      </div>
    </div>
  </div>

  <div style="padding:14px 20px;border-top:1px solid #e2e8f0;display:flex;gap:8px;flex-shrink:0;">
    <a id="drawerViewBtn" href="#" class="btn btn-sm mb-0 flex-fill text-white"
       style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
      <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">confirmation_number</i>
      Tous les tickets
    </a>
    <button id="drawerChatBtn" type="button" class="btn btn-sm btn-outline-primary mb-0 flex-fill" style="display:none;">
      <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">chat</i>
      Conversation
    </button>
  </div>
</div>

<script>
var _csrf = '{{ csrf_token() }}';
var _cid = null, _selType = null, _origType = null;

var STATUS_META = {
  'pending':    {color:'#f59e0b',label:'En attente'},
  'in_progress':{color:'#3b82f6',label:'En cours'},
  'resolved':   {color:'#22c55e',label:'Résolu'},
  'closed':     {color:'#94a3b8',label:'Clôturé'},
  'local':      {color:'#f59e0b',label:'En attente'},
  'synced':     {color:'#3b82f6',label:'En cours'},
};
var TYPE_META = {
  'client': {icon:'🟣', label:'Client'},
  'user':   {icon:'🟠', label:'Nouveau'},
};

var _st = null;
document.getElementById('clientSearch').addEventListener('input', function() {
  clearTimeout(_st);
  var q = this.value.trim();
  document.getElementById('clearBtn').style.display = q ? 'block' : 'none';
  _st = setTimeout(function() {
    var url = new URL(window.location.href);
    if (q) url.searchParams.set('search', q); else url.searchParams.delete('search');
    url.searchParams.delete('page');
    window.location.href = url.toString();
  }, 400);
});
function clearSearch() {
  var url = new URL(window.location.href);
  url.searchParams.delete('search'); url.searchParams.delete('page');
  window.location.href = url.toString();
}

function infoRow(icon, label, value) {
  return '<div class="info-row"><span class="ir-icon">'+icon+'</span><div><p class="ir-label">'+label+'</p><p class="ir-value">'+escHtml(String(value))+'</p></div></div>';
}

function openDrawer(id) {
  var d = CLIENTS_DATA[id];
  if (!d) return;
  _cid = id; _selType = d.type || null; _origType = d.type || null;

  var av = document.getElementById('drawerAvatar');
  if (d.avatar) { av.innerHTML = '<img src="'+d.avatar+'" style="width:100%;height:100%;object-fit:cover;">'; }
  else { av.textContent = d.name.substring(0,2).toUpperCase(); }

  document.getElementById('drawerName').textContent    = d.name;
  document.getElementById('drawerEmail').textContent   = d.email;
  document.getElementById('drawerTickets').textContent = d.tickets;
  document.getElementById('drawerStatus').textContent  = d.active ? '✅ Actif' : '❌ Inactif';
  document.getElementById('drawerDate').textContent    = d.date;

  var tm = TYPE_META[d.type] || {icon:'⚪',label:'—'};
  document.getElementById('drawerType').textContent = tm.icon + ' ' + tm.label;

  var contact = [infoRow('📧','Email', d.email)];
  if (d.phone)        contact.push(infoRow('📞','Tél. fixe',   d.phone));
  if (d.phone_mobile) contact.push(infoRow('📱','Mobile',      d.phone_mobile));
  if (d.whatsapp)     contact.push(infoRow('💬','WhatsApp',    d.whatsapp));
  if (d.teams_email)  contact.push(infoRow('🟦','Teams',       d.teams_email));
  document.getElementById('drawerContact').innerHTML = contact.join('');

  var info = [infoRow('\u{1F194}','ID Plateforme', '#'+d.id)];
  if (d.first_name || d.last_name) info.push(infoRow('\u{1F464}','Prénom / Nom', (d.first_name||'') + ' ' + (d.last_name||'')));
  if (d.birthday) info.push(infoRow('\u{1F382}','Naissance', d.birthday + (d.age ? ' ('+d.age+' ans)' : '')));
  if (d.gender) { var gl = {male:'\u2642 Homme',female:'\u2640 Femme',other:'\u26A7 Autre'}; info.push(infoRow('\u26A7','Genre', gl[d.gender]||d.gender)); }
  if (d.glpi_id) info.push(infoRow('\u{1F517}','ID GLPI', d.glpi_id));
  info.push(infoRow('📅','Inscrit le', d.date));
  info.push(infoRow('🕐','Dernière connexion', d.last_login || 'Jamais'));
  document.getElementById('drawerInfo').innerHTML = info.join('');

  document.getElementById('opt-client').classList.toggle('selected', d.type === 'client');
  document.getElementById('opt-user').classList.toggle('selected', d.type === 'user');
  document.getElementById('saveTypeBtn').disabled = true;
  document.getElementById('saveTypeBtn').innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i> Enregistrer le type';
  document.getElementById('saveTypeBtn').style.background = '';
  document.getElementById('type-feedback').style.display = 'none';

  var ticketsUrl = '/admin/tickets?client_id=' + id;
  document.getElementById('drawerTicketsLink').href = ticketsUrl;
  document.getElementById('drawerViewBtn').href     = ticketsUrl;

  var chatBtn = document.getElementById('drawerChatBtn');
  chatBtn.style.display = d.hasChat ? 'block' : 'none';
  if (d.hasChat) chatBtn.onclick = function() { closeDrawer(); openClientChat(id, d.name); };

  loadTickets(id);
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById('clientDrawer').classList.add('open');
}

function closeDrawer() {
  document.getElementById('clientDrawer').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('open');
  _cid = null;
}

function loadTickets(clientId) {
  var list = document.getElementById('drawerTicketsList');
  list.innerHTML = '<div style="text-align:center;padding:20px;color:#94a3b8;"><div style="width:24px;height:24px;border:3px solid var(--color-primary);border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 8px;"></div>Chargement…</div>';

  fetch('/admin/clients/'+clientId+'/tickets', {
    headers:{'Accept':'application/json','X-CSRF-TOKEN':_csrf}
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (!data.tickets || data.tickets.length === 0) {
      list.innerHTML = '<div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">Aucun ticket pour ce client</div>';
      return;
    }
    var html = '';
    data.tickets.forEach(function(t) {
      var sm = STATUS_META[t.sync_status] || {color:'#94a3b8',label:'Inconnu'};
      html += '<a href="/admin/tickets/'+t.id+'" class="ticket-card">' +
        '<span style="font-size:11px;font-weight:700;color:white;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));padding:3px 8px;border-radius:6px;flex-shrink:0;">#'+t.id+'</span>' +
        '<div style="flex:1;min-width:0;"><p style="font-size:13px;font-weight:600;color:#1e293b;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+escHtml(t.title||'Sans titre')+'</p>' +
        '<p style="font-size:11px;color:#94a3b8;margin:0;">'+(t.created_at||'')+'</p></div>' +
        '<span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;background:'+sm.color+'18;color:'+sm.color+';border:1px solid '+sm.color+'40;white-space:nowrap;">'+sm.label+'</span>' +
        '<i class="material-symbols-rounded" style="font-size:16px;color:#94a3b8;flex-shrink:0;">chevron_right</i></a>';
    });
    list.innerHTML = html;
  })
  .catch(function() {
    list.innerHTML = '<div style="text-align:center;padding:20px;color:#ef4444;font-size:13px;">⚠️ Erreur de chargement</div>';
  });
}

function selectType(t) {
  _selType = t;
  document.getElementById('opt-client').classList.toggle('selected', t === 'client');
  document.getElementById('opt-user').classList.toggle('selected', t === 'user');
  document.getElementById('saveTypeBtn').disabled = (t === _origType);
}

function saveClientType() {
  if (!_selType || !_cid) return;
  var btn = document.getElementById('saveTypeBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">hourglass_top</i> Enregistrement...';

  fetch('/admin/clients/'+_cid+'/type', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':_csrf,'Accept':'application/json'},
    body: JSON.stringify({client_type:_selType})
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      _origType = _selType;
      btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">check_circle</i> Enregistré ✓';
      btn.style.background = 'linear-gradient(135deg,#10b981,#059669)';
      var fb = document.getElementById('type-feedback');
      fb.textContent = 'Type mis à jour : ' + (_selType === 'client' ? '🟣 Client' : '🟠 Nouveau');
      fb.style.display = 'block';
      setTimeout(function() { closeDrawer(); location.reload(); }, 1200);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i> Enregistrer le type';
    }
  })
  .catch(function(e) {
    btn.disabled = false;
    btn.style.background = '';
    btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i> Enregistrer le type';
  });
}

function openClientChat(id, name) {
  if (typeof cwOpen === 'function') { cwOpen(); setTimeout(function() { cwOpenRO(id, true, name); }, 350); }
}

function escHtml(t) { return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeDrawer();
  if ((e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) || (e.ctrlKey && e.key === 'k')) {
    e.preventDefault(); document.getElementById('clientSearch').focus();
  }
});
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>

@endsection