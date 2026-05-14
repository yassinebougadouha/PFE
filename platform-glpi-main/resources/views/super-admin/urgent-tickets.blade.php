@extends('layouts.dashboard')
@section('title', 'Tickets urgents (SLA)')
@section('page-title', 'Tickets urgents')

@section('content')

<style>
.sla-bar-bg {
    background: #f0f0f0;
    border-radius: 6px;
    height: 8px;
    width: 100%;
}
.sla-bar-fill {
    height: 8px;
    border-radius: 6px;
    transition: width .4s ease;
}
.priority-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
}
.urgent-card {
    transition: transform .15s ease, box-shadow .15s ease;
    cursor: pointer;
}
.urgent-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.12) !important;
}
</style>

<div class="container-fluid py-3">

  {{-- Header --}}
  <div class="card mb-4" style="background:linear-gradient(135deg,#e53e3e,#c53030);border:none;border-radius:16px;">
    <div class="card-body d-flex align-items-center justify-content-between px-4 py-3">
      <div class="d-flex align-items-center gap-3">
        <i class="material-symbols-rounded text-white" style="font-size:36px;">priority_high</i>
        <div>
          <h5 class="text-white mb-0 fw-bold">Tickets urgents — Risque SLA</h5>
          <p class="text-white mb-0" style="opacity:.8;font-size:12px;">
            {{ $tickets->count() }} ticket(s) en dépassement ou à risque SLA
          </p>
        </div>
      </div>
      @php
        $backRoute = auth()->user()->role === 'admin'
          ? route('admin.dashboard')
          : route('super-admin.dashboard');
      @endphp
      <a href="{{ $backRoute }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);">
        ← Retour Dashboard
      </a>
    </div>
  </div>

  {{-- Liste --}}
  @if($tickets->isEmpty())
    <div class="card text-center py-5">
      <i class="material-symbols-rounded text-success mx-auto d-block" style="font-size:48px;">check_circle</i>
      <h6 class="mt-3 text-secondary">Aucun ticket urgent en ce moment 🎉</h6>
    </div>
  @else
    <div class="row g-3">
      @foreach($tickets as $t)
        @php
          $sla = min($t->sla_used, 100);
          $barColor = $sla >= 100 ? '#e53e3e' : ($sla >= 80 ? '#ed8936' : '#48bb78');
          $priorityLabels = [5=>'Critique',4=>'Haute',3=>'Moyenne',2=>'Basse',1=>'Très basse'];
          $priorityColors = [5=>'#e53e3e',4=>'#ed8936',3=>'#ecc94b',2=>'#4299e1',1=>'#a0aec0'];
          $label = $priorityLabels[$t->priority] ?? '?';
          $color = $priorityColors[$t->priority] ?? '#888';
          $isAdmin = auth()->user()->role === 'admin';
          $ticketUrl = $isAdmin
            ? route('admin.tickets.show', $t->id)
            : route('super-admin.decision-engine') . '?ticket=' . $t->id;
        @endphp
        <div class="col-12">
          <div class="card shadow-sm border-0 urgent-card"
               style="border-left: 4px solid {{ $color }} !important; border-radius:12px;"
               onclick="window.location='{{ $ticketUrl }}'">
            <div class="card-body px-4 py-3">
              <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">

                {{-- Left info --}}
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-muted" style="font-size:12px;font-weight:600;">#{{ $t->id }}</span>
                    <span class="priority-badge text-white" style="background:{{ $color }};">{{ $label }}</span>
                    @if($sla >= 100)
                      <span class="priority-badge text-white" style="background:#e53e3e;">SLA dépassé</span>
                    @elseif($t->sla_risk)
                      <span class="priority-badge" style="background:#fed7d7;color:#c53030;">Risque SLA</span>
                    @endif
                  </div>
                  <h6 class="mb-1 fw-bold" style="font-size:14px;">{{ $t->title }}</h6>
                  <p class="mb-0 text-secondary" style="font-size:12px;">
                    <i class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;">person</i>
                    {{ $t->client }}
                    &nbsp;·&nbsp;
                    <i class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;">schedule</i>
                    Ouvert depuis {{ $t->hours_open }}h
                    &nbsp;·&nbsp; SLA : {{ $t->sla_limit }}h
                  </p>
                </div>

                {{-- Right: SLA bar + bouton --}}
                <div style="min-width:190px;text-align:right;">
                  @if($sla >= 100)
                    @php
                      $hoursExceeded = abs(round(($t->sla_used - 100) / 100 * $t->sla_limit, 1));
                    @endphp
                    <span style="font-size:13px;font-weight:700;color:#e53e3e;">
                      +{{ $hoursExceeded }}h dépassé
                    </span>
                  @else
                    @php
                      $hoursLeft = round($t->sla_limit - ($t->sla_used / 100 * $t->sla_limit), 1);
                    @endphp
                    <span style="font-size:13px;font-weight:700;color:{{ $barColor }};">
                      {{ $t->sla_used }}% — {{ $hoursLeft }}h restantes
                    </span>
                  @endif
                  <div class="sla-bar-bg mt-1">
                    <div class="sla-bar-fill" style="width:{{ $sla }}%;background:{{ $barColor }};"></div>
                  </div>
                  <div class="mt-2">
                    <a href="{{ $ticketUrl }}"
                       class="btn btn-sm text-white"
                       style="background:#667eea;font-size:11px;padding:3px 10px;border-radius:8px;"
                       onclick="event.stopPropagation();">
                      Voir le ticket →
                    </a>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>
@endsection