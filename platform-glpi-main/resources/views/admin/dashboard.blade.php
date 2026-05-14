@extends('layouts.dashboard')
@section('title','Admin Dashboard')
@section('page-title','Admin Dashboard')

@section('content')

{{-- Header Card --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-xl bg-white border-radius-lg p-2 me-3 shadow">
            <i class="material-symbols-rounded" style="font-size:40px; color:var(--color-primary);">support_agent</i>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">Tableau de bord Admin</h5>
            <p class="text-white text-sm mb-0 opacity-8">
              Bienvenue, <strong>{{ auth()->user()->name }}</strong> 👋
            </p>
          </div>
        </div>
        <div class="text-end d-none d-md-block">
          <p class="text-white text-sm mb-0 opacity-8">{{ now()->format('l, d F Y') }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.dash-card {
  border-radius: 24px;
  padding: 24px;
  color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 140px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.dash-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
}
.dash-card::before {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(5px);
}
.dash-card-num {
  font-size: 42px;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 8px;
  letter-spacing: -0.03em;
}
.dash-card-label {
  font-size: 14px;
  opacity: 0.9;
  font-weight: 600;
  letter-spacing: -0.01em;
}
.dash-card-icon {
  font-size: 56px;
  opacity: 0.2;
  position: absolute;
  right: 20px;
  bottom: 20px;
  z-index: 0;
}
.dash-card-link {
  font-size: 12px;
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 12px;
  border-radius: 10px;
  backdrop-filter: blur(10px);
  font-weight: 700;
  text-decoration: none;
  color: #fff;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 16px;
  width: fit-content;
  transition: all 0.2s ease;
}
.dash-card-link:hover {
  background: rgba(255, 255, 255, 0.3);
  color: #fff;
}
.dash-card-sub{font-size:11px;opacity:.7;margin-top:3px;display:flex;align-items:center;gap:5px;}
.dash-card-content{position:relative;z-index:1;}
</style>

<div class="row mb-4 g-3">

  {{-- CARD 1 : Réclamations externes (clients non classifiés) --}}
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#7C3AED 0%,#A78BFA 100%);">
        <i class="material-symbols-rounded dash-card-icon">mark_email_unread</i>
        <div class="dash-card-content">
          <div class="dash-card-num">{{ $reclamationsExternes }}</div>
          <div class="dash-card-label">Réclamations externes</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;">🟠 Non classifiés</span>
          </div>
          <a href="{{ route('admin.clients') }}?client_type=user" class="dash-card-link">Voir clients non classifiés →</a>
        </div>
      </div>
    </div>
  </div>

  {{-- CARD 2 : Clients actifs (client_type = 'client') --}}
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#0284C7 0%,#38BDF8 100%);">
        <i class="material-symbols-rounded dash-card-icon">group</i>
        <div class="dash-card-content">
          <div class="dash-card-num">{{ $clientsActifs }}</div>
          <div class="dash-card-label">Clients actifs</div>
          <div class="dash-card-sub">
            <a href="{{ route('admin.clients') }}?client_type=client"
               style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;color:white;text-decoration:none;">
              🟣 {{ $countClient }} classifiés
            </a>
          </div>
          <a href="{{ route('admin.clients') }}?client_type=client" class="dash-card-link">Voir les clients actifs →</a>
        </div>
      </div>
    </div>
  </div>

  {{-- CARD 3 : Tickets aujourd'hui --}}
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#059669 0%,#34D399 100%);">
        <i class="material-symbols-rounded dash-card-icon">today</i>
        <div class="dash-card-content">
          <div class="dash-card-num">{{ $ticketsAujourdhui }}</div>
          <div class="dash-card-label">Tickets aujourd'hui</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;">{{ now()->format('d/m/Y') }}</span>
          </div>
          <a href="{{ route('admin.tickets') }}?date_from={{ now()->format('Y-m-d') }}&date_to={{ now()->format('Y-m-d') }}" class="dash-card-link">Voir les tickets du jour →</a>
        </div>
      </div>
    </div>
  </div>

  {{-- CARD 4 : Tickets en attente --}}
  <div class="col-xl-3 col-sm-6">
    <div class="card p-0 border-0 overflow-hidden" style="border-radius:18px;">
      <div class="dash-card" style="background:linear-gradient(135deg,#DC2626 0%,#F87171 100%);">
        <i class="material-symbols-rounded dash-card-icon">pending_actions</i>
        <div class="dash-card-content">
          <div class="dash-card-num">{{ $openTickets }}</div>
          <div class="dash-card-label">Tickets en attente</div>
          <div class="dash-card-sub">
            <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:10px;">⏳ À traiter</span>
          </div>
          <a href="{{ route('admin.tickets') }}?status=pending" class="dash-card-link">Voir les tickets en attente →</a>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- Charts + Quick Stats + Urgent --}}
<div class="row mb-4">

  {{-- Urgent Tickets Card --}}
  <div class="col-lg-4 mb-4">
    <a href="{{ route('admin.urgent-tickets') }}" style="text-decoration:none;color:inherit;">
      <div class="card h-100">
        <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 font-weight-bold">
              <i class="material-symbols-rounded" style="font-size:18px;vertical-align:middle;color:#ef4444;">priority_high</i>
              Tickets urgents
            </h6>
            <p class="text-sm text-muted mb-0">Alerte SLA</p>
          </div>
          <span class="badge badge-sm" style="background:#ef4444;color:white;">
            {{ $urgentTickets->count() ?? 0 }}
          </span>
        </div>
        <div class="card-body p-3" style="max-height:300px;overflow-y:auto;">
          @forelse($urgentTickets->take(4) as $ut)
            <div class="d-flex align-items-center justify-content-between mb-2 p-2"
                 style="border-radius:10px;background:rgba(239,68,68,.06);border-left:3px solid {{ $ut->sla_breached ? '#ef4444' : ($ut->sla_risk ? '#f59e0b' : '#22c55e') }};">
              <div class="d-flex flex-column" style="min-width:0;">
                <span style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  #{{ $ut->id }} — {{ $ut->title }}
                </span>
                <span style="font-size:10px;color:#64748b;">
                  @if($ut->sla_breached)
                    <span style="color:#ef4444;font-weight:700;">SLA dépassé!</span> +{{ abs($ut->sla_hours_left) }}h
                  @elseif($ut->sla_risk)
                    <span style="color:#f59e0b;font-weight:700;">SLA!</span> {{ $ut->sla_hours_left }}h restantes
                  @else
                    {{ $ut->sla_hours_left }}h restantes
                  @endif
                  · {{ $ut->sla_hours_open }}h ouvert
                </span>
              </div>
              <span class="badge badge-sm ms-2" style="flex-shrink:0;background:
                {{ $ut->priority >= 5 ? '#dc2626' : ($ut->priority >= 4 ? '#ef4444' : '#f59e0b') }};color:white;">
                P{{ $ut->priority }}
              </span>
            </div>
          @empty
            <div class="text-center py-4">
              <span style="font-size:32px;">🎉</span>
              <p class="text-sm text-muted mt-2 mb-0">Aucun ticket urgent</p>
            </div>
          @endforelse
        </div>
        <div class="card-footer text-center p-2 border-0" style="background:transparent;">
          <span class="text-sm" style="color:var(--color-primary);font-weight:600;">
            Voir tous les tickets urgents →
          </span>
        </div>
      </div>
    </a>
  </div>

  {{-- Ticket Status Chart --}}
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 font-weight-bold">Répartition des tickets</h6>
            <p class="text-sm text-muted mb-0">Par statut</p>
          </div>
          <span class="badge badge-sm" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%); color:white;">
            Total: {{ $totalTickets }}
          </span>
        </div>
      </div>
      <div class="card-body p-3">
        <div class="chart">
          <canvas id="statusChart" height="200"></canvas>
        </div>
        <div class="row mt-3 g-2">
          <div class="col-4 text-center">
            <a href="{{ route('admin.tickets') }}?status=pending"
               class="badge text-white w-100 text-decoration-none d-block"
               style="background:#ef4444;padding:6px 4px;border-radius:12px;font-size:11px;">
              ⏳ En attente
            </a>
          </div>
          <div class="col-4 text-center">
            <a href="{{ route('admin.tickets') }}?status=in_progress"
               class="badge text-white w-100 text-decoration-none d-block"
               style="background:#f59e0b;padding:6px 4px;border-radius:12px;font-size:11px;">
              🔄 En cours
            </a>
          </div>
          <div class="col-4 text-center">
            <a href="{{ route('admin.tickets') }}?status=resolved"
               class="badge text-white w-100 text-decoration-none d-block"
               style="background:#22c55e;padding:6px 4px;border-radius:12px;font-size:11px;">
              ✅ Résolu
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- Monthly Chart --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="mb-0 font-weight-bold">Tickets par mois</h6>
        <p class="text-xs text-secondary mb-0">6 derniers mois</p>
      </div>
      <div class="card-body p-3">
        <div style="position:relative;height:220px;">
          <canvas id="monthlyChart" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Recent Tickets Table --}}
<style>
.dash-tk-table{width:100%;border-collapse:collapse;}
.dash-tk-table thead tr{background:var(--bs-tertiary-bg,#f8fafc);}
.dash-tk-table thead th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:10px 14px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);white-space:nowrap;}
.dash-tk-table tbody tr{border-bottom:1px solid var(--bs-border-color,#f1f5f9);transition:background .12s;cursor:pointer;}
.dash-tk-table tbody tr:hover{background:color-mix(in srgb,var(--color-primary) 4%,transparent);}
.dash-tk-table tbody tr:last-child{border-bottom:none;}
.dash-tk-table td{padding:12px 14px;vertical-align:middle;}
.dash-ctype{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;}
.dash-ctype-client{background:#F5F3FF;color:#7C3AED;}
.dash-ctype-new{background:#FFF7ED;color:#C2410C;}
.dash-prio{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap;}
.dash-prio-1{background:#f1f5f9;color:#475569;}
.dash-prio-2{background:#e0f2fe;color:#0369a1;}
.dash-prio-3{background:#fef3c7;color:#b45309;}
.dash-prio-4{background:#fee2e2;color:#b91c1c;}
.dash-prio-5{background:#1e1b4b;color:#fff;}
.dash-stbadge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;}
.dash-st-pending{background:#fef3c7;color:#b45309;}
.dash-st-inprogress{background:#dbeafe;color:#1d4ed8;}
.dash-st-resolved{background:#d1fae5;color:#065f46;}
.dash-st-closed{background:#f1f5f9;color:#475569;}
.dash-tk-replied{background:#d1fae5;color:#065f46;font-size:9px;font-weight:700;padding:2px 6px;border-radius:99px;display:inline-block;margin-top:3px;}
.dash-btn-reply{display:inline-flex;align-items:center;gap:5px;padding:6px 16px;border-radius:12px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;text-decoration:none;transition:opacity .15s;white-space:nowrap;}
.dash-btn-reply:hover{opacity:.85;color:#fff;}
</style>

<div class="row">
  <div class="col-12">
    <div class="card" style="border-radius:14px;border:1px solid var(--bs-border-color,#e2e8f0);overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.04);">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);">
        <div>
          <h6 style="margin:0;font-weight:700;font-size:14px;">Derniers tickets</h6>
          <p class="text-sm text-muted mb-0">Tickets récents de tous les clients</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span style="background:var(--bs-tertiary-bg,#f1f5f9);color:#64748b;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">
            {{ $recentTickets->count() }} ticket(s)
          </span>
          <a href="{{ route('admin.tickets') }}" class="dash-btn-reply">
            <i class="material-symbols-rounded" style="font-size:15px;">open_in_new</i>Voir tous
          </a>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="dash-tk-table">
          <thead>
            <tr>
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
              $cat = $catLabels[$ticket->category] ?? ['⚪', $ticket->category ?? 'Autre'];
              $p = $ticket->priority ?? 3;
              $pLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];
              $ct = $ticket->user?->client_type;
              $ctBadge = $ct === 'client'
                ? '<span class="dash-ctype dash-ctype-client">🟣 Client</span>'
                : '<span class="dash-ctype dash-ctype-new">🟠 Non classifié</span>';
              $stMap = [
                'pending'     => ['dash-st-pending','En attente','schedule'],
                'in_progress' => ['dash-st-inprogress','En cours','autorenew'],
                'resolved'    => ['dash-st-resolved','Résolu','check_circle'],
                'closed'      => ['dash-st-closed','Clôturé','lock'],
                'synced'      => ['dash-st-pending','Sync','sync'],
                'failed'      => ['dash-st-pending','Erreur','error'],
              ];
              $st = $stMap[$ticket->sync_status] ?? ['dash-st-pending','Inconnu','help'];
            @endphp
            <tr onclick="window.location='{{ route('admin.tickets.show', $ticket->id) }}'">
              <td><span style="font-size:11px;font-weight:700;color:var(--color-primary);">#{{ $ticket->id }}</span></td>
              <td style="max-width:220px;">
                <p style="font-size:12px;font-weight:600;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Str::limit($ticket->title, 36) }}</p>
                <p style="font-size:11px;color:#94a3b8;margin:0;">{{ Str::limit($ticket->description, 44) }}</p>
                @if($ticket->solution)<span class="dash-tk-replied">✅ Répondu</span>@endif
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:7px;">
                  @if($ticket->user?->avatar)
                    <img src="{{ asset('storage/' . $ticket->user->avatar) }}" style="width:26px;height:26px;border-radius:50%;object-fit:cover;border:1.5px solid #e2e8f0;flex-shrink:0;" alt="">
                  @else
                    <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                      <span style="font-size:9px;font-weight:700;color:#fff;">{{ strtoupper(substr($ticket->user->name ?? 'U', 0, 2)) }}</span>
                    </div>
                  @endif
                  <span style="font-size:12px;color:#64748b;">{{ $ticket->user->name ?? 'N/A' }}</span>
                </div>
              </td>
              <td>{!! $ctBadge !!}</td>
              <td><span style="font-size:12px;">{{ $cat[0] }} {{ $cat[1] }}</span></td>
              <td style="text-align:center;"><span class="dash-prio dash-prio-{{ $p }}">{{ $pLabels[$p] ?? 'Moyenne' }}</span></td>
              <td style="text-align:center;">
                <span class="dash-stbadge {{ $st[0] }}">
                  <i class="material-symbols-rounded" style="font-size:11px;vertical-align:middle;">{{ $st[2] }}</i>
                  {{ $st[1] }}
                </span>
              </td>
              <td style="text-align:center;"><span style="font-size:11px;color:#94a3b8;">{{ $ticket->created_at->format('d/m/Y') }}</span></td>
              <td style="text-align:center;" onclick="event.stopPropagation()">
                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="dash-btn-reply">
                  <i class="material-symbols-rounded" style="font-size:15px;">reply</i>Répondre
                </a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9" style="text-align:center;padding:40px;">
                <i class="material-symbols-rounded text-secondary" style="font-size:48px;">confirmation_number</i>
                <p class="text-secondary mt-2">Aucun ticket pour le moment</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection

@push('page-scripts')
<script>
  var ctx = document.getElementById("statusChart");
  if (ctx) {
    var _chartDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var _tickClr   = _chartDark ? '#64748b' : '#aaa';
    var _gridClr   = _chartDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';

    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ["En attente", "En cours", "Résolus"],
        datasets: [{
          data: [{{ $openTickets }}, {{ $inProgressTickets }}, {{ $closedTickets }}],
          backgroundColor: ["#ef4444", "#f59e0b", "#22c55e"],
          borderWidth: 3, borderColor: "#fff", hoverOffset: 8
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(item) { return ' ' + item.label + ' : ' + item.raw + ' ticket(s)'; }
            }
          }
        },
        cutout: '60%'
      }
    });
  }

  var ctxM = document.getElementById('monthlyChart');
  if (ctxM && typeof Chart !== 'undefined') {
    var _chartDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var _tickClr   = _chartDark ? '#64748b' : '#aaa';
    var _gridClr   = _chartDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
    new Chart(ctxM, {
      type: 'bar',
      data: {
        labels: {!! json_encode(array_column($ticketsByMonth, 'month')) !!},
        datasets: [{
          label: 'Tickets',
          data: {!! json_encode(array_column($ticketsByMonth, 'count')) !!},
          backgroundColor: 'rgba(102,126,234,0.5)',
          borderColor: '#667eea',
          borderWidth: 2,
          borderRadius: 4,
          barPercentage: 0.6
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: _tickClr, stepSize: 1 },
            grid: { color: _gridClr }
          },
          x: {
            ticks: { color: _tickClr },
            grid: { display: false }
          }
        }
      }
    });
  }
</script>
@endpush