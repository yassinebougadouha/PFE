@extends('layouts.dashboard')
@section('title','Ticket #'.$ticket->id)
@section('page-title','Détail ticket')

@section('content')

<div class="row mb-3">
  <div class="col-12">
    <a href="{{ route('admin.tickets') }}" class="btn btn-link text-secondary ps-0">
      <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">arrow_back</i>
      Retour aux tickets
    </a>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
  <i class="material-symbols-rounded me-2">check_circle</i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">

  {{-- COL GAUCHE: Détail ticket --}}
  <div class="col-lg-8 mb-4">

    {{-- HEADER TICKET --}}
    <div class="card mb-4 border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
        <div class="card-body p-0">
            <div class="p-4" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white border-radius-lg p-2 d-flex align-items-center justify-content-center shadow-sm" 
                             style="width:54px; height:54px; border-radius: 12px !important;">
                            <span class="font-weight-bold" style="color: var(--color-primary); font-size: 18px;">#{{ $ticket->id }}</span>
                        </div>
                        <div>
                            <h5 class="text-white mb-0 font-weight-bolder" style="letter-spacing: -0.02em;">{{ $ticket->title }}</h5>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="text-white text-xs opacity-8">
                                    <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">calendar_today</i>
                                    Ouvert le {{ $ticket->created_at->format('d/m/Y à H:i') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @php
                        $statusData = [
                          'pending'     => ['#f59e0b','#fef3c7','En attente'],
                          'in_progress' => ['#2563eb','#dbeafe','En cours'],
                          'escalated'   => ['#db2777','#fce7f3','Escaladé'],
                          'resolved'    => ['#059669','#d1fae5','Résolu'],
                          'closed'      => ['#64748b','#f1f5f9','Clôturé'],
                        ];
                        $st = $statusData[$ticket->sync_status] ?? ['#64748b','#f1f5f9','Inconnu'];
                    @endphp
                    <span class="badge bg-white text-dark px-3 py-2 border-radius-pill shadow-sm" style="font-size: 11px; font-weight: 800; color:{{ $st[0] }} !important;">
                        {{ $st[2] }}
                    </span>
                </div>
            </div>
            
            {{-- TABS NAVIGATION --}}
            <div class="bg-white px-4 border-bottom">
                <div class="nav-wrapper position-relative end-0">
                    <ul class="nav nav-pills nav-fill p-1 bg-transparent" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link mb-0 px-0 py-3 active d-flex align-items-center justify-content-center gap-2" data-bs-toggle="tab" href="#tab-details" role="tab" aria-selected="true">
                                <i class="material-symbols-rounded" style="font-size: 18px;">description</i>
                                <span class="ms-1">Détails & Échanges</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mb-0 px-0 py-3 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="tab" href="#tab-ai" role="tab" aria-selected="false">
                                <i class="material-symbols-rounded" style="font-size: 18px;">smart_toy</i>
                                <span class="ms-1">Analyse IA</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mb-0 px-0 py-3 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="tab" href="#tab-reply" role="tab" aria-selected="false">
                                <i class="material-symbols-rounded" style="font-size: 18px;">reply</i>
                                <span class="ms-1">Répondre</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- TABS CONTENT --}}
    <div class="tab-content">
        
        {{-- TAB 1: DETAILS & ECHANGES --}}
        <div class="tab-pane fade show active" id="tab-details" role="tabpanel">
            
            {{-- Description --}}
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    @php
                        $catLabels = [
                          'incident_technique'  => ['🔴','Incident technique'],
                          'integration_api'     => ['🔵','Intégration API SMS'],
                          'facturation'         => ['🟡','Facturation'],
                          'plateforme'          => ['🟢','Plateforme L2T'],
                          'paiement_mobile'     => ['🟠','Paiement Mobile'],
                        ];
                        $cat = $catLabels[$ticket->category] ?? ['⚪','Autre'];
                        $pLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Très haute'];
                        $pColors = [1=>'secondary',2=>'info',3=>'warning',4=>'danger',5=>'dark'];
                        $p = $ticket->priority ?? 3;
                    @endphp
                    
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-light text-dark border px-3 py-2 border-radius-pill" style="font-size: 11px;">
                            {{ $cat[0] }} {{ $cat[1] }}
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2 border-radius-pill" style="font-size: 11px;">
                            Priorité: <span class="text-{{ $pColors[$p] }} font-weight-bold">{{ $pLabels[$p] }}</span>
                        </span>
                    </div>

                    <div class="p-3 border-radius-lg mb-4" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                        <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Description initiale</p>
                        <p class="text-sm mb-0 text-dark" style="line-height:1.8;white-space:pre-wrap;">{{ $ticket->description }}</p>
                    </div>

                    {{-- Pièces jointes --}}
                    @php $attachments = json_decode($ticket->attachments ?? '[]', true); @endphp
                    @if(!empty($attachments))
                    <div class="mb-4">
                        <p class="text-xs font-weight-bold text-uppercase text-secondary mb-3">Fichiers joints ({{ count($attachments) }})</p>
                        <div class="row g-2">
                            @foreach($attachments as $path)
                            <div class="col-sm-6">
                                <a href="{{ asset('storage/' . $path) }}" target="_blank" class="d-flex align-items-center p-2 border border-radius-lg text-decoration-none bg-white">
                                    <div class="avatar avatar-sm bg-light border-radius-md me-2 d-flex align-items-center justify-content-center">
                                        <i class="material-symbols-rounded text-primary" style="font-size: 18px;">attach_file</i>
                                    </div>
                                    <div class="min-width-0">
                                        <p class="text-xs font-weight-bold text-dark mb-0 text-truncate">{{ basename($path) }}</p>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Timeline des échanges --}}
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-transparent pb-0 pt-4 px-4 border-0">
                    <h6 class="mb-0 font-weight-bold">Fil de discussion</h6>
                </div>
                <div class="card-body p-4">
                    <div class="timeline timeline-one-side">
                        @foreach($ticket->comments->sortBy('created_at') as $comment)
                        @php $isAdmin = in_array($comment->user->role, ['admin', 'super_admin']); @endphp
                        <div class="timeline-block mb-4">
                            <span class="timeline-step">
                                <i class="material-symbols-rounded {{ $isAdmin ? 'text-info' : 'text-dark' }}">
                                    {{ $isAdmin ? 'support_agent' : 'person' }}
                                </i>
                            </span>
                            <div class="timeline-content">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">
                                        {{ $comment->user->name }}
                                        @if($isAdmin) <span class="badge bg-light text-info text-xxs ms-1">Support</span> @endif
                                    </h6>
                                    <span class="text-xs text-secondary">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="mt-2 p-3 border-radius-lg" style="background: {{ $isAdmin ? '#f0f9ff' : '#f8fafc' }}; border: 1px solid {{ $isAdmin ? '#e0f2fe' : '#f1f5f9' }};">
                                    <p class="text-sm mb-0" style="white-space:pre-wrap;">{{ $comment->content }}</p>
                                    @if($comment->attachment_path)
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $comment->attachment_path) }}" target="_blank" class="text-xs text-primary font-weight-bold">
                                            <i class="material-symbols-rounded align-middle" style="font-size: 14px;">attach_file</i> {{ basename($comment->attachment_path) }}
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: ANALYSE IA --}}
        <div class="tab-pane fade" id="tab-ai" role="tabpanel">
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 16px; background: #f9faff;">
                <div class="card-body p-4">
                    {{-- L'assistance IA sera chargée ici via JS (déjà présent en bas de page) --}}
                    <div id="aiLoading" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3"></div>
                        <p class="text-sm text-secondary">L'IA analyse le ticket et prépare une réponse...</p>
                    </div>

                    <div id="aiContent" class="d-none">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="p-3 bg-white border-radius-lg border h-100">
                                    <h6 class="text-xs font-weight-bold text-uppercase text-primary mb-3">Résumé du ticket</h6>
                                    <p id="aiSummary" class="text-sm mb-0 text-dark" style="line-height:1.6;"></p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="p-3 bg-white border-radius-lg border h-100">
                                    <h6 class="text-xs font-weight-bold text-uppercase text-primary mb-3">Indicateurs clés</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-xs text-secondary">Utilisation SLA</span>
                                            <span id="aiSlaLabel" class="text-xs font-weight-bold"></span>
                                        </div>
                                        <div class="progress" style="height:6px; border-radius:3px;">
                                            <div id="aiSlaBar" class="progress-bar" style="width:0%;"></div>
                                        </div>
                                    </div>
                                    <div id="aiTags" class="d-flex flex-wrap gap-1"></div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-4 border-radius-lg border mt-2">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <h6 class="text-sm font-weight-bold mb-0">Réponse suggérée</h6>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary mb-0" id="applyBtn" onclick="applyAiResponse()">
                                        <i class="material-symbols-rounded me-1" style="font-size:16px;">content_copy</i> Utiliser cette réponse
                                    </button>
                                </div>
                            </div>
                            <div id="aiResponseText" class="p-3 border-radius-lg" style="background:#f8fafc; border:1px dashed #cbd5e1; white-space:pre-wrap; font-size:14px; line-height:1.7;"></div>
                        </div>
                    </div>

                    <div id="aiError" class="d-none text-center py-5">
                        <i class="material-symbols-rounded text-secondary mb-3" style="font-size:48px;">cloud_off</i>
                        <p class="text-sm text-secondary">L'analyse IA est temporairement indisponible.</p>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadAiAnalysis()">Réessayer</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 3: REPONDRE --}}
        <div class="tab-pane fade" id="tab-reply" role="tabpanel">
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.tickets.update-status', $ticket->id) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Statut du ticket</label>
                                <select name="sync_status" class="form-select border-radius-lg" style="height:48px;" onchange="showStatusHint(this.value)">
                                    <option value="pending"     {{ $ticket->sync_status==='pending'?'selected':'' }}>En attente</option>
                                    <option value="in_progress" {{ $ticket->sync_status==='in_progress'?'selected':'' }}>En cours de traitement</option>
                                    <option value="resolved"    {{ $ticket->sync_status==='resolved'?'selected':'' }}>Résolu</option>
                                    <option value="closed"      {{ $ticket->sync_status==='closed'?'selected':'' }}>Clôturé</option>
                                </select>
                            </div>
                        </div>

                        <div id="statusHints" class="mb-4">
                            <div id="hint-resolved" class="p-3 border-radius-lg mb-3 d-none" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                                <p class="text-xs text-success mb-0"><strong>Note:</strong> Marquer comme résolu enverra une notification au client.</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Votre réponse au client</label>
                            <textarea name="solution" class="form-control border-radius-lg p-3" rows="10" placeholder="Décrivez la solution apportée..." required>{{ old('solution', $ticket->solution) }}</textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5" style="height:48px;">
                                <i class="material-symbols-rounded me-2">send</i> Envoyer la réponse
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  {{-- COL DROITE: Info client --}}
  <div class="col-lg-4 mb-4">
    <div class="card">
      <div class="card-header pb-0 pt-3 px-4">
        <h6 class="mb-0 font-weight-bold">Informations client</h6>
      </div>
      <div class="card-body px-4 pb-4">
        @php $client = $ticket->user; @endphp
        <a href="{{ route('admin.clients.show', $client->id) }}"
           class="d-flex align-items-center mb-3 text-dark"
           style="text-decoration:none;border-radius:10px;padding:6px;margin:-6px;transition:background .15s;"
           onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
          <div class="avatar shadow me-3 d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));font-size:18px;font-weight:700;color:white;">
            {{ strtoupper(substr($client->name ?? 'U', 0, 2)) }}
          </div>
          <div style="min-width:0;">
            <h6 class="mb-0 font-weight-bold d-flex align-items-center gap-1">
              {{ $client->name ?? 'N/A' }}
              <i class="material-symbols-rounded" style="font-size:14px;color:var(--color-primary);opacity:.7;">open_in_new</i>
            </h6>
            <p class="text-xs text-secondary mb-0 text-truncate">{{ $client->email ?? '' }}</p>
            @if($client->client_type === 'client')
              <span class="badge mt-1" style="font-size:10px;font-weight:600;background:#EDE9FE;color:#6D28D9;border:1.5px solid #DDD6FE;">
                🟣 Client
              </span>
            @else
              <span class="badge mt-1" style="font-size:10px;font-weight:600;background:#FFF7ED;color:#C2410C;border:1.5px solid #FED7AA;">
                🟠 Nouveau
              </span>
            @endif
          </div>
        </a>

        <hr class="horizontal dark my-3">

        <div class="d-flex justify-content-between py-2">
          <span class="text-xs text-secondary">Total tickets</span>
          <span class="text-xs font-weight-bold">{{ $client->tickets()->count() ?? 0 }}</span>
        </div>
        <div class="d-flex justify-content-between py-2">
          <span class="text-xs text-secondary">Membre depuis</span>
          <span class="text-xs font-weight-bold">{{ $client->created_at->format('d/m/Y') ?? '-' }}</span>
        </div>
        <div class="d-flex justify-content-between py-2">
          <span class="text-xs text-secondary">Statut</span>
          @if($client->is_active)
            <span class="badge bg-gradient-success" style="font-size:10px;">Actif</span>
          @else
            <span class="badge bg-gradient-secondary" style="font-size:10px;">Inactif</span>
          @endif
        </div>
      </div>
    </div>
  </div>
<div class="card mt-3">
  <div class="card-header pb-0 pt-3 px-4">
    <div class="d-flex align-items-center">
      <i class="material-symbols-rounded me-2" style="color:var(--color-primary);">assignment_ind</i>
      <h6 class="mb-0 font-weight-bold">Assigner le ticket</h6>
    </div>
  </div>
  <div class="card-body px-4 pb-4">
 
    @if($ticket->assigned_to)
      @php $assignedAdmin = $admins->firstWhere('id', $ticket->assigned_to); @endphp
      <div class="d-flex align-items-center mb-3 p-2 border-radius-md" style="background:#e8f5e9;">
        @if(isset($assignedAdmin) && $assignedAdmin?->avatar)
          <img src="{{ asset('storage/' . $assignedAdmin->avatar) }}"
               style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:10px;border:2px solid #a5d6a7;flex-shrink:0;"
               alt="">
        @else
          <i class="material-symbols-rounded me-2" style="color:#2e7d32;font-size:18px;">check_circle</i>
        @endif
        <div>
          <p class="text-xs font-weight-bold mb-0" style="color:#2e7d32;">Assigné à</p>
          <p class="text-xs mb-0">{{ $assignedAdmin->name ?? 'Admin supprimé' }}</p>
        </div>
      </div>
    @endif
 
    <form id="assignForm">
      @csrf
      <select name="admin_id" class="form-control form-select mb-3"
              style="height:40px;border:1px solid #d2d6da;border-radius:8px;font-size:13px;">
        <option value="">-- Choisir un admin --</option>
        @foreach($admins as $adm)
          <option value="{{ $adm->id }}"
            {{ $ticket->assigned_to == $adm->id ? 'selected' : '' }}>
            {{ $adm->name }}
          </option>
        @endforeach
      </select>
      <button type="button" onclick="assignTicket({{ $ticket->id }})"
              class="btn btn-sm w-100 mb-0 text-white"
              style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
        <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">send</i>
        Assigner
      </button>
    </form>
 
    <div id="assignMsg" class="mt-2" style="display:none;"></div>
  </div>
</div>
{{-- Script assign (AJAX) --}}
<script>
function assignTicket(ticketId) {
    const adminId = document.querySelector('select[name="admin_id"]').value;
    if (!adminId) { alert('Choisissez un admin'); return; }
 
    fetch(`/admin/tickets/${ticketId}/assign`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ admin_id: adminId })
    })
    .then(r => r.json())
    .then(data => {
        const msg = document.getElementById('assignMsg');
        if (data.success) {
            msg.innerHTML = `<div class="alert py-2 px-3" style="background:#e8f5e9;border-left:4px solid #4caf50;border-radius:6px;">
                <p class="text-xs mb-0" style="color:#2e7d32;">✅ Ticket assigné à <strong>${data.admin}</strong></p>
            </div>`;
        } else {
            msg.innerHTML = `<div class="alert py-2 px-3" style="background:#fff3f3;border-left:4px solid #e53935;border-radius:6px;">
                <p class="text-xs mb-0" style="color:#c62828;">❌ Erreur: ${data.error ?? 'Veuillez réessayer'}</p>
            </div>`;
        }
        msg.style.display = 'block';
        setTimeout(() => msg.style.display = 'none', 4000);
    })
    .catch(() => alert('Erreur réseau'));
}
</script>
</div>

@endsection
@push('page-scripts')
<script>
(function() {
  var AI_TID  = {{ $ticket->id }};
  var AI_CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  function loadAiAnalysis() {
    var loading = document.getElementById('aiLoading');
    var content = document.getElementById('aiContent');
    var error   = document.getElementById('aiError');
    if (!loading) return;
    loading.classList.remove('d-none');
    if (content) content.classList.add('d-none');
    if (error)   error.classList.add('d-none');

    fetch('/admin/ai/analyze', {
      method: 'POST',
      headers: {
        'Content-Type':      'application/json',
        'X-CSRF-TOKEN':      AI_CSRF,
        'X-Requested-With':  'XMLHttpRequest'
      },
      body: JSON.stringify({ticket_id: AI_TID})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (loading) loading.classList.add('d-none');

      var summary = document.getElementById('aiSummary');
      var resp    = document.getElementById('aiResponseText');
      if (summary) summary.textContent = data.summary || '';
      if (resp)    resp.textContent    = data.response || '';

      // Tags
      if (data.tags && data.tags.length > 0) {
        var colors = {URGENT:'#e53e3e',API:'#3b82f6',FACTURATION:'#f59e0b',TECHNIQUE:'#8b5cf6',PLATEFORME:'#10b981',PAIEMENT:'#f97316'};
        var tagsEl = document.getElementById('aiTags');
        var tagsBox = document.getElementById('aiTagsBox');
        if (tagsEl) {
          tagsEl.innerHTML = data.tags.map(function(t) {
            var c = colors[t] || getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
            return '<span class="badge badge-sm" style="background:'+c+'20;color:'+c+';border:1px solid '+c+'40;font-size:10px;">'+t+'</span>';
          }).join('');
        }
        if (tagsBox) tagsBox.classList.remove('d-none');
      }

      // SLA bar
      if (data.urgency) {
        var used     = data.urgency.sla_used || 0;
        var barColor = used >= 80 ? '#e53e3e' : (used >= 60 ? '#f59e0b' : '#10b981');
        var bar      = document.getElementById('aiSlaBar');
        var lbl      = document.getElementById('aiSlaLabel');
        if (bar) { bar.style.width = used+'%'; bar.style.background = barColor; }
        if (lbl) { lbl.textContent = used+'% utilisé'; lbl.style.color = barColor; }
      }

      if (content) content.classList.remove('d-none');
    })
    .catch(function() {
      if (loading) loading.classList.add('d-none');
      if (error)   error.classList.remove('d-none');
    });
  }

  window.applyAiResponse = function() {
    var text = document.getElementById('aiResponseText') ? document.getElementById('aiResponseText').textContent : '';
    var ta   = document.querySelector('textarea[name="solution"]');
    if (!ta || !text) return;
    ta.value = text;
    var btn  = document.getElementById('applyBtn');
    if (btn) {
      btn.textContent = '✅ Appliqué !';
      btn.style.background = '#10b981';
      setTimeout(function() {
        btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">content_copy</i>Appliquer';
        btn.style.background = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
      }, 2000);
    }
    ta.scrollIntoView({behavior:'smooth', block:'center'});
    ta.focus();
  };

  window.appendAiResponse = function() {
    var text = document.getElementById('aiResponseText') ? document.getElementById('aiResponseText').textContent : '';
    var ta   = document.querySelector('textarea[name="solution"]');
    if (!ta || !text) return;
    // Insérer = remplacer le contenu existant (comme Appliquer)
    ta.value = text;
    var btn = document.querySelector('button[onclick="appendAiResponse()"]');
    if (btn) {
      var orig = btn.innerHTML;
      btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">check</i>Inséré !';
      btn.style.background = '#10b981';
      btn.style.color = 'white';
      setTimeout(function() { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = ''; }, 2000);
    }
    ta.scrollIntoView({behavior:'smooth', block:'center'});
    ta.focus();
  };

  window.loadAiAnalysis = loadAiAnalysis;

  document.addEventListener('DOMContentLoaded', function() {
    AI_CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    loadAiAnalysis();
  });
})();
</script>
@endpush