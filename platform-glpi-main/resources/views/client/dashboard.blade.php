@extends('layouts.dashboard')
@section('title','Dashboard')
@section('page-title','Dashboard')

@section('content')

{{-- HEADER --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow"
               style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
            <span class="font-weight-bolder" style="font-size:22px;color:var(--color-primary);">
              {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </span>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">
              Bonjour, {{ auth()->user()->name }} 👋
            </h5>
            <p class="text-white text-sm mb-0 opacity-8">
              {{ now()->format('l, d F Y') }}
            </p>
          </div>
        </div>
        <a href="{{ route('tickets.create') }}"
           class="btn bg-white mb-0 d-none d-md-block" style="color:var(--color-primary); font-weight:600;">
          <i class="material-symbols-rounded me-1" style="font-size:18px;vertical-align:middle;">add</i>
          Nouveau Ticket
        </a>
      </div>
    </div>
  </div>
</div>

{{-- STATS --}}
<div class="row mb-4">
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2" style="font-size:32px;color:var(--color-primary);">confirmation_number</i>
      <h4 class="font-weight-bolder mb-0">{{ $totalTickets }}</h4>
      <p class="text-xs text-secondary mb-0">Total</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2 text-warning" style="font-size:32px;">schedule</i>
      <h4 class="font-weight-bolder mb-0 text-warning">{{ $openTickets }}</h4>
      <p class="text-xs text-secondary mb-0">En attente</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2 text-info" style="font-size:32px;">autorenew</i>
      <h4 class="font-weight-bolder mb-0 text-info">{{ $inProgressTickets }}</h4>
      <p class="text-xs text-secondary mb-0">En cours</p>
    </div>
  </div>
  <div class="col-6 col-md-3 mb-3">
    <div class="card text-center p-3 h-100">
      <i class="material-symbols-rounded mb-2 text-success" style="font-size:32px;">check_circle</i>
      <h4 class="font-weight-bolder mb-0 text-success">{{ $closedTickets }}</h4>
      <p class="text-xs text-secondary mb-0">Résolus</p>
    </div>
  </div>
</div>

{{-- CHART + RECENT TICKETS --}}
<div class="row">

  {{-- Donut Chart --}}
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="mb-0 font-weight-bold">Aperçu</h6>
        <p class="text-xs text-secondary mb-0">Répartition de vos tickets</p>
      </div>
      <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">

        {{-- ✅ FIX : wrapper avec hauteur fixe --}}
        <div style="position: relative; height: 220px; width: 220px;">
          <canvas id="clientChart"></canvas>
        </div>

        <div class="d-flex gap-3 mt-3 justify-content-center flex-wrap">
          <div class="text-center">
            <span class="badge bg-gradient-warning">{{ $openTickets }} En attente</span>
          </div>
          <div class="text-center">
            <span class="badge" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:white;">
              {{ $inProgressTickets }} En cours
            </span>
          </div>
          <div class="text-center">
            <span class="badge bg-gradient-success">{{ $closedTickets }} Résolus</span>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- Recent Tickets --}}
  <div class="col-lg-8 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 font-weight-bold">Derniers tickets</h6>
            <p class="text-xs text-secondary mb-0">Vos demandes récentes</p>
          </div>
          <a href="{{ route('tickets.create') }}"
             class="btn btn-sm mb-0 text-white"
             style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
            + Nouveau
          </a>
        </div>
      </div>
      <div class="card-body px-0 pb-2">
        @forelse($recentTickets as $ticket)
        @php
          $catLabels = [
            'incident_technique' => ['🔴','Incident'],
            'integration_api'    => ['🔵','API SMS'],
            'facturation'        => ['🟡','Facturation'],
            'plateforme'         => ['🟢','Plateforme'],
            'paiement_mobile'    => ['🟠','Paiement'],
            'autre'              => ['⚪','Autre'],
          ];
          $cat = $catLabels[$ticket->category] ?? ['⚪','Autre'];

          $statusData = [
            'pending'     => ['warning','En attente'],
            'in_progress' => ['info','En cours'],
            'resolved'    => ['success','Résolu'],
            'closed'      => ['secondary','Clôturé'],
            'local'       => ['warning','En attente'],
            'synced'      => ['success','Résolu'],
          ];
          $st = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu'];
        @endphp
        <a href="{{ route('tickets.show', $ticket->id) }}"
           class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between text-decoration-none"
           style="cursor:pointer; transition:background 0.15s;"
           onmouseover="this.style.background='rgba(0,0,0,0.03)'"
           onmouseout="this.style.background=''">
          <div class="d-flex align-items-center">
            <span class="badge text-white me-3"
                  style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));min-width:32px;">
              #{{ $ticket->id }}
            </span>
            <div>
              <p class="text-sm font-weight-bold mb-0" style="color:inherit;">{{ Str::limit($ticket->title, 40) }}</p>
              <p class="text-xs text-secondary mb-0">
                {{ $cat[0] }} {{ $cat[1] }} · {{ $ticket->created_at->format('d/m/Y') }}
                @if($ticket->solution)
                  · <span class="text-success font-weight-bold">✅ Répondu</span>
                @endif
              </p>
            </div>
          </div>
          <span class="badge bg-gradient-{{ $st[0] }} ms-2">{{ $st[1] }}</span>
        </a>
        @empty
        <div class="text-center py-5">
          <i class="material-symbols-rounded text-secondary" style="font-size:48px;">confirmation_number</i>
          <p class="text-secondary mt-2 mb-3">Aucun ticket pour le moment</p>
          <a href="{{ route('tickets.create') }}" class="btn btn-sm text-white"
             style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
            Créer mon premier ticket
          </a>
        </div>
        @endforelse

        @if($totalTickets > 5)
        <div class="text-center pt-3">
          <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary mb-0">
            Voir tous mes tickets →
          </a>
        </div>
        @endif
      </div>
    </div>
  </div>

</div>

@endsection

@push('page-scripts')
<script>
var ctx = document.getElementById('clientChart');
if (ctx) {
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['En attente', 'En cours', 'Résolus'],
      datasets: [{
        data: [{{ $openTickets }}, {{ $inProgressTickets }}, {{ $closedTickets }}],
        backgroundColor: ['#ffc107', getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim(), '#28a745'],
        borderWidth: 3,
        borderColor: '#fff',
        hoverOffset: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: { legend: { display: false } }
    }
  });
}
</script>
@endpush