@extends('layouts.dashboard')
@section('title','Gérer les tickets')
@section('page-title','Gérer les tickets')
@php
  $todayFmt     = now()->format('Y-m-d');
  $isTodayActive = (!request('status') && !request('search') && !request('client_type'))
                   && (request('date_from') === $todayFmt && request('date_to') === $todayFmt)
                   && !request('all');
@endphp

@section('content')
<style>
*{box-sizing:border-box;}
.tk-wrap{font-family:inherit;}

/* ── Header card ── */
.tk-header{
  background:linear-gradient(135deg,var(--color-primary) 0%,var(--color-secondary) 100%);
  border-radius:16px;padding:18px 20px;margin-bottom:20px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.tk-header-left{display:flex;align-items:center;gap:12px;min-width:0;}
.tk-header-icon{width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:12px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.tk-header h5{color:#fff;font-weight:700;margin:0;font-size:16px;}
.tk-header p{color:rgba(255,255,255,.8);margin:0;font-size:12px;}
.tk-stats-group{display:flex;gap:20px;flex-shrink:0;}
.tk-stat{text-align:center;color:#fff;}
.tk-stat-num{font-size:24px;font-weight:800;line-height:1;}
.tk-stat-label{font-size:10px;opacity:.75;margin-top:2px;}
@media(max-width:575px){
  .tk-stats-group{gap:12px;}
  .tk-stat-num{font-size:18px;}
  .tk-stat-label{font-size:9px;}
  .tk-header-icon{display:none;}
}

/* ── Filters card ── */
.tk-filters{background:var(--bs-body-bg,#fff);border-radius:14px;
  border:1px solid var(--bs-border-color,#e2e8f0);padding:14px 16px;margin-bottom:16px;
  box-shadow:0 1px 6px rgba(0,0,0,.04);}
.tk-search-bar{display:flex;align-items:center;gap:10px;
  background:var(--bs-tertiary-bg,#f8fafc);border:1.5px solid var(--bs-border-color,#e2e8f0);
  border-radius:10px;padding:9px 12px;transition:.2s;margin-bottom:12px;}
.tk-search-bar:focus-within{border-color:var(--color-primary);
  box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 12%,transparent);}
.tk-search-bar input{border:none;outline:none;background:transparent;
  font-size:13px;color:inherit;width:100%;}
.tk-search-bar input::placeholder{color:#a0aec0;}
.tk-search-bar .si{color:#a0aec0;font-size:18px;flex-shrink:0;}
.tk-search-bar .kbd{flex-shrink:0;font-size:10px;font-weight:600;color:#a0aec0;
  background:var(--bs-body-bg,#fff);border:1px solid var(--bs-border-color,#e2e8f0);
  border-radius:5px;padding:2px 6px;font-family:monospace;
  display:none;}
@media(min-width:768px){.tk-search-bar .kbd{display:inline-block;}}

.tk-filter-row{display:flex;gap:8px;flex-wrap:nowrap;align-items:flex-end;}
.tk-filter-group{display:flex;flex-direction:column;gap:4px;flex:1;min-width:0;}
.tk-filter-group label{font-size:11px;font-weight:600;color:#64748b;white-space:nowrap;}
.tk-filter-group select,.tk-filter-group input[type=date]{
  border:1.5px solid var(--bs-border-color,#e2e8f0);border-radius:9px;
  padding:7px 8px;font-size:12px;background:var(--bs-body-bg,#fff);
  color:inherit;outline:none;transition:.15s;cursor:pointer;width:100%;}
.tk-filter-group select:focus,.tk-filter-group input[type=date]:focus{
  border-color:var(--color-primary);}
.tk-filter-actions{display:flex;gap:6px;align-items:flex-end;flex-shrink:0;}
.btn-filter{display:flex;align-items:center;gap:5px;padding:8px 14px;border-radius:10px;
  border:none;font-size:12px;font-weight:600;cursor:pointer;transition:.15s;
  background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;white-space:nowrap;}
.btn-filter:hover{opacity:.9;}
.btn-clear{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;
  border:1.5px solid var(--bs-border-color,#e2e8f0);background:transparent;
  color:#64748b;cursor:pointer;transition:.15s;flex-shrink:0;}
.btn-clear:hover{border-color:#ef4444;color:#ef4444;}
/* Responsive filters */
@media(max-width:991px){
  .tk-filter-row{flex-wrap:wrap;}
  .tk-filter-group{min-width:calc(50% - 8px);flex:none;}
}
@media(max-width:575px){
  .tk-filter-group{min-width:100%;flex:none;}
}

/* ── Pills ── */
.tk-pills{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;padding-top:10px;
  border-top:1px solid var(--bs-border-color,#e2e8f0);align-items:center;}
.tk-pills-label{font-size:11px;font-weight:700;color:#94a3b8;white-space:nowrap;
  padding:4px 2px;}
.pill{display:inline-flex;align-items:center;gap:5px;padding:5px 13px;
  border-radius:20px;font-size:11px;font-weight:600;text-decoration:none;
  transition:.15s;border:1.5px solid transparent;white-space:nowrap;}
.pill .pill-count{background:rgba(0,0,0,.12);border-radius:99px;
  padding:1px 6px;font-size:10px;font-weight:700;}
.pill-today{border-color:var(--color-primary);color:var(--color-primary);}
.pill-today.active,.pill-today:hover{background:var(--color-primary);color:#fff;}
.pill-all{border-color:#64748b;color:#64748b;}
.pill-all.active,.pill-all:hover{background:#64748b;color:#fff;}
.pill-pending{border-color:#f59e0b;color:#d97706;}
.pill-pending.active,.pill-pending:hover{background:#f59e0b;color:#fff;}
.pill-inprogress{border-color:#0ea5e9;color:#0284c7;}
.pill-inprogress.active,.pill-inprogress:hover{background:#0ea5e9;color:#fff;}
.pill-resolved{border-color:#10b981;color:#059669;}
.pill-resolved.active,.pill-resolved:hover{background:#10b981;color:#fff;}
.pill-nonclass{border-color:#ef4444;color:#dc2626;}
.pill-nonclass.active,.pill-nonclass:hover{background:#ef4444;color:#fff;}

/* ── Table card ── */
.tk-table-card{background:var(--bs-body-bg,#fff);border-radius:14px;
  border:1px solid var(--bs-border-color,#e2e8f0);overflow:hidden;
  box-shadow:0 1px 6px rgba(0,0,0,.04);}
.tk-table-head{display:flex;align-items:center;justify-content:space-between;
  padding:14px 20px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);}
.tk-table-head h6{margin:0;font-weight:700;font-size:14px;}
.tk-count-badge{background:var(--bs-tertiary-bg,#f1f5f9);color:#64748b;
  font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;}

table.tk-table{width:100%;border-collapse:collapse;}
table.tk-table thead tr{background:var(--bs-tertiary-bg,#f8fafc);}
table.tk-table thead th{
  font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
  color:#94a3b8;padding:10px 14px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);
  white-space:nowrap;}
table.tk-table tbody tr{border-bottom:1px solid var(--bs-border-color,#f1f5f9);
  transition:background .12s;cursor:pointer;}
table.tk-table tbody tr:hover{background:color-mix(in srgb,var(--color-primary) 4%,transparent);}
table.tk-table tbody tr:last-child{border-bottom:none;}
table.tk-table td{padding:12px 14px;vertical-align:middle;}

/* Type badges */
.ctype{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;
  border-radius:20px;font-size:10px;font-weight:700;}
.ctype-tns{background:#EEF2FF;color:#4F46E5;}
.ctype-l2t{background:#F5F3FF;color:#7C3AED;}
.ctype-new{background:#FFF7ED;color:#C2410C;}

/* Priority badges */
.prio{display:inline-flex;align-items:center;gap:3px;padding:3px 9px;
  border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap;}
.prio-1{background:#f1f5f9;color:#475569;}
.prio-2{background:#e0f2fe;color:#0369a1;}
.prio-3{background:#fef3c7;color:#b45309;}
.prio-4{background:#fee2e2;color:#b91c1c;}
.prio-5{background:#1e1b4b;color:#fff;}

/* Status badges */
.stbadge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;
  border-radius:20px;font-size:10px;font-weight:700;}
.st-pending{background:#fef3c7;color:#b45309;}
.st-inprogress{background:#dbeafe;color:#1d4ed8;}
.st-resolved{background:#d1fae5;color:#065f46;}
.st-closed{background:#f1f5f9;color:#475569;}

.tk-replied-badge{background:#d1fae5;color:#065f46;font-size:9px;font-weight:700;
  padding:2px 6px;border-radius:99px;display:inline-block;margin-top:3px;}

.btn-reply{display:inline-flex;align-items:center;gap:5px;padding:6px 16px;
  border-radius:12px;font-size:12px;font-weight:600;border:none;cursor:pointer;
  background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
  color:#fff;text-decoration:none;transition:opacity .15s;white-space:nowrap;}
.btn-reply:hover{opacity:.85;color:#fff;}

/* Bulk bar */
.bulk-bar{display:none;align-items:center;gap:12px;padding:12px 18px;
  background:color-mix(in srgb,var(--color-primary) 8%,transparent);
  border:1px solid color-mix(in srgb,var(--color-primary) 25%,transparent);
  border-radius:12px;margin-bottom:14px;}
.bulk-bar.show{display:flex;}

/* Empty state */
.tk-empty{text-align:center;padding:60px 20px;}
.tk-empty-icon{font-size:56px;color:#cbd5e1;display:block;margin-bottom:12px;}
.tk-empty p{color:#94a3b8;font-size:14px;margin:0;}
</style>

<div class="tk-wrap">

{{-- HEADER --}}
<div class="tk-header">
  <div class="tk-header-left">
    <div class="tk-header-icon">
      <i class="material-symbols-rounded text-white" style="font-size:28px;">confirmation_number</i>
    </div>
    <div>
      <h5>Gérer les tickets</h5>
      <p>Répondez aux demandes des clients</p>
    </div>
  </div>
  <div class="tk-stats-group">
    <div class="tk-stat">
      <div class="tk-stat-num">{{ $totalAll }}</div>
      <div class="tk-stat-label">Total</div>
    </div>
    <div class="tk-stat">
      <div class="tk-stat-num">{{ $totalPending }}</div>
      <div class="tk-stat-label">En attente</div>
    </div>
    <div class="tk-stat">
      <div class="tk-stat-num">{{ $totalProgress }}</div>
      <div class="tk-stat-label">En cours</div>
    </div>
    <div class="tk-stat">
      <div class="tk-stat-num">{{ $totalResolved }}</div>
      <div class="tk-stat-label">Résolus</div>
    </div>
  </div>
</div>

{{-- BULK BAR --}}
<div class="bulk-bar" id="bulkBar">
  <span style="font-size:12px;font-weight:700;" id="bulkCount">0 sélectionnés</span>
  <div style="margin-left:auto;display:flex;gap:8px;">
    <button onclick="bulkAction('close')" class="btn btn-sm mb-0" style="background:#ef4444;color:#fff;border-radius:8px;">
      <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">lock</i>Clôturer
    </button>
    <button onclick="showNoteModal()" class="btn btn-sm mb-0" style="background:var(--color-primary);color:#fff;border-radius:8px;">
      <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">note_add</i>Ajouter note
    </button>
    <button onclick="clearSelection()" class="btn btn-sm mb-0 btn-outline-secondary" style="border-radius:8px;">Annuler</button>
  </div>
</div>

{{-- NOTE MODAL --}}
<div id="noteModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--bs-body-bg,#fff);border-radius:14px;padding:24px;width:420px;max-width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);">
    <h6 class="font-weight-bold mb-3">Ajouter une note aux tickets sélectionnés</h6>
    <textarea id="bulkNote" class="form-control mb-3" rows="4" placeholder="Note à ajouter…"></textarea>
    <div class="d-flex gap-2 justify-content-end">
      <button onclick="document.getElementById('noteModal').style.display='none'" class="btn btn-sm mb-0 btn-outline-secondary" style="border-radius:8px;">Annuler</button>
      <button onclick="bulkAction('note')" class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);border-radius:8px;">Envoyer</button>
    </div>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;">
  <i class="material-symbols-rounded me-2">check_circle</i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- FILTERS --}}
<div class="tk-filters">
  <form method="GET" action="{{ route('admin.tickets') }}" id="ticketFilterForm">

    <div class="tk-search-bar">
      <i class="material-symbols-rounded si">search</i>
      <input type="text" name="search" id="searchInput"
             placeholder="Rechercher par titre, client, description, email…"
             value="{{ request('search') }}" autocomplete="off">
      <span class="kbd">/ ou Ctrl+K</span>
    </div>

    <div class="tk-filter-row">
      <div class="tk-filter-group">
        <label>📅 Date début</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}">
      </div>
      <div class="tk-filter-group">
        <label>📅 Date fin</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}">
      </div>
      <div class="tk-filter-group">
        <label>🏷️ Statut</label>
        <select name="status">
          <option value="">Tous les statuts</option>
          <option value="pending"     {{ request('status')==='pending'     ?'selected':'' }}>⏳ En attente</option>
          <option value="in_progress" {{ request('status')==='in_progress' ?'selected':'' }}>🔄 En cours</option>
          <option value="resolved"    {{ request('status')==='resolved'    ?'selected':'' }}>✅ Résolus</option>
          <option value="closed"      {{ request('status')==='closed'      ?'selected':'' }}>🔒 Clôturé</option>
        </select>
      </div>
      <div class="tk-filter-group">
        <label>📁 Catégorie</label>
        <select name="category">
          <option value="">Toutes les catégories</option>
          <option value="incident_technique" {{ request('category')==='incident_technique'?'selected':'' }}>🔴 Incident technique</option>
          <option value="integration_api"    {{ request('category')==='integration_api'?'selected':'' }}>🔵 Intégration API SMS</option>
          <option value="facturation"        {{ request('category')==='facturation'?'selected':'' }}>🟡 Facturation</option>
          <option value="plateforme"         {{ request('category')==='plateforme'?'selected':'' }}>🟢 Plateforme L2T</option>
          <option value="paiement_mobile"    {{ request('category')==='paiement_mobile'?'selected':'' }}>🟠 Paiement Mobile</option>
          <option value="autre"              {{ request('category')==='autre'?'selected':'' }}>⚪ Autre</option>
        </select>
      </div>
      <div class="tk-filter-group">
        <label>👤 Type client</label>
        <select name="client_type">
          <option value="">Tous les types</option>
          <option value="client"        {{ request('client_type')==='client'        ?'selected':'' }}>🔵 Client</option>
          <option value="non_classifie" {{ request('client_type')==='non_classifie' ?'selected':'' }}>🟠 Non classifié</option>
        </select>
      </div>
      <div class="tk-filter-actions">
        <button type="submit" class="btn-filter">
          <i class="material-symbols-rounded" style="font-size:16px;">filter_list</i>Filtrer
        </button>
        <a href="{{ route('admin.tickets') }}" class="btn-clear">
          <i class="material-symbols-rounded" style="font-size:16px;">close</i>
        </a>
      </div>
    </div>

    {{-- Pills --}}
    <div class="tk-pills">
      <span class="tk-pills-label">Raccourcis :</span>

      <a href="{{ route('admin.tickets') }}?date_from={{ $todayFmt }}&date_to={{ $todayFmt }}"
         class="pill pill-today {{ $isTodayActive ? 'active' : '' }}">
        📅 Aujourd'hui
      </a>

      <a href="{{ route('admin.tickets') }}?all=1"
         class="pill pill-all {{ request('all')==='1' ? 'active' : '' }}">
        Tout <span class="pill-count">{{ $totalAll }}</span>
      </a>

      <a href="{{ route('admin.tickets') }}?status=pending"
         class="pill pill-pending {{ request('status')==='pending' && !request('all') ? 'active' : '' }}">
        ⏳ En attente <span class="pill-count">{{ $totalPending }}</span>
      </a>

      <a href="{{ route('admin.tickets') }}?status=in_progress"
         class="pill pill-inprogress {{ request('status')==='in_progress' ? 'active' : '' }}">
        🔄 En cours <span class="pill-count">{{ $totalProgress }}</span>
      </a>

      <a href="{{ route('admin.tickets') }}?status=resolved"
         class="pill pill-resolved {{ request('status')==='resolved' ? 'active' : '' }}">
        ✅ Résolus <span class="pill-count">{{ $totalResolved }}</span>
      </a>

      <a href="{{ route('admin.tickets') }}?client_type=non_classifie"
         class="pill pill-nonclass {{ request('client_type')==='non_classifie' ? 'active' : '' }}">
        🟠 Non classifiés <span class="pill-count">{{ $totalNonClass }}</span>
      </a>
    </div>

  </form>
</div>

{{-- TABLE --}}
<div class="tk-table-card">
  <div class="tk-table-head">
    <h6>
      Liste des tickets
      @if(request('search') || request('status') || request('date_from') || request('client_type'))
        <span style="background:var(--color-primary);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;margin-left:6px;">Filtrés</span>
      @endif
    </h6>
    <span class="tk-count-badge">{{ $tickets->total() }} ticket(s)</span>
  </div>

  {{-- Pagination TOP supprimée --}}

  <div style="overflow-x:auto;">
    <table class="tk-table">
      <thead>
        <tr>
          <th style="width:40px;padding-left:20px;">
            <input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="width:15px;height:15px;cursor:pointer;">
          </th>
          <th>ID</th>
          <th>Ticket</th>
          <th>Client</th>
          <th>Type</th>
          <th>Catégorie</th>
          <th style="text-align:center;">Priorité</th>
          <th style="text-align:center;">Statut</th>
          <th style="text-align:center;">Date</th>
          <th style="text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tickets as $ticket)
        @php
          $catLabels = [
            'incident_technique' => ['🔴','Incident'],
            'integration_api'    => ['🔵','API SMS'],
            'facturation'        => ['🟡','Facturation'],
            'plateforme'         => ['🟢','Plateforme'],
            'paiement_mobile'    => ['🟠','Paiement'],
            'autre'              => ['⚪','Autre'],
          ];
          $cat = $catLabels[$ticket->category] ?? ['⚪', $ticket->category ?? 'Autre'];

          $p = $ticket->priority ?? 3;
          $pLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];

          // Type client
          $ct = $ticket->user?->client_type;
          if ($ct === 'client') {
            $ctBadge = '<span class="ctype ctype-tns">🔵 Client</span>';
          } else {
            $ctBadge = '<span class="ctype ctype-new">🟠 Non classifié</span>';
          }

          // Statut
          $stMap = [
            'pending'     => ['st-pending','En attente','schedule'],
            'in_progress' => ['st-inprogress','En cours','autorenew'],
            'resolved'    => ['st-resolved','Résolu','check_circle'],
            'closed'      => ['st-closed','Clôturé','lock'],
            'synced'      => ['st-pending','Sync','sync'],
            'failed'      => ['st-pending','Erreur','error'],
          ];
          $st = $stMap[$ticket->sync_status] ?? ['st-pending','Inconnu','help'];
        @endphp
        <tr onclick="goTicket({{ $ticket->id }}, event)">
          <td style="padding-left:20px;" onclick="event.stopPropagation()">
            <input type="checkbox" class="ticket-check" value="{{ $ticket->id }}" style="width:15px;height:15px;cursor:pointer;">
          </td>
          <td>
            <span style="font-size:11px;font-weight:700;color:var(--color-primary);">#{{ $ticket->id }}</span>
          </td>
          <td style="max-width:220px;">
            <div class="d-flex align-items-center gap-2">
              <p style="font-size:12px;font-weight:600;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Str::limit($ticket->title, 36) }}</p>
              @if($ticket->ai_analysis)
                <i class="material-symbols-rounded text-primary" style="font-size:14px;" title="Analysé par IA">smart_toy</i>
              @endif
            </div>
            <p style="font-size:11px;color:#94a3b8;margin:0;">{{ Str::limit($ticket->description, 44) }}</p>
            @if($ticket->solution)
              <span class="tk-replied-badge">✅ Répondu</span>
            @endif
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:7px;">
              @if($ticket->user?->avatar)
                <img src="{{ asset('storage/' . $ticket->user->avatar) }}"
                     style="width:26px;height:26px;border-radius:50%;object-fit:cover;border:1.5px solid #e2e8f0;flex-shrink:0;" alt="">
              @else
                <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <span style="font-size:9px;font-weight:700;color:#fff;">{{ strtoupper(substr($ticket->user->name ?? 'U', 0, 2)) }}</span>
                </div>
              @endif
              <span style="font-size:12px;color:#64748b;">{{ $ticket->user->name ?? 'N/A' }}</span>
            </div>
          </td>
          <td>{!! $ctBadge !!}</td>
          <td>
            <span style="font-size:12px;">{{ $cat[0] }} {{ $cat[1] }}</span>
          </td>
          <td style="text-align:center;">
            <span class="prio prio-{{ $p }}">{{ $pLabels[$p] ?? 'Moyenne' }}</span>
          </td>
          <td style="text-align:center;">
            <span class="stbadge {{ $st[0] }}">
              <i class="material-symbols-rounded" style="font-size:11px;vertical-align:middle;">{{ $st[2] }}</i>
              {{ $st[1] }}
            </span>
          </td>
          <td style="text-align:center;">
            <span style="font-size:11px;color:#94a3b8;">{{ $ticket->created_at->format('d/m/Y') }}</span>
          </td>
          <td style="text-align:center;" onclick="event.stopPropagation()">
            <a href="{{ route('admin.tickets.show', $ticket->id) }}#tab-reply" class="btn-reply">
              <i class="material-symbols-rounded" style="font-size:10px;">reply</i>Répondre
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="10">
            <div class="tk-empty">
              <i class="material-symbols-rounded tk-empty-icon">confirmation_number</i>
              <p>Aucun ticket trouvé</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="padding:14px 20px;">
    {{ $tickets->appends(request()->query())->links() }}
  </div>
</div>

</div>
{{-- /tk-wrap --}}

<script>
document.addEventListener('keydown', function(e) {
  if ((e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') ||
      (e.ctrlKey && e.key === 'k')) {
    e.preventDefault();
    document.getElementById('searchInput').focus();
    document.getElementById('searchInput').select();
  }
});

function goTicket(id, e) {
  if (e.target.type === 'checkbox' || e.target.closest('button') || e.target.closest('a') || e.target.closest('select') || e.target.closest('form')) return;
  window.location.href = '/admin/tickets/' + id;
}

function toggleAll(master) {
  document.querySelectorAll('.ticket-check').forEach(el => el.checked = master.checked);
  updateBulkBar();
}
document.addEventListener('change', function(e) {
  if (e.target.classList.contains('ticket-check')) {
    updateBulkBar();
    const all = document.querySelectorAll('.ticket-check');
    const checked = document.querySelectorAll('.ticket-check:checked');
    document.getElementById('selectAll').checked = all.length === checked.length;
  }
});
function updateBulkBar() {
  const checked = document.querySelectorAll('.ticket-check:checked');
  const bar = document.getElementById('bulkBar');
  document.getElementById('bulkCount').textContent = checked.length + ' sélectionnés';
  if (checked.length > 0) bar.classList.add('show');
  else bar.classList.remove('show');
}
function getSelectedIds() {
  return [...document.querySelectorAll('.ticket-check:checked')].map(el => el.value);
}
function clearSelection() {
  document.querySelectorAll('.ticket-check, #selectAll').forEach(el => el.checked = false);
  updateBulkBar();
}
function showNoteModal() {
  document.getElementById('noteModal').style.display = 'flex';
}
function bulkAction(action) {
  const ids = getSelectedIds();
  const note = document.getElementById('bulkNote')?.value ?? '';
  if (!ids.length) return;
  fetch('/admin/tickets/bulk', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' },
    body: JSON.stringify({ ids, action, note })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('noteModal').style.display = 'none';
      alert('✅ Action appliquée sur ' + data.affected + ' ticket(s)');
      location.reload();
    }
  })
  .catch(() => alert('Erreur réseau'));
}
</script>
@endsection