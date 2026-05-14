{{-- ========================================================
     super-admin/client-detail.blade.php
     ======================================================== --}}
@extends('layouts.dashboard')
@section('title', 'Client — ' . $client->name)
@section('page-title', 'Fiche client')

@section('content')

@php
  $initials   = strtoupper(substr($client->name, 0, 2));
  $typeInfo   = $client->getClientTypeInfo();
  $statusData = [
    'pending'     => ['warning',   'En attente',  'hourglass_empty'],
    'in_progress' => ['info',      'En cours',    'autorenew'],
    'resolved'    => ['success',   'Résolu',      'check_circle'],
    'closed'      => ['secondary', 'Clôturé',     'lock'],
    'local'       => ['warning',   'En attente',  'hourglass_empty'],
    'synced'      => ['info',      'Synchronisé', 'sync'],
    'failed'      => ['danger',    'Erreur',      'error'],
  ];
  $catLabels = [
    'incident_technique' => '🔧 Incident technique',
    'integration_api'    => '🔌 Intégration API SMS',
    'facturation'        => '💳 Facturation',
    'plateforme'         => '🖥️ Plateforme',
    'paiement_mobile'    => '📱 Paiement mobile',
    'autre'              => '📋 Autre',
  ];
  $prioColors = [1=>'secondary',2=>'info',3=>'warning',4=>'danger',5=>'dark'];
  $prioLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];
@endphp

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="{{ route('super-admin.clients') }}" class="btn btn-sm btn-outline-secondary mb-0">
    <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">arrow_back</i> Clients
  </a>
  <span class="text-secondary">/</span>
  <span class="text-sm font-weight-bold">{{ $client->name }}</span>
</div>

<div class="row g-4">
  <div class="col-lg-4">

    {{-- Carte identité --}}
    <div class="card mb-4" style="border-radius:16px;overflow:hidden;">
      <div style="height:80px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));"></div>
      <div class="card-body text-center" style="margin-top:-45px;">
        @if($client->avatar)
          <img src="{{ asset('storage/'.$client->avatar) }}" style="width:80px;height:80px;border-radius:50%;border:3px solid #fff;object-fit:cover;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        @else
          <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border:3px solid #fff;display:flex;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            <span style="font-size:28px;font-weight:700;color:#fff;">{{ $initials }}</span>
          </div>
        @endif
        <h6 class="font-weight-bold mt-3 mb-1">{{ $client->name }}</h6>
        <p class="text-secondary text-sm mb-2">{{ $client->email }}</p>

        {{-- Type badge --}}
        <span class="badge mb-3 px-3 py-2"
              style="background:{{ $typeInfo['css'] === 'ctype-client' ? 'rgba(139,92,246,0.1)' : 'rgba(249,115,22,0.1)' }};
                     color:{{ $typeInfo['css'] === 'ctype-client' ? '#7c3aed' : '#ea580c' }};
                     border:1px solid {{ $typeInfo['css'] === 'ctype-client' ? '#ddd6fe' : '#fed7aa' }};
                     border-radius:20px;font-size:12px;">
          {{ $typeInfo['icon'] }} {{ $typeInfo['label'] }} — {{ $typeInfo['desc'] }}
        </span>

        <div class="mb-3">
          <form method="POST" action="{{ route('super-admin.clients.toggle', $client->id) }}" style="display:inline;">
            @csrf
            <button type="submit" class="badge border-0 px-3 py-2"
                    style="cursor:pointer;font-size:12px;
                           background:{{ $client->is_active ? 'linear-gradient(135deg,#22c55e,#16a34a)' : 'linear-gradient(135deg,#94a3b8,#64748b)' }};
                           color:#fff;border-radius:20px;">
              <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">
                {{ $client->is_active ? 'check_circle' : 'cancel' }}
              </i>
              {{ $client->is_active ? 'Actif' : 'Inactif' }}
            </button>
          </form>
        </div>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <form method="POST" action="{{ route('super-admin.clients.delete', $client->id) }}"
                onsubmit="return confirm('Supprimer ce client et tous ses tickets définitivement?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm mb-0 btn-outline-danger">
              <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">delete</i>Supprimer
            </button>
          </form>
        </div>
      </div>
    </div>


    {{-- Informations personnelles --}}
    @if($client->first_name || $client->birthday || $client->gender)
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">person</i>Informations personnelles
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="d-flex flex-column gap-3">
          @if($client->first_name || $client->last_name)
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:var(--color-primary);">badge</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Prénom / Nom</p>
              <p class="text-sm font-weight-bold mb-0">{{ $client->first_name }} {{ $client->last_name }}</p>
            </div>
          </div>
          @endif
          @if($client->birthday)
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#fef9e7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#d4a017;">cake</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Date de naissance</p>
              <p class="text-sm font-weight-bold mb-0">
                {{ \Carbon\Carbon::parse($client->birthday)->format('d/m/Y') }}
                <span class="text-secondary" style="font-size:11px;font-weight:400;">
                  ({{ \Carbon\Carbon::parse($client->birthday)->age }} ans)
                </span>
              </p>
            </div>
          </div>
          @endif
          @if($client->gender)
          @php
            $genderLabels = ['male' => '♂ Homme', 'female' => '♀ Femme', 'other' => '⚧ Autre'];
            $genderColors = ['male' => '#3b82f6', 'female' => '#ec4899', 'other' => '#8b5cf6'];
            $genderBg     = ['male' => '#eff6ff', 'female' => '#fdf2f8', 'other' => '#f5f3ff'];
          @endphp
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:{{ $genderBg[$client->gender] ?? '#f8f9fa' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:{{ $genderColors[$client->gender] ?? '#64748b' }};">wc</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Genre</p>
              <p class="text-sm font-weight-bold mb-0" style="color:{{ $genderColors[$client->gender] ?? '#374151' }};">
                {{ $genderLabels[$client->gender] ?? ucfirst($client->gender) }}
              </p>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
    @endif

    {{-- Coordonnées --}}
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">contact_page</i>Coordonnées
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:var(--color-primary);">email</i>
            </div>
            <div><p class="text-xs text-secondary mb-0">Email</p><p class="text-sm font-weight-bold mb-0">{{ $client->email }}</p></div>
          </div>
          @if($client->phone)
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#16a34a;">phone</i>
            </div>
            <div><p class="text-xs text-secondary mb-0">Téléphone</p><p class="text-sm font-weight-bold mb-0">{{ $client->phone }}</p></div>
          </div>
          @endif
          @if($client->phone_mobile)
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#fefce8;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#ca8a04;">smartphone</i>
            </div>
            <div>
              <p class="text-xs text-secondary mb-0">Mobile</p>
              <p class="text-sm font-weight-bold mb-0">
                {{ $client->phone_mobile }}
                @if($client->phone_verified ?? false)<span class="badge bg-gradient-success ms-1" style="font-size:10px;">✓ Vérifié</span>@endif
              </p>
            </div>
          </div>
          @endif
          @if($client->whatsapp)
          <div class="d-flex align-items-center gap-3">
            <div style="width:36px;height:36px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="material-symbols-rounded" style="font-size:18px;color:#16a34a;">chat_bubble</i>
            </div>
            <div><p class="text-xs text-secondary mb-0">WhatsApp</p><p class="text-sm font-weight-bold mb-0">{{ $client->whatsapp }}</p></div>
          </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Classification --}}
    <div class="card mb-4" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">sell</i>Classifier ce client
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-2 mb-3">
          <div class="col-12">
            <div id="opt-client" onclick="selectType('client')"
                 style="border:2px solid {{ $client->client_type === 'client' ? '#7c3aed' : '#e2e8f0' }};border-radius:12px;padding:12px;cursor:pointer;transition:all 0.2s;text-align:center;background:{{ $client->client_type === 'client' ? '#f5f3ff' : '#fff' }};">
              <div style="font-size:24px;margin-bottom:4px;">🟣</div>
              <div style="font-weight:700;color:#6d28d9;font-size:13px;">Client</div>
              <div style="color:#64748b;font-size:11px;">Déjà en base</div>
            </div>
          </div>

        </div>
        <div id="type-feedback" class="alert alert-success py-2 px-3 mb-0 text-sm" style="display:none;border-radius:8px;"></div>
      </div>
    </div>

    {{-- Stats --}}
    <div class="card" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">bar_chart</i>Statistiques
        </h6>
      </div>
      <div class="card-body px-4 pb-4">
        @php
          $total    = $client->tickets_count;
          $pending  = $client->tickets()->where('sync_status','pending')->count();
          $inprog   = $client->tickets()->where('sync_status','in_progress')->count();
          $resolved = $client->tickets()->whereIn('sync_status',['resolved','closed'])->count();
        @endphp
        <div class="row g-2">
          <div class="col-6"><div class="text-center p-3" style="background:#f8f9fa;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:var(--color-primary);">{{ $total }}</p><p class="text-xs text-secondary mb-0">Total tickets</p></div></div>
          <div class="col-6"><div class="text-center p-3" style="background:#fef3c7;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:#d97706;">{{ $pending }}</p><p class="text-xs text-secondary mb-0">En attente</p></div></div>
          <div class="col-6"><div class="text-center p-3" style="background:#dbeafe;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:#2563eb;">{{ $inprog }}</p><p class="text-xs text-secondary mb-0">En cours</p></div></div>
          <div class="col-6"><div class="text-center p-3" style="background:#dcfce7;border-radius:12px;"><p class="font-weight-bold mb-0" style="font-size:22px;color:#16a34a;">{{ $resolved }}</p><p class="text-xs text-secondary mb-0">Résolus</p></div></div>
        </div>
        <hr class="horizontal dark my-3">
        <p class="text-xs text-secondary mb-1">Inscrit le</p>
        <p class="text-sm font-weight-bold mb-0">{{ $client->created_at->format('d/m/Y à H:i') }}</p>
        @if($client->last_login_at)
        <p class="text-xs text-secondary mb-1 mt-2">Dernière connexion</p>
        <p class="text-sm font-weight-bold mb-0">{{ \Carbon\Carbon::parse($client->last_login_at)->format('d/m/Y à H:i') }}</p>
        @endif
      </div>
    </div>

  </div>

  {{-- COL DROITE : Tickets --}}
  <div class="col-lg-8">
    <div class="card" style="border-radius:16px;">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="font-weight-bold mb-0">
          <i class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">confirmation_number</i>
          Tickets de {{ $client->name }}
          <span class="badge ms-2" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff;font-size:11px;">{{ $client->tickets_count }}</span>
        </h6>
      </div>
      <div class="card-body px-0 pb-0">
        @forelse($tickets as $ticket)
          @php
            $st = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu','help'];
            $cat = $catLabels[$ticket->category] ?? '📋 Autre';
            $p = $ticket->priority ?? 3;
          @endphp
          <a href="{{ route('super-admin.decision-engine') }}?ticket={{ $ticket->id }}"
             class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom text-decoration-none"
             style="transition:background 0.15s;"
             onmouseover="this.style.background='rgba(102,126,234,0.05)'"
             onmouseout="this.style.background=''">
            <div class="d-flex align-items-center gap-3" style="min-width:0;">
              <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="material-symbols-rounded text-white" style="font-size:18px;">{{ $st[2] }}</i>
              </div>
              <div style="min-width:0;">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge text-white" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));font-size:10px;">#{{ $ticket->id }}</span>
                  <span class="text-sm font-weight-bold text-dark text-truncate">{{ $ticket->title }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="text-xs text-secondary">{{ $cat }}</span>
                  <span class="text-xs text-secondary">·</span>
                  <span class="badge badge-sm bg-gradient-{{ $prioColors[$p] ?? 'secondary' }}" style="font-size:10px;">{{ $prioLabels[$p] ?? 'Moyenne' }}</span>
                  <span class="text-xs text-secondary">·</span>
                  <span class="text-xs text-secondary">{{ $ticket->created_at->format('d/m/Y') }}</span>
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <span class="badge bg-gradient-{{ $st[0] }}">{{ $st[1] }}</span>
              <i class="material-symbols-rounded text-secondary" style="font-size:18px;">chevron_right</i>
            </div>
          </a>
        @empty
          <div class="text-center py-5">
            <i class="material-symbols-rounded text-secondary" style="font-size:48px;">inbox</i>
            <p class="text-secondary mt-2 mb-0">Aucun ticket pour ce client</p>
          </div>
        @endforelse
        @if($tickets->hasPages())<div class="px-4 py-3">{{ $tickets->links() }}</div>@endif
      </div>
    </div>
  </div>
</div>

@push('page-scripts')
<script>
var _cid      = {{ $client->id }};
var _origType = @json($client->client_type);
var _selType  = _origType;
var _csrf     = "{{ csrf_token() }}";

function selectType(t) {
  if (!confirm('Voulez-vous vraiment changer le type de ce client en "' + (t === 'client' ? 'Client' : 'Nouveau') + '" ?')) return;
  _selType = t;
  var optClient = document.getElementById('opt-client');
  if (optClient) { optClient.style.border = '2px solid #7c3aed'; optClient.style.background = '#f5f3ff'; }
  saveClientType(t);
}

function saveClientType(t) {
  var type = t || _selType;
  if (!type || !_cid) return;
  var fb = document.getElementById('type-feedback');
  if (fb) { fb.textContent = 'Enregistrement...'; fb.style.display = 'block'; fb.className = 'alert alert-info py-2 px-3 mb-0 text-sm'; }
  fetch('/super-admin/clients/' + _cid + '/type', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf, 'Accept': 'application/json' },
    body: JSON.stringify({ client_type: type })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success || data.ok) {
      _origType = type;
      if (fb) { fb.textContent = type === 'client' ? '✅ Client enregistré' : '✅ Nouveau enregistré'; fb.className = 'alert alert-success py-2 px-3 mb-0 text-sm'; }
      setTimeout(function() { location.reload(); }, 900);
    } else {
      if (fb) { fb.textContent = '❌ Erreur — réessayez'; fb.className = 'alert alert-danger py-2 px-3 mb-0 text-sm'; }
    }
  })
  .catch(function() {
    if (fb) { fb.textContent = '❌ Erreur réseau'; fb.className = 'alert alert-danger py-2 px-3 mb-0 text-sm'; }
  });
}
</script>
@endpush

@endsection