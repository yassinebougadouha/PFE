@extends('layouts.dashboard')
@section('title','Logs & Audit')
@section('page-title','Logs & Audit')

@section('content')

{{-- Header --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card p-4" style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);border:none;box-shadow:0 10px 30px -5px rgba(0,0,0,0.2);">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center">
          <div class="bg-white border-radius-lg p-3 me-4 shadow-sm d-flex align-items-center justify-content-center" style="width:72px;height:72px;border-radius:20px !important;">
            <i class="material-symbols-rounded" style="font-size:42px;color:#0f172a;">policy</i>
          </div>
          <div>
            <h4 class="text-white font-weight-bolder mb-1">Logs & Audit</h4>
            <p class="text-white text-sm mb-0 opacity-9" style="font-weight:500;">Surveillance complète des activités du système en temps réel</p>
          </div>
        </div>
        {{-- Stats rapides --}}
        <div class="d-none d-md-flex gap-4">
          <div class="text-center text-white">
            <h4 class="mb-0 font-weight-bolder" style="font-size:28px;">{{ $totalLogs }}</h4>
            <p class="text-xs opacity-7 mb-0 font-weight-bold uppercase tracking-wider">Total</p>
          </div>
          <div class="text-center text-white">
            <h4 class="mb-0 font-weight-bolder text-danger" style="font-size:28px;">{{ $failedLogs }}</h4>
            <p class="text-xs opacity-7 mb-0 font-weight-bold uppercase tracking-wider">Échecs</p>
          </div>
          <div class="text-center text-white">
            <h4 class="mb-0 font-weight-bolder text-warning" style="font-size:28px;">{{ $todayLogs }}</h4>
            <p class="text-xs opacity-7 mb-0 font-weight-bold uppercase tracking-wider">Aujourd'hui</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Stats Cards --}}
<div class="row mb-4">
  @foreach([
    ['LOGIN','Connexions','login','info',$statsByAction['LOGIN'] ?? 0],
    ['CREATE','Créations','add_circle','success',($statsByAction['CREATE'] ?? 0) + ($statsByAction['CREATE TICKET'] ?? 0)],
    ['UPDATE','Modifications','edit','warning',($statsByAction['UPDATE'] ?? 0) + ($statsByAction['UPDATE TICKET'] ?? 0) + ($statsByAction['UPDATE SETTINGS'] ?? 0)],
    ['DELETE','Suppressions','delete','danger',($statsByAction['DELETE'] ?? 0) + ($statsByAction['DELETE USER'] ?? 0) + ($statsByAction['DELETE TICKET'] ?? 0)],
  ] as [$action,$label,$icon,$color,$count])
  <div class="col-xl-3 col-sm-6 mb-3">
    <div class="card">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="text-sm mb-0 font-weight-bold text-uppercase">{{ $label }}</p>
            <h4 class="mb-0 font-weight-bolder">{{ $count }}</h4>
          </div>
          <div class="icon icon-shape bg-gradient-{{ $color }} shadow border-radius-md text-center">
            <i class="material-symbols-rounded text-white" style="font-size:24px;line-height:48px;">{{ $icon }}</i>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- ── Modern search styles (logs) ── --}}
<style>
.logs-search-bar {
  display: flex; align-items: center; gap: 12px;
  background: #f8fafc; border: 2px solid #f1f5f9; border-radius: 16px;
  padding: 12px 16px; transition: all 0.2s ease;
  margin-bottom: 20px;
}
.logs-search-bar:focus-within {
  border-color: #1e293b;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(30, 41, 59, 0.1);
}
.logs-search-bar input {
  border: none; outline: none; background: transparent;
  font-size: 14px; color: #1e293b; width: 100%; font-weight: 500;
}
.logs-search-bar input::placeholder { color: #94a3b8; }
.logs-search-bar .si { color: #94a3b8; flex-shrink:0; font-size:20px; }
.logs-search-bar .kbd {
  flex-shrink:0; font-size:10px; font-weight:700; color:#94a3b8;
  background:#fff; border:1px solid #e2e8f0; border-radius:6px;
  padding:2px 8px; font-family:monospace; box-shadow:0 1px 2px rgba(0,0,0,0.05);
}
</style>

{{-- Filters --}}
<div class="card mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('super-admin.logs') }}" id="filterForm">

      {{-- Smart search --}}
      <div class="logs-search-bar">
        <i class="material-symbols-rounded si">manage_search</i>
        <input type="text" name="search" id="logsSearch"
               placeholder="Rechercher par nom, action, ticket, IP, module…"
               value="{{ request('search') }}"
               autocomplete="off">
        <span class="kbd">/ ou Ctrl+K</span>
      </div>

      <div class="row g-2 align-items-end">

        {{-- Date début --}}
        <div class="col-md-2">
          <label class="form-label text-xs font-weight-bold mb-1">📅 Date début</label>
          <input type="date" name="date_from" class="form-control form-control-sm"
                 value="{{ request('date_from') }}">
        </div>

        {{-- Date fin --}}
        <div class="col-md-2">
          <label class="form-label text-xs font-weight-bold mb-1">📅 Date fin</label>
          <input type="date" name="date_to" class="form-control form-control-sm"
                 value="{{ request('date_to') }}">
        </div>

        {{-- Action --}}
        <div class="col-md-2">
          <label class="form-label text-xs font-weight-bold mb-1">🏷️ Action</label>
          <select name="action" class="form-select form-select-sm">
            <option value="">Toutes</option>
            @foreach($actions as $action)
              <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                {{ $action }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Module --}}
        <div class="col-md-1">
          <label class="form-label text-xs font-weight-bold mb-1">📦 Module</label>
          <select name="module" class="form-select form-select-sm">
            <option value="">Tous</option>
            @foreach(['Auth','Tickets','Users','Settings','System'] as $mod)
              <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
            @endforeach
          </select>
        </div>

        {{-- Status --}}
        <div class="col-md-1">
          <label class="form-label text-xs font-weight-bold mb-1">✅ Statut</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Tous</option>
            <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>✅ Success</option>
            <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>❌ Failed</option>
            <option value="warning" {{ request('status') === 'warning' ? 'selected' : '' }}>⚠️ Warning</option>
          </select>
        </div>

        {{-- Buttons --}}
        <div class="col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm mb-0 w-100">
            <i class="material-symbols-rounded" style="font-size:16px;">filter_list</i>
          </button>
          <a href="{{ route('super-admin.logs') }}" class="btn btn-outline-secondary btn-sm mb-0">
            <i class="material-symbols-rounded" style="font-size:16px;">close</i>
          </a>
        </div>

      </div>
    </form>
  </div>
</div>

{{-- Export + Table --}}
<div class="card">
  <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
    <div>
      <h6 class="font-weight-bold mb-0">Journal des activités</h6>
      <p class="text-xs text-muted mb-0">{{ $logs->total() }} entrée(s) trouvée(s)</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('super-admin.logs.export', array_merge(request()->query(), ['format'=>'csv'])) }}"
         class="btn btn-sm btn-outline-success mb-0">
        <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">download</i>CSV
      </a>
      <a href="{{ route('super-admin.logs.export', array_merge(request()->query(), ['format'=>'pdf'])) }}"
         class="btn btn-sm btn-outline-danger mb-0">
        <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">picture_as_pdf</i>PDF
      </a>
      {{-- Clear logs (danger) --}}
      <form action="{{ route('super-admin.logs.clear') }}" method="POST" class="d-inline"
            onsubmit="return confirm('Supprimer TOUS les logs ? Cette action est irréversible!')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger mb-0">
          <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">delete_sweep</i>Vider
        </button>
      </form>
    </div>
  </div>

  <div class="card-body px-0 pb-2">
    <div class="table-responsive">
      <table class="table align-items-center mb-0" style="font-size:12px;">
        <thead>
          <tr style="background:#f8f9fa;">
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">#</th>
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date / Heure</th>
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Utilisateur</th>
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Rôle</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Module</th>
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Description</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">IP</th>
            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Statut</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
          <tr class="border-bottom" style="cursor:pointer;" onclick="toggleDetails({{ $log->id }})">

            {{-- ID --}}
            <td class="ps-4">
              <p class="text-xs text-secondary mb-0">#{{ $log->id }}</p>
            </td>

            {{-- Date --}}
            <td>
              <p class="text-xs font-weight-bold mb-0">{{ $log->created_at->format('d/m/Y') }}</p>
              <p class="text-xs text-secondary mb-0">{{ $log->created_at->format('H:i:s') }}</p>
            </td>

            {{-- User --}}
            <td>
              <div class="d-flex align-items-center">
                @php
                  $roleColor = match($log->user_role) {
                    'super_admin' => 'danger',
                    'admin'       => 'info',
                    'client'      => 'success',
                    default       => 'secondary'
                  };
                @endphp
                <div class="avatar avatar-xs me-2 bg-gradient-{{ $roleColor }}">
                  <span class="text-white" style="font-size:9px;">
                    {{ strtoupper(substr($log->user_name ?? 'S', 0, 2)) }}
                  </span>
                </div>
                <p class="text-xs font-weight-bold mb-0">{{ $log->user_name ?? 'Système' }}</p>
              </div>
            </td>

            {{-- Role --}}
            <td>
              <span class="badge badge-sm bg-gradient-{{ $roleColor }}">
                {{ ucfirst(str_replace('_',' ', $log->user_role ?? 'system')) }}
              </span>
            </td>

            {{-- Action Badge --}}
            <td class="text-center">
              <span class="badge badge-sm bg-gradient-{{ $log->action_color }} d-flex align-items-center justify-content-center gap-1"
                    style="white-space:nowrap;">
                <i class="material-symbols-rounded" style="font-size:12px;">{{ $log->action_icon }}</i>
                {{ $log->action }}
              </span>
            </td>

            {{-- Module --}}
            <td>
              @php
                $modColor = match($log->module) {
                  'Auth'     => 'info',
                  'Tickets'  => 'primary',
                  'Users'    => 'success',
                  'Settings' => 'warning',
                  'System'   => 'dark',
                  default    => 'secondary'
                };
              @endphp
              <span class="badge badge-sm" style="background:transparent; border:1px solid; color:var(--bs-{{ $modColor }})">
                {{ $log->module }}
              </span>
            </td>

            {{-- Description --}}
            <td style="max-width:280px;">
              <p class="text-xs mb-0" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px;"
                 title="{{ $log->description }}">
                {{ $log->description }}
              </p>
            </td>

            {{-- IP --}}
            <td class="text-center">
              <p class="text-xs text-secondary mb-0 font-monospace">{{ $log->ip_address ?? '-' }}</p>
            </td>

            {{-- Status --}}
            <td class="text-center">
              @php
                $stColor = match($log->status) {
                  'success' => 'success',
                  'failed'  => 'danger',
                  'warning' => 'warning',
                  default   => 'secondary'
                };
                $stIcon = match($log->status) {
                  'success' => 'check_circle',
                  'failed'  => 'cancel',
                  'warning' => 'warning',
                  default   => 'info'
                };
              @endphp
              <i class="material-symbols-rounded text-{{ $stColor }}" style="font-size:20px;" title="{{ $log->status }}">{{ $stIcon }}</i>
            </td>

          </tr>

          {{-- Details row (hidden by default) --}}
          <tr id="details-{{ $log->id }}" style="display:none; background:#f8f9fa;">
            <td colspan="9" class="px-4 py-3">
              <div class="row">
                <div class="col-md-6">
                  <p class="text-xs font-weight-bold mb-1">📝 Description complète:</p>
                  <p class="text-xs text-secondary mb-2">{{ $log->description }}</p>
                  @if($log->ip_address)
                  <p class="text-xs font-weight-bold mb-1">🌐 IP Address:</p>
                  <p class="text-xs font-monospace text-secondary mb-0">{{ $log->ip_address }}</p>
                  @endif
                </div>
                <div class="col-md-3">
                  @if($log->old_values)
                  <p class="text-xs font-weight-bold mb-1">📤 Anciennes valeurs:</p>
                  <pre class="text-xs bg-white p-2 border-radius-lg" style="font-size:10px; max-height:80px; overflow:auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                  @endif
                </div>
                <div class="col-md-3">
                  @if($log->new_values)
                  <p class="text-xs font-weight-bold mb-1">📥 Nouvelles valeurs:</p>
                  <pre class="text-xs bg-white p-2 border-radius-lg" style="font-size:10px; max-height:80px; overflow:auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                  @endif
                </div>
              </div>
            </td>
          </tr>

          @empty
          <tr>
            <td colspan="9" class="text-center py-5">
              <i class="material-symbols-rounded text-secondary" style="font-size:64px;">manage_search</i>
              <p class="text-secondary mt-2 mb-0">Aucun log trouvé</p>
              <p class="text-xs text-muted">Les activités apparaîtront ici automatiquement</p>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    <div class="px-4 pt-3">
      {{ $logs->appends(request()->query())->links() }}
    </div>
  </div>
</div>

<script>
function toggleDetails(id) {
  const row = document.getElementById('details-' + id);
  if (row) {
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
  }
}
document.addEventListener('keydown', function(e) {
  if ((e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') ||
      (e.ctrlKey && e.key === 'k')) {
    e.preventDefault();
    var s = document.getElementById('logsSearch');
    if (s) { s.focus(); s.select(); }
  }
});
</script>

@endsection