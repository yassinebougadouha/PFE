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
          <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow">
            <i class="material-symbols-rounded" style="font-size:36px; color:var(--color-primary);">confirmation_number</i>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">Mes Tickets</h5>
            <p class="text-white text-sm mb-0 opacity-8">Suivez l'état de vos demandes de support</p>
          </div>
        </div>
        <a href="{{ route('tickets.create') }}"
           class="btn bg-white mb-0" style="color:var(--color-primary); font-weight:600;">
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
  $inprog   = $tickets->whereIn('sync_status',['in_progress','synced'])->count();
  $resolved = $tickets->whereIn('sync_status',['resolved','closed'])->count();
@endphp
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
            in_array($ticket->sync_status, ['in_progress', 'synced']) => 'filter-in_progress',
            default => 'filter-pending',
          };

          // ✅ Date corrigée — timezone Tunis
          $createdAt = $ticket->created_at->timezone('Africa/Tunis')->format('d/m/Y à H:i');
        @endphp

        {{-- TICKET CARD --}}
        <div class="px-4 py-3 border-bottom ticket-item {{ $filterClass }}"
             id="ticket-{{ $ticket->id }}">

          {{-- HEADER TICKET --}}
          <div class="d-flex align-items-start justify-content-between"
               style="cursor:pointer;"
               onclick="window.location.href='{{ route('tickets.show', $ticket->id) }}'"
               onmouseenter="this.parentElement.style.background=(document.documentElement.getAttribute('data-bs-theme')==='dark' ? 'rgba(255,255,255,0.04)' : '#f8f9ff')"
               onmouseleave="this.parentElement.style.background='transparent'">

            <div class="d-flex align-items-start flex-grow-1">
              <div class="me-3 flex-shrink-0">
                <span class="badge text-white text-xs"
                      style="background: linear-gradient(135deg,var(--color-primary),var(--color-secondary)); min-width:40px;">
                  #{{ $loop->iteration }}
                </span>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                  <p class="text-sm font-weight-bold mb-0">{{ $ticket->title }}</p>
                  <span class="badge text-dark text-xs"
                        style="background:#f0f4ff; border:1px solid #d0d8f0; font-size:10px;">
                    {{ $cat[0] }} {{ $cat[1] }}
                  </span>
                  @if($commentCount > 0)
                  <span class="badge bg-gradient-info text-xs">
                    <i class="material-symbols-rounded me-1" style="font-size:10px;vertical-align:middle;">chat</i>
                    {{ $commentCount }} commentaire(s)
                  </span>
                  @endif
                </div>
                <p class="text-xs text-secondary mb-1">{{ Str::limit($ticket->description, 80) }}</p>
                <div class="d-flex align-items-center gap-3">
                  {{-- ✅ Date avec timezone corrigé --}}
                  <span class="text-xs text-secondary">
                    <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">calendar_today</i>
                    {{ $createdAt }}
                  </span>
                  @if($ticket->solution)
                  <span class="text-xs text-success font-weight-bold">
                    <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">mark_email_read</i>
                    Réponse disponible
                  </span>
                  @endif
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center ms-3 flex-shrink-0 gap-2">
              <span class="badge bg-gradient-{{ $st[0] }}">
                <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">{{ $st[2] }}</i>
                {{ $st[1] }}
              </span>

              @if($isPending)
              <form method="POST" action="{{ route('tickets.destroy', $ticket->id) }}"
                    onclick="event.stopPropagation()"
                    onsubmit="return confirm('Supprimer ce ticket définitivement ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger mb-0 px-2 py-1" title="Supprimer">
                  <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">delete</i>
                </button>
              </form>
              @endif

              @if($ticket->sync_status === 'failed')
              <form method="POST" action="{{ route('tickets.retry', $ticket->id) }}"
                    onclick="event.stopPropagation()">
                @csrf
                <button type="submit"
                        class="btn btn-sm mb-0 px-2 py-1"
                        style="background:#fff8e1;color:#f57c00;border:1px solid #ffcc02;"
                        title="Réessayer la synchronisation">
                  <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
                </button>
              </form>
              @endif

              <i class="material-symbols-rounded text-secondary"
                 style="font-size:20px; transition:transform 0.2s;">chevron_right</i>
            </div>
          </div>

          {{-- DÉTAIL EXPANDABLE --}}
          <div id="ticket-detail-{{ $ticket->id }}" class="d-none mt-3">
            <div class="border-radius-lg p-3" style="background:#f8f9ff; border:1px solid #e0e7ff;">

              <div class="mb-3">
                <p class="text-xs font-weight-bold text-uppercase text-secondary mb-1">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">description</i>
                  Description complète
                </p>
                <p class="text-sm mb-0">{{ $ticket->description }}</p>
              </div>

              @if($ticket->attachments)
              @php $files = json_decode($ticket->attachments, true) ?? []; @endphp
              @if(count($files) > 0)
              <div class="mb-3">
                <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">attach_file</i>
                  Pièces jointes
                </p>
                @foreach($files as $file)
                <a href="{{ asset('storage/' . $file) }}" target="_blank"
                   class="badge bg-gradient-secondary me-1 mb-1">
                  📎 {{ basename($file) }}
                </a>
                @endforeach
              </div>
              @endif
              @endif

              @if($ticket->solution)
              <div class="border-radius-md p-3 mb-3" style="background:#e8f5e9; border-left:3px solid #4caf50;">
                <p class="text-xs font-weight-bold text-uppercase mb-1" style="color:#2e7d32;">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">support_agent</i>
                  Réponse de notre équipe support
                </p>
                <p class="text-sm mb-0">{{ $ticket->solution }}</p>
              </div>
              @else
              <div class="border-radius-md p-3 mb-3" style="background:#fff8e1; border-left:3px solid #ffc107;">
                <p class="text-xs text-secondary mb-0">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">hourglass_empty</i>
                  Notre équipe est en train de traiter votre demande.
                </p>
              </div>
              @endif

              @if($ticket->comments->count() > 0)
              <div class="mb-3">
                <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">chat</i>
                  Commentaires ({{ $ticket->comments->count() }})
                </p>
                @foreach($ticket->comments as $comment)
                <div class="d-flex mb-2">
                  <div class="avatar avatar-sm me-2 flex-shrink-0"
                       style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <span class="text-white text-xs font-weight-bold">
                      {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                    </span>
                  </div>
                  <div class="flex-grow-1">
                    <div class="border-radius-md p-2" style="background:#eff6ff;">
                      <div class="d-flex justify-content-between mb-1">
                        <span class="text-xs font-weight-bold" style="color:#3730a3;">{{ $comment->user->name }}</span>
                        {{-- ✅ Date commentaire corrigée --}}
                        <span class="text-xs text-secondary">
                          {{ $comment->created_at->timezone('Africa/Tunis')->format('d/m/Y H:i') }}
                        </span>
                      </div>
                      <p class="text-xs mb-0">{{ $comment->content }}</p>
                      @if($comment->attachment_path)
                      <a href="{{ asset('storage/' . $comment->attachment_path) }}" target="_blank"
                         class="text-xs text-primary">
                        📎 Voir le fichier joint
                      </a>
                      @endif
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
              @endif

              @if(!in_array($ticket->sync_status, ['closed']))
              <div class="mt-3">
                <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">
                  <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">add_comment</i>
                  Ajouter un commentaire
                </p>
                <form method="POST"
                      action="{{ route('tickets.comment', $ticket->id) }}"
                      enctype="multipart/form-data">
                  @csrf
                  <div class="mb-2">
                    <textarea name="content" rows="3" class="form-control form-control-sm"
                              placeholder="Décrivez votre problème complémentaire ou informations supplémentaires..."
                              required></textarea>
                  </div>
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <label class="btn btn-sm btn-outline-secondary mb-0 px-2 py-1" style="cursor:pointer;">
                        <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">attach_file</i>
                        Joindre un fichier
                        <input type="file" name="attachment" class="d-none"
                               onchange="document.getElementById('fileName-{{ $ticket->id }}').textContent = this.files[0]?.name || ''">
                      </label>
                      <small id="fileName-{{ $ticket->id }}" class="text-xs text-secondary ms-2"></small>
                    </div>
                    <button type="submit" class="btn btn-sm text-white mb-0"
                            style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
                      <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">send</i>
                      Envoyer
                    </button>
                  </div>
                </form>
              </div>
              @else
              <div class="border-radius-md p-2 mt-2" style="background:#f1f5f9;border-left:3px solid #94a3b8;">
                <p class="text-xs text-secondary mb-0">
                  <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">lock</i>
                  Ce ticket est clôturé. Créez un nouveau ticket si nécessaire.
                </p>
              </div>
              @endif

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
// ── Toggle ticket detail ────────────────────────────────────────────────────
function toggleTicket(id) {
  // Redirected to show page — accordion disabled
  window.location.href = '/tickets/' + id;
  return;
  var detail  = document.getElementById('ticket-detail-' + id);
  var chevron = document.querySelector('.chevron-' + id);
  if (detail.classList.contains('d-none')) {
    detail.classList.remove('d-none');
    if (chevron) chevron.style.transform = 'rotate(180deg)';
  } else {
    detail.classList.add('d-none');
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  }
}

// ── ✅ Filtre par statut au clic sur les cards stats ────────────────────────
var currentFilter = 'all';

function applyFilter(filter) {
  currentFilter = filter;
  var items      = document.querySelectorAll('.ticket-item');
  var label      = document.getElementById('filterLabel');
  var labelText  = document.getElementById('filterLabelText');
  var countEl    = document.getElementById('ticketCount');
  var noResult   = document.getElementById('noFilterResult');

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
    activeCard.style.boxShadow = '0 4px 15px rgba(102,126,234,0.25)';
    activeCard.style.transform = 'translateY(-2px)';
  }

  // Afficher/masquer le label filtre
  if (filter === 'all') {
    label.style.display = 'none';
  } else {
    label.style.display = 'block';
    labelText.textContent = 'Filtre : ' + labels[filter];
  }

  // Filtrer les tickets
  var visible = 0;
  items.forEach(function(item) {
    var show = false;
    if (filter === 'all') {
      show = true;
    } else if (filter === 'resolved') {
      show = item.classList.contains('filter-resolved');
    } else if (filter === 'in_progress') {
      show = item.classList.contains('filter-in_progress');
    } else if (filter === 'pending') {
      show = item.classList.contains('filter-pending');
    }

    if (show) {
      item.style.display = '';
      visible++;
    } else {
      item.style.display = 'none';
    }
  });

  // Compteur
  countEl.textContent = visible + ' ticket(s)';

  // Message si aucun résultat
  noResult.style.display = (visible === 0) ? 'block' : 'none';

  // Scroll vers la liste
  document.getElementById('ticketsContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
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