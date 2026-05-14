@extends('layouts.dashboard')
@section('title','Mes Tickets')
@section('page-title','Mes Tickets')

@section('content')

{{-- HEADER --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow dark-bg-card">
            <i class="material-symbols-rounded" style="font-size:36px; color:var(--color-primary);">confirmation_number</i>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">Mes Tickets</h5>
            <p class="text-white text-sm mb-0 opacity-8">Suivez l'état de vos demandes de support</p>
          </div>
        </div>
        <a href="{{ route('tickets.create') }}"
           class="btn bg-white mb-0 dark-bg-card" style="color:var(--color-primary); font-weight:600;">
          <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">add</i>
          Nouveau Ticket
        </a>
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

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
  <i class="material-symbols-rounded me-2">error</i>{{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ✅ STATS — cliquables pour filtrer --}}
@php
  $total    = $tickets->count();
  $pending  = $tickets->where('sync_status','pending')->count();
  $inprog   = $tickets->whereIn('sync_status',['in_progress','synced','escalated'])->count();
  $resolved = $tickets->whereIn('sync_status',['resolved','closed'])->count();
@endphp

<div class="row mb-3">
  <div class="col-md-8">
    <div class="input-group input-group-outline border-radius-lg shadow-sm overflow-hidden" 
         style="border: 1px solid var(--border-color); background: var(--input-bg); transition: all 0.2s ease;">
      <span class="input-group-text text-body bg-transparent border-0 pe-0">
        <i class="material-symbols-rounded text-secondary" style="font-size: 20px;">search</i>
      </span>
      <input type="text" id="ticketSearch" class="form-control border-0 ps-2" 
             placeholder="Rechercher par titre ou description..." 
             style="height: 46px; box-shadow: none !important; background: transparent !important;"
             onkeyup="searchTickets(this.value)">
    </div>
  </div>
  <div class="col-md-4 mt-md-0 mt-3">
    <div class="d-flex align-items-center justify-content-end h-100">
      <div class="dropdown w-100">
        <button class="btn mb-0 dropdown-toggle w-100" type="button" 
                id="categoryFilterBtn" data-bs-toggle="dropdown" aria-expanded="false"
                style="border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); text-transform: none; font-weight: 600;">
          <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">filter_alt</i>
          Toutes catégories
        </button>
        <ul class="dropdown-menu dropdown-menu-end p-2 border-radius-lg shadow-lg border-0" aria-labelledby="categoryFilterBtn">
          <li><a class="dropdown-item border-radius-md" href="javascript:;" onclick="filterByCategory('all')">Toutes catégories</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item border-radius-md" href="javascript:;" onclick="filterByCategory('incident_technique')">🔴 Incident technique</a></li>
          <li><a class="dropdown-item border-radius-md" href="javascript:;" onclick="filterByCategory('integration_api')">🔵 Intégration API SMS</a></li>
          <li><a class="dropdown-item border-radius-md" href="javascript:;" onclick="filterByCategory('facturation')">🟡 Facturation</a></li>
          <li><a class="dropdown-item border-radius-md" href="javascript:;" onclick="filterByCategory('plateforme')">🟢 Plateforme L2T</a></li>
          <li><a class="dropdown-item border-radius-md" href="javascript:;" onclick="filterByCategory('paiement_mobile')">🟠 Paiement Mobile</a></li>
          <li><a class="dropdown-item border-radius-md" href="javascript:;" onclick="filterByCategory('autre')">⚪ Autre</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4" id="statsRow">
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 stat-card" data-filter="all"
         style="cursor:pointer; transition:all 0.2s; border:2px solid transparent;">
      <h4 class="font-weight-bolder mb-0">{{ $total }}</h4>
      <p class="text-xs text-secondary mb-0">Total</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 stat-card" data-filter="pending"
         style="cursor:pointer; transition:all 0.2s; border:2px solid transparent;">
      <h4 class="font-weight-bolder mb-0 text-warning">{{ $pending }}</h4>
      <p class="text-xs text-secondary mb-0">En attente</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 stat-card" data-filter="in_progress"
         style="cursor:pointer; transition:all 0.2s; border:2px solid transparent;">
      <h4 class="font-weight-bolder mb-0 text-info">{{ $inprog }}</h4>
      <p class="text-xs text-secondary mb-0">En cours</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 stat-card" data-filter="resolved"
         style="cursor:pointer; transition:all 0.2s; border:2px solid transparent;">
      <h4 class="font-weight-bolder mb-0 text-success">{{ $resolved }}</h4>
      <p class="text-xs text-secondary mb-0">Résolus</p>
    </div>
  </div>
</div>

{{-- ✅ Label filtre actif --}}
<div id="filterLabel" class="mb-3" style="display:none;">
  <span class="badge text-white px-3 py-2"
        style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary)); font-size:12px;">
    <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">filter_list</i>
    <span id="filterLabelText"></span>
    <span onclick="applyFilter('all')"
          style="cursor:pointer; margin-left:8px; opacity:0.8;"
          title="Réinitialiser">✕</span>
  </span>
</div>

{{-- LISTE DES TICKETS --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0 pt-3 px-4 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 font-weight-bold">Liste de mes demandes</h6>
        <span id="ticketCount" class="badge bg-gradient-secondary text-xs">{{ $total }} ticket(s)</span>
      </div>
      <div class="card-body px-0 pb-2" id="ticketsContainer">

        @forelse($tickets as $ticket)
        @php
          $catLabels = [
            'incident_technique' => ['🔴', 'Incident technique'],
            'integration_api'    => ['🔵', 'Intégration API SMS'],
            'facturation'        => ['🟡', 'Facturation'],
            'plateforme'         => ['🟢', 'Plateforme L2T'],
            'paiement_mobile'    => ['🟠', 'Paiement Mobile'],
            'autre'              => ['⚪', 'Autre'],
          ];
          $cat = $catLabels[$ticket->category] ?? ['🎫', ucfirst($ticket->category ?? 'Non catégorisé')];

          $statusData = [
            'pending'     => ['warning',   'En attente', 'schedule'],
            'in_progress' => ['info',      'En cours',   'autorenew'],
            'escalated'   => ['danger',    'Escaladé',   'priority_high'],
            'resolved'    => ['success',   'Résolu',     'check_circle'],
            'closed'      => ['secondary', 'Clôturé',    'lock'],
            'local'       => ['warning',   'En attente', 'schedule'],
            'synced'      => ['info',      'En cours',   'autorenew'],
            'failed'      => ['warning',   'En attente', 'schedule'],
          ];
          $st = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu','help'];
          $isPending = $ticket->sync_status === 'pending';
          $commentCount = $ticket->comments->count();

          // ✅ Filtre JS — classe pour filtrage côté client
          $filterClass = match(true) {
            in_array($ticket->sync_status, ['resolved', 'closed']) => 'filter-resolved',
            in_array($ticket->sync_status, ['in_progress', 'synced', 'escalated']) => 'filter-in_progress',
            default => 'filter-pending',
          };
          $categoryClass = 'cat-' . ($ticket->category ?? 'autre');

          // ✅ Date corrigée — timezone Tunis
          $createdAt = $ticket->created_at->timezone('Africa/Tunis')->format('d/m/Y à H:i');
        @endphp

        {{-- TICKET CARD --}}
        <div class="px-4 py-3 mb-3 mx-4 border-radius-lg ticket-item {{ $filterClass }} {{ $categoryClass }}"
             id="ticket-{{ $ticket->id }}"
             style="background: #fff; border: 1px solid #f1f5f9; transition: all 0.2s ease; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.02);"
             onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.06)'; this.style.borderColor='var(--color-primary)';"
             onmouseleave="this.style.transform='none'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.02)'; this.style.borderColor='#f1f5f9';"
             onclick="window.location.href='{{ route('tickets.show', $ticket->id) }}'">

          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center flex-grow-1 min-width-0">
              <div class="me-3 flex-shrink-0">
                <div class="avatar avatar-md border-radius-lg bg-light d-flex align-items-center justify-content-center" 
                     style="background: #f0f4ff !important; width: 48px; height: 48px;">
                  <span class="font-weight-bold" style="color: var(--color-primary); font-size: 14px;">#{{ $ticket->id }}</span>
                </div>
              </div>
              <div class="flex-grow-1 min-width-0 pe-3">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                  <h6 class="text-sm font-weight-bold mb-0 text-truncate ticket-title">{{ $ticket->title }}</h6>
                  <span class="badge text-dark text-xs font-weight-normal py-1 px-2 ticket-category-label"
                        style="background:#f8fafc; border:1px solid #e2e8f0; font-size:10px;">
                    {{ $cat[0] }} {{ $cat[1] }}
                  </span>
                  @if($commentCount > 0)
                  <span class="badge bg-light text-primary text-xs font-weight-bold py-1 px-2" style="font-size: 10px;">
                    <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">chat</i>
                    {{ $commentCount }}
                  </span>
                  @endif
                </div>
                <p class="text-xs text-secondary mb-1 text-truncate ticket-desc">{{ $ticket->description }}</p>
                <div class="d-flex align-items-center gap-3">
                  <span class="text-xxs text-secondary">
                    <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">calendar_today</i>
                    {{ $createdAt }}
                  </span>
                  @if($ticket->solution)
                  <span class="text-xxs text-success font-weight-bold">
                    <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">verified</i>
                    Réponse disponible
                  </span>
                  @endif
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 flex-shrink-0">
              <span class="badge bg-gradient-{{ $st[0] }} text-xxs px-3 py-1 border-radius-pill">
                <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">{{ $st[2] }}</i>
                {{ $st[1] }}
              </span>
              
              <div class="dropdown" onclick="event.stopPropagation()">
                <button class="btn btn-link text-secondary mb-0 p-0" type="button" data-bs-toggle="dropdown">
                  <i class="material-symbols-rounded" style="font-size: 20px;">more_vert</i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-2 border-radius-lg shadow-lg border-0">
                  <li><a class="dropdown-item border-radius-md text-sm" href="{{ route('tickets.show', $ticket->id) }}">
                    <i class="material-symbols-rounded me-2" style="font-size:16px;vertical-align:middle;">visibility</i>Voir les détails</a></li>
                  @if($isPending)
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form method="POST" action="{{ route('tickets.destroy', $ticket->id) }}" onsubmit="return confirm('Supprimer ce ticket ?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="dropdown-item border-radius-md text-sm text-danger">
                        <i class="material-symbols-rounded me-2" style="font-size:16px;vertical-align:middle;">delete</i>Supprimer
                      </button>
                    </form>
                  </li>
                  @endif
                  @if($ticket->sync_status === 'failed')
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form method="POST" action="{{ route('tickets.retry', $ticket->id) }}">
                      @csrf
                      <button type="submit" class="dropdown-item border-radius-md text-sm text-warning">
                        <i class="material-symbols-rounded me-2" style="font-size:16px;vertical-align:middle;">refresh</i>Réessayer
                      </button>
                    </form>
                  </li>
                  @endif
                </ul>
              </div>
            </div>
          </div>
        </div>

        @empty
        <div class="text-center py-5 px-4">
          <div class="avatar avatar-xl mx-auto mb-3"
               style="background: linear-gradient(135deg,var(--color-primary),var(--color-secondary)); width:80px;height:80px;border-radius:50%;">
            <i class="material-symbols-rounded text-white" style="font-size:40px;line-height:80px;">confirmation_number</i>
          </div>
          <h6 class="font-weight-bold mb-2">Aucun ticket pour le moment</h6>
          <p class="text-sm text-secondary mb-4">
            Vous n'avez pas encore soumis de demande de support.<br>
            Notre équipe est disponible pour vous aider.
          </p>
          <a href="{{ route('tickets.create') }}" class="btn text-white"
             style="background: linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">add</i>
            Créer mon premier ticket
          </a>
        </div>
        @endforelse

        {{-- ✅ Message "aucun résultat" pour le filtre --}}
        <div id="noFilterResult" class="text-center py-4 px-4" style="display:none;">
          <i class="material-symbols-rounded text-secondary" style="font-size:40px;">search_off</i>
          <p class="text-sm text-secondary mt-2 mb-0">Aucun ticket dans cette catégorie.</p>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
// ── ✅ Recherche et Filtres ────────────────────────────────────────────────
var currentFilter   = 'all';
var currentCategory = 'all';
var searchQuery     = '';

function updateDisplay() {
  var items    = document.querySelectorAll('.ticket-item');
  var visible  = 0;
  var noResult = document.getElementById('noFilterResult');

  items.forEach(function(item) {
    var matchesStatus   = (currentFilter === 'all') || 
                          (currentFilter === 'resolved' && item.classList.contains('filter-resolved')) ||
                          (currentFilter === 'in_progress' && item.classList.contains('filter-in_progress')) ||
                          (currentFilter === 'pending' && item.classList.contains('filter-pending'));
    
    var matchesCategory = (currentCategory === 'all') || item.classList.contains('cat-' + currentCategory);
    
    var title = item.querySelector('.ticket-title').textContent.toLowerCase();
    var desc  = item.querySelector('.ticket-desc').textContent.toLowerCase();
    var matchesSearch   = !searchQuery || title.includes(searchQuery) || desc.includes(searchQuery);

    if (matchesStatus && matchesCategory && matchesSearch) {
      item.style.display = '';
      visible++;
    } else {
      item.style.display = 'none';
    }
  });

  document.getElementById('ticketCount').textContent = visible + ' ticket(s)';
  noResult.style.display = (visible === 0) ? 'block' : 'none';
}

function searchTickets(val) {
  searchQuery = val.toLowerCase().trim();
  updateDisplay();
}

function filterByCategory(cat) {
  currentCategory = cat;
  var btn = document.getElementById('categoryFilterBtn');
  var labels = {
    'all': 'Toutes catégories',
    'incident_technique': '🔴 Incident technique',
    'integration_api': '🔵 Intégration API SMS',
    'facturation': '🟡 Facturation',
    'plateforme': '🟢 Plateforme L2T',
    'paiement_mobile': '🟠 Paiement Mobile',
    'autre': '⚪ Autre'
  };
  btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">filter_alt</i> ' + (labels[cat] || 'Filtrer');
  updateDisplay();
}

function applyFilter(filter) {
  currentFilter = filter;
  var label      = document.getElementById('filterLabel');
  var labelText  = document.getElementById('filterLabelText');

  // Mettre à jour les cards stats (active state)
  document.querySelectorAll('.stat-card').forEach(function(card) {
    card.style.border = '2px solid transparent';
    card.style.boxShadow = '';
    card.style.transform = '';
  });

  var labels = {
    'all':        'Tous les tickets',
    'pending':    'En attente',
    'in_progress':'En cours',
    'resolved':   'Résolus & Clôturés'
  };

  var activeCard = document.querySelector('.stat-card[data-filter="' + filter + '"]');
  if (activeCard) {
    activeCard.style.border = '2px solid ' + getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
    activeCard.style.boxShadow = '0 4px 15px rgba(102,126,234,0.15)';
    activeCard.style.transform = 'translateY(-2px)';
  }

  if (filter === 'all') {
    label.style.display = 'none';
  } else {
    label.style.display = 'block';
    labelText.textContent = 'Statut : ' + labels[filter];
  }

  updateDisplay();
  
  if (window.innerWidth < 768) {
    document.getElementById('ticketsContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// ── Clic sur les cards stats ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {

  document.querySelectorAll('.stat-card').forEach(function(card) {
    card.addEventListener('click', function() {
      applyFilter(this.getAttribute('data-filter'));
    });
  });

  // ── Auto-ouvrir depuis URL hash (notifications) ─────────────────────────
  var hash = window.location.hash;
  if (hash && hash.startsWith('#ticket-')) {
    var id   = hash.replace('#ticket-', '');
    var card = document.getElementById('ticket-' + id);
    if (card) {
      setTimeout(function() {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.style.transition = 'box-shadow 0.4s ease, background 0.4s ease';
        card.style.background = '#f0f4ff';
        card.style.boxShadow  = '0 0 0 3px #667eea44';
        var detail = document.getElementById('ticket-detail-' + id);
        if (detail && detail.classList.contains('d-none')) {
          detail.classList.remove('d-none');
          var ch = document.querySelector('.chevron-' + id);
          if (ch) ch.style.transform = 'rotate(180deg)';
        }
        setTimeout(function() {
          card.style.background = '';
          card.style.boxShadow  = '';
        }, 3000);
      }, 300);
    }
  }

  // ── Auto-appliquer filtre depuis URL ?filter=pending ────────────────────
  var urlParams = new URLSearchParams(window.location.search);
  var filterParam = urlParams.get('filter');
  if (filterParam && ['pending','in_progress','resolved'].includes(filterParam)) {
    applyFilter(filterParam);
  }
});
</script>

@endsection