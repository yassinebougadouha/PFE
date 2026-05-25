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

    {{-- INFO TICKET --}}
    <div class="card mb-4">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <span class="badge text-white me-2"
                  style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">#{{ $ticket->id }}</span>
            <h6 class="mb-0 font-weight-bold">{{ $ticket->title }}</h6>
          </div>
          @php
            $statusData = [
              'pending'     => ['warning','En attente'],
              'in_progress' => ['info','En cours'],
              'resolved'    => ['success','Résolu'],
              'closed'      => ['secondary','Clôturé'],
              'local'       => ['warning','En attente'],
              'synced'      => ['warning','En attente'],
            ];
            $st = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu'];
          @endphp
          <span class="badge bg-gradient-{{ $st[0] }}">{{ $st[1] }}</span>
        </div>
      </div>
      <div class="card-body px-4 pb-4">

        {{-- Catégorie + Date --}}
        @php
          $catLabels = [
            'incident_technique'  => ['🔴','Incident technique'],
            'integration_api'     => ['🔵','Intégration API SMS'],
            'facturation'         => ['🟡','Facturation'],
            'plateforme'          => ['🟢','Plateforme L2T'],
            'paiement_mobile'     => ['🟠','Paiement Mobile'],
            'autre'               => ['⚪','Autre'],
          ];
          $cat = $catLabels[$ticket->category] ?? ['⚪','Autre'];
          $pLabels = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Très haute'];
          $pColors = [1=>'secondary',2=>'info',3=>'warning',4=>'danger',5=>'dark'];
          $p = $ticket->priority ?? 3;
        @endphp

        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="badge text-dark" style="background:#f0f4ff; border:1px solid #d0d8f0;">
            {{ $cat[0] }} {{ $cat[1] }}
          </span>
          <span class="badge badge-sm bg-gradient-{{ $pColors[$p] ?? 'secondary' }}">
            Priorité: {{ $pLabels[$p] ?? 'Moyenne' }}
          </span>
          <span class="badge text-secondary" style="background:#f8f9fa; border:1px solid #dee2e6;">
            <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">calendar_today</i>
            {{ $ticket->created_at->format('d/m/Y à H:i') }}
          </span>
        </div>

        {{-- Description --}}
        <div class="p-3 border-radius-md" style="background:#f8f9ff; border:1px solid #e0e7ff;">
          <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">
            <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">description</i>
            Description du client
          </p>
          <p class="text-sm mb-0" style="white-space:pre-wrap;">{{ $ticket->description }}</p>
        </div>

        {{-- ✅ Pièces jointes du ticket --}}
        @php $attachments = json_decode($ticket->attachments ?? '[]', true); @endphp
        @if(!empty($attachments))
        <div class="mt-3 p-3 border-radius-md" style="background:#f8f9ff; border:1px solid #e0e7ff;">
          <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">
            <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">attach_file</i>
            Pièces jointes ({{ count($attachments) }})
          </p>
          <div class="d-flex flex-wrap gap-2">
            @foreach($attachments as $i => $path)
              @php
                $ext     = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $name    = basename($path);
                $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                $isPdf   = $ext === 'pdf';
                $icon    = $isImage ? 'image' : ($isPdf ? 'picture_as_pdf' : 'insert_drive_file');
                $color   = $isImage ? '#667eea' : ($isPdf ? '#e53e3e' : '#718096');
              @endphp
              <a href="{{ asset('storage/' . $path) }}" target="_blank"
                 class="d-flex align-items-center gap-1 px-2 py-1 text-decoration-none border-radius-md"
                 style="background:#fff; border:1px solid #d0d8f0; font-size:11px; color:#344767; max-width:200px;"
                 title="{{ $name }}">
                <i class="material-symbols-rounded" style="font-size:16px; color:{{ $color }};">{{ $icon }}</i>
                <span class="text-truncate" style="max-width:140px;">{{ $name }}</span>
              </a>
            @endforeach
          </div>
        </div>
        @endif

        {{-- Réponse existante --}}
        @if($ticket->solution)
        <div class="mt-3 p-3 border-radius-md" style="background:#e8f5e9; border-left:4px solid #4caf50;">
          <p class="text-xs font-weight-bold text-uppercase mb-2" style="color:#2e7d32;">
            <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">support_agent</i>
            Votre réponse précédente
          </p>
          <p class="text-sm mb-0" style="white-space:pre-wrap;">{{ $ticket->solution }}</p>
        </div>
        @endif

      </div>
    </div>

    {{-- COMMENTAIRES DU CLIENT --}}
    @if($ticket->comments->count() > 0)
    <div class="card mb-4">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center">
          <i class="material-symbols-rounded me-2" style="color:var(--color-primary);">forum</i>
          <h6 class="mb-0 font-weight-bold">Commentaires ({{ $ticket->comments->count() }})</h6>
        </div>
      </div>
      <div class="card-body px-4 pb-4">
        @foreach($ticket->comments->sortBy('created_at') as $comment)
        <div class="d-flex mb-3">
          <div class="avatar me-3 d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));font-size:13px;font-weight:700;color:white;">
            {{ strtoupper(substr($comment->user->name ?? 'U', 0, 2)) }}
          </div>
          <div class="flex-grow-1">
            <div class="p-3 border-radius-md" style="background:#f8f9ff; border:1px solid #e0e7ff;">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-xs font-weight-bold" style="color:#344767;">{{ $comment->user->name ?? 'Inconnu' }}</span>
                <span class="text-xs text-secondary">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
              </div>
              <p class="text-sm mb-0" style="white-space:pre-wrap;">{{ $comment->content }}</p>

              {{-- ✅ Pièce jointe du commentaire --}}
              @if($comment->attachment_path)
                @php
                  $ext     = strtolower(pathinfo($comment->attachment_path, PATHINFO_EXTENSION));
                  $name    = basename($comment->attachment_path);
                  $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                  $isPdf   = $ext === 'pdf';
                  $icon    = $isImage ? 'image' : ($isPdf ? 'picture_as_pdf' : 'insert_drive_file');
                  $color   = $isImage ? '#667eea' : ($isPdf ? '#e53e3e' : '#718096');
                @endphp
                <div class="mt-2">
                  <a href="{{ asset('storage/' . $comment->attachment_path) }}" target="_blank"
                     class="d-inline-flex align-items-center gap-1 px-2 py-1 text-decoration-none border-radius-md"
                     style="background:#fff; border:1px solid #d0d8f0; font-size:11px; color:#344767;">
                    <i class="material-symbols-rounded" style="font-size:15px; color:{{ $color }};">{{ $icon }}</i>
                    <span>{{ $name }}</span>
                  </a>
                </div>
              @endif
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif


    {{-- ═══════════════════════════════════════
         🤖 BLOC IA — Résumé + Réponse suggérée
         ═══════════════════════════════════════ --}}

    {{-- Badge urgence si priorité >= 4 --}}
    @if(($ticket->priority ?? 3) >= 4)
    <div class="alert mb-4 py-3 px-4 d-flex align-items-center gap-3"
         style="background:#fff5f5;border-left:4px solid #e53e3e;border-radius:8px;">
      <i class="material-symbols-rounded" style="font-size:22px;color:#e53e3e;flex-shrink:0;">warning</i>
      <div>
        <p class="text-sm font-weight-bold mb-0" style="color:#c53030;">Ticket urgent — réponse prioritaire requise</p>
        <p class="text-xs text-secondary mb-0">
          Priorité {{ ['','Très basse','Basse','Moyenne','Haute','Critique'][$ticket->priority ?? 3] }}
          · Ouvert {{ $ticket->created_at->diffForHumans() }}
          · SLA max: {{ [5=>4,4=>8,3=>24,2=>48,1=>72][$ticket->priority ?? 3] }}h
        </p>
      </div>
    </div>
    @endif

    {{-- Carte IA principale --}}
    <div class="card mb-4" id="aiCard" style="border-left:4px solid var(--color-primary);">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="material-symbols-rounded" style="color:var(--color-primary);font-size:20px;">smart_toy</i>
            <h6 class="mb-0 font-weight-bold">Assistance IA</h6>
            <span class="badge badge-sm" style="background:#eef2ff;color:#4c51bf;font-size:10px;">Groq LLM</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary mb-0 py-1"
                  style="font-size:11px;" onclick="loadAiAnalysis()">
            <i class="material-symbols-rounded" style="font-size:13px;vertical-align:middle;">refresh</i>
          </button>
        </div>
      </div>
      <div class="card-body px-4 pb-4">

        {{-- Loading --}}
        <div id="aiLoading" class="text-center py-3">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div>
          <span class="text-xs text-secondary">Analyse IA en cours...</span>
        </div>

        {{-- Contenu IA --}}
        <div id="aiContent" class="d-none">

          {{-- Résumé --}}
          <div class="mb-3 p-3 border-radius-md" style="background:#f0f4ff;border:1px solid #e0e7ff;">
            <p class="text-xs font-weight-bold text-uppercase mb-2" style="color:var(--color-primary);">
              <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">summarize</i>
              Résumé IA
            </p>
            <p id="aiSummary" class="text-sm mb-0"></p>
          </div>

          {{-- Tags --}}
          <div id="aiTagsBox" class="mb-3 d-none">
            <div id="aiTags" class="d-flex flex-wrap gap-1"></div>
          </div>

          {{-- SLA Bar --}}
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-xs text-secondary">SLA utilisé</span>
              <span id="aiSlaLabel" class="text-xs font-weight-bold"></span>
            </div>
            <div class="progress" style="height:6px;border-radius:3px;">
              <div id="aiSlaBar" class="progress-bar" style="width:0%;border-radius:3px;"></div>
            </div>
          </div>

          {{-- Réponse suggérée --}}
          <div class="mb-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <p class="text-xs font-weight-bold text-uppercase mb-0" style="color:var(--color-primary);">
                <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">auto_fix_high</i>
                Réponse suggérée
              </p>
              <div class="d-flex gap-2">
                <button type="button" id="applyBtn" class="btn btn-sm mb-0 text-white py-1"
                        style="background:var(--color-primary);font-size:11px;" onclick="applyAiResponse()">
                  <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">content_copy</i>Appliquer
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-0 py-1"
                        style="font-size:11px;" onclick="appendAiResponse()">
                  <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">add</i>Insérer
                </button>
              </div>
            </div>
            <div id="aiResponseText"
                 style="background:#f8f9ff;border:1px solid #e0e7ff;border-radius:8px;padding:12px;
                        font-size:13px;white-space:pre-wrap;max-height:180px;overflow-y:auto;
                        cursor:pointer;line-height:1.6;"
                 onclick="applyAiResponse()" title="Cliquer pour appliquer dans le formulaire"></div>
          </div>
          <p class="text-xs text-secondary mb-0">
            <i class="material-symbols-rounded me-1" style="font-size:11px;vertical-align:middle;">info</i>
            Réponse adaptée à votre style de réponses précédentes pour cette catégorie.
          </p>
        </div>

        {{-- Erreur --}}
        <div id="aiError" class="d-none text-center py-2">
          <p class="text-xs text-secondary mb-0">
            <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">cloud_off</i>
            Service IA indisponible — répondez manuellement.
          </p>
        </div>
      </div>
    </div>


    {{-- FORMULAIRE RÉPONSE --}}
    <div class="card">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center">
          <i class="material-symbols-rounded me-2" style="color:var(--color-primary);">reply</i>
          <h6 class="mb-0 font-weight-bold">{{ $ticket->solution ? 'Modifier la réponse' : 'Répondre au ticket' }}</h6>
        </div>
      </div>
      <div class="card-body px-4 pb-4">
        <form method="POST" action="{{ route('admin.tickets.update-status', $ticket->id) }}">
          @csrf

          {{-- Statut --}}
          <div class="mb-3">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Changer le statut
            </label>
            <select name="sync_status" id="statusSelect" class="form-control form-select"
                    style="height:45px; border:1px solid #d2d6da; border-radius:8px;"
                    onchange="showStatusHint(this.value)">
              <option value="pending"     {{ $ticket->sync_status==='pending'?'selected':'' }}>⏳ En attente</option>
              <option value="in_progress" {{ $ticket->sync_status==='in_progress'?'selected':'' }}>🔄 En cours de traitement</option>
              <option value="resolved"    {{ $ticket->sync_status==='resolved'?'selected':'' }}>✅ Résolu</option>
              <option value="closed"      {{ $ticket->sync_status==='closed'?'selected':'' }}>🔒 Clôturé</option>
            </select>
          </div>

          {{-- Hint selon statut --}}
          <div id="hint-resolved" class="alert mb-3 py-2 px-3"
               style="background:#f0fff4;border-left:4px solid #38a169;border-radius:6px;
               display:{{ $ticket->sync_status==='resolved' ? 'block' : 'none' }};">
            <p class="text-xs mb-0" style="color:#276749;">
              ✅ <strong>Résolu :</strong> Un email sera envoyé au client pour l'informer que son ticket est résolu et l'inviter à consulter votre réponse.
              Le ticket sera <strong>clôturé automatiquement après 5 jours</strong> sans retour du client.
            </p>
          </div>
          <div id="hint-closed" class="alert mb-3 py-2 px-3"
               style="background:#f1f5f9;border-left:4px solid #94a3b8;border-radius:6px;
               display:{{ $ticket->sync_status==='closed' ? 'block' : 'none' }};">
            <p class="text-xs mb-0" style="color:#475569;">
              🔒 <strong>Clôturé :</strong> Le ticket sera fermé définitivement. Le client ne pourra plus ajouter de commentaires.
            </p>
          </div>
          <div id="hint-in_progress" class="alert mb-3 py-2 px-3"
               style="background:#eff6ff;border-left:4px solid #3b82f6;border-radius:6px;
               display:{{ $ticket->sync_status==='in_progress' ? 'block' : 'none' }};">
            <p class="text-xs mb-0" style="color:#1e40af;">
              🔄 <strong>En cours :</strong> Le client sera notifié que son ticket est en cours de traitement.
            </p>
          </div>

          {{-- Réponse --}}
          <div class="mb-3">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Réponse / Solution <span class="text-danger">*</span>
            </label>
            <textarea name="solution" class="form-control" rows="6" required
                      autocomplete="off"
                      placeholder="Écrivez votre réponse au client...">{{ old('solution', $ticket->solution) }}</textarea>
            @error('solution')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn mb-0 text-white"
                    id="submitBtn"
                    style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
              <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">send</i>
              {{ $ticket->solution ? 'Mettre à jour' : 'Envoyer la réponse' }}
            </button>
          </div>

          <script>
          function showStatusHint(val) {
            ['resolved','closed','in_progress'].forEach(function(s) {
              var el = document.getElementById('hint-' + s);
              if(el) el.style.display = (val === s) ? 'block' : 'none';
            });
            var btn = document.getElementById('submitBtn');
            if(val === 'resolved') {
              btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">check_circle</i> Marquer résolu & notifier le client';
              btn.style.background = 'linear-gradient(135deg,#38a169,#276749)';
            } else if(val === 'closed') {
              btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">lock</i> Clôturer le ticket';
              btn.style.background = 'linear-gradient(135deg,#718096,#4a5568)';
            } else {
              btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">send</i> Envoyer la réponse';
              btn.style.background = 'linear-gradient(135deg,var(--color-primary),var(--color-secondary))';
            }
          }
          // Init on load
          showStatusHint('{{ $ticket->sync_status }}');
          </script>
        </form>
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