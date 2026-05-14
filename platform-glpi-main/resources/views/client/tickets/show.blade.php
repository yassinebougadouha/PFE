@extends('layouts.dashboard')
@section('title', 'Ticket #' . $ticket->id)
@section('page-title', 'Ticket #' . $ticket->id)

@section('content')

@php
$statusData = [
    'pending'     => ['warning',   'En attente',   'hourglass_empty'],
    'in_progress' => ['info',      'En cours',     'autorenew'],
    'escalated'   => ['danger',    'Escaladé',     'priority_high'],
    'synced'      => ['info',      'En cours',     'autorenew'],
    'resolved'    => ['success',   'Résolu',       'check_circle'],
    'closed'      => ['secondary', 'Clôturé',      'lock'],
    'failed'      => ['danger',    'Erreur sync',  'error'],
    'local'       => ['warning',   'En attente',   'hourglass_empty'],
];
$catLabels = [
    'incident_technique' => ['🔧', 'Incident technique'],
    'integration_api'    => ['🔌', 'Intégration API SMS'],
    'facturation'        => ['💳', 'Facturation'],
    'plateforme'         => ['🖥️', 'Plateforme'],
    'paiement_mobile'    => ['📱', 'Paiement mobile'],
    'autre'              => ['🎫', 'Autre'],
];
$prioMap = [1=>'Très basse',2=>'Basse',3=>'Moyenne',4=>'Haute',5=>'Critique'];
$prioColors = [1=>'secondary',2=>'info',3=>'warning',4=>'danger',5=>'danger'];
$st  = $statusData[$ticket->sync_status] ?? ['secondary','Inconnu','help'];
$cat = $catLabels[$ticket->category] ?? ['🎫', ucfirst($ticket->category ?? 'Autre')];
@endphp

{{-- Breadcrumb --}}
<div class="d-flex align-items-center mb-4 gap-2">
    <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary mb-0">
        <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">arrow_back</i>
        Mes tickets
    </a>
    <span class="text-secondary">/</span>
    <span class="text-sm font-weight-bold">Ticket #{{ $ticket->id }}</span>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    <i class="material-symbols-rounded me-2">check_circle</i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    {{-- Colonne principale --}}
    <div class="col-lg-8 mb-4">

        {{-- Header ticket --}}
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
                                <h4 class="text-white mb-0 font-weight-bolder" style="letter-spacing: -0.02em;">{{ $ticket->title }}</h4>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="text-white text-xs opacity-8">
                                        <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">calendar_today</i>
                                        Ouvert le {{ $ticket->created_at->timezone('Africa/Tunis')->format('d/m/Y à H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-dark px-3 py-2 border-radius-pill shadow-sm" style="font-size: 11px; font-weight: 700;">
                                {{ $cat[0] }} {{ $cat[1] }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="p-4 bg-white">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge bg-gradient-{{ $st[0] }} px-3 py-2 border-radius-pill">
                            <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">{{ $st[2] }}</i>
                            {{ $st[1] }}
                        </span>
                        <span class="badge bg-light text-dark px-3 py-2 border-radius-pill border" style="font-size: 11px;">
                            Priorité: <span class="text-{{ $prioColors[$ticket->priority] ?? 'warning' }} font-weight-bold">{{ $prioMap[$ticket->priority] ?? 'Moyenne' }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-transparent pb-0 pt-4 px-4 border-0">
                <h6 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                    <div style="width:32px; height:32px; background: #f0f4ff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="material-symbols-rounded" style="font-size:18px; color:var(--color-primary);">description</i>
                    </div>
                    Description du problème
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="p-3 border-radius-lg" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                    <p class="text-sm mb-0 text-dark" style="line-height:1.8;white-space:pre-wrap; font-weight: 400;">{{ $ticket->description }}</p>
                </div>
                
                {{-- ✅ Pièces jointes du ticket --}}
                @if($ticket->attachments)
                <div class="mt-4">
                    <p class="text-xs font-weight-bold text-uppercase text-secondary mb-3">
                        <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">attach_file</i>
                        Fichiers joints
                    </p>
                    <div class="row g-2">
                        @foreach(json_decode($ticket->attachments) as $path)
                        @php
                            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                            $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                            $icon = $isImg ? 'image' : ($ext === 'pdf' ? 'picture_as_pdf' : 'description');
                        @endphp
                        <div class="col-sm-6 col-md-4">
                            <a href="{{ asset('storage/' . $path) }}" target="_blank" 
                               class="d-flex align-items-center p-2 border border-radius-lg text-decoration-none transition-all hover-shadow"
                               style="background: #fff; border-color: #e2e8f0;">
                                <div class="avatar avatar-sm bg-light border-radius-md me-2 d-flex align-items-center justify-content-center">
                                    <i class="material-symbols-rounded text-primary" style="font-size: 18px;">{{ $icon }}</i>
                                </div>
                                <div class="min-width-0">
                                    <p class="text-xs font-weight-bold text-dark mb-0 text-truncate">{{ basename($path) }}</p>
                                    <p class="text-xxs text-secondary mb-0">{{ strtoupper($ext) }}</p>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- FIL DE DISCUSSION (TIMELINE) --}}
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-transparent pb-0 pt-4 px-4 border-0">
                <h6 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                    <div style="width:32px; height:32px; background: #fff7ed; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="material-symbols-rounded" style="font-size:18px; color:#ea580c;">forum</i>
                    </div>
                    Échanges et Historique
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="timeline timeline-one-side">
                    
                    {{-- Création du ticket --}}
                    <div class="timeline-block mb-4">
                        <span class="timeline-step">
                            <i class="material-symbols-rounded text-primary">add_circle</i>
                        </span>
                        <div class="timeline-content">
                            <h6 class="text-dark text-sm font-weight-bold mb-0">Ticket créé</h6>
                            <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">
                                {{ $ticket->created_at->timezone('Africa/Tunis')->format('d/m/Y à H:i') }}
                            </p>
                        </div>
                    </div>

                    {{-- Solution (si présente) --}}
                    @if($ticket->solution)
                    <div class="timeline-block mb-4">
                        <span class="timeline-step">
                            <i class="material-symbols-rounded text-success">check_circle</i>
                        </span>
                        <div class="timeline-content">
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="text-success text-sm font-weight-bold mb-0">Réponse de l'équipe support</h6>
                                <span class="text-xs text-secondary">{{ $ticket->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="mt-2 p-3 border-radius-lg" style="background:#f0fdf4; border: 1px solid #bbf7d0;">
                                <p class="text-sm mb-0" style="white-space:pre-wrap;">{{ $ticket->solution }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Commentaires --}}
                    @foreach($ticket->comments->sortBy('created_at') as $comment)
                    @php
                        $isAdmin = in_array($comment->user->role, ['admin', 'super_admin']);
                    @endphp
                    <div class="timeline-block mb-4">
                        <span class="timeline-step">
                            <i class="material-symbols-rounded {{ $isAdmin ? 'text-info' : 'text-dark' }}">
                                {{ $isAdmin ? 'support_agent' : 'person' }}
                            </i>
                        </span>
                        <div class="timeline-content">
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="text-dark text-sm font-weight-bold mb-0">
                                    {{ $comment->user->name ?? 'Utilisateur' }}
                                    @if($isAdmin) <span class="badge bg-light text-info text-xxs ms-1">Support</span> @endif
                                </h6>
                                <span class="text-xs text-secondary">{{ $comment->created_at->timezone('Africa/Tunis')->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="mt-2 p-3 border-radius-lg" style="background: {{ $isAdmin ? '#f0f9ff' : '#f8fafc' }}; border: 1px solid {{ $isAdmin ? '#e0f2fe' : '#f1f5f9' }};">
                                <p class="text-sm mb-0" style="white-space:pre-wrap;">{{ $comment->content }}</p>
                                
                                @if($comment->attachment_path)
                                @php
                                    $paths = json_decode($comment->attachment_path, true);
                                    if (!is_array($paths)) $paths = [$comment->attachment_path];
                                @endphp
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($paths as $ap)
                                    <a href="{{ asset('storage/' . $ap) }}" target="_blank"
                                       class="badge bg-white text-primary border text-xxs d-inline-flex align-items-center gap-1 py-2">
                                        <i class="material-symbols-rounded" style="font-size:14px;">attach_file</i>
                                        {{ basename($ap) }}
                                    </a>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ✅ Bouton clôturer ticket --}}
        @if($appSettings['allow_client_close'] ?? false)
        @if(!in_array($ticket->sync_status, ['closed']))
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body px-4 py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 font-weight-bold">Clôturer ce ticket</h6>
                    <p class="text-sm text-muted mb-0">Marquer ce ticket comme résolu et le clôturer définitivement.</p>
                </div>
                <form method="POST" action="{{ route('tickets.close', $ticket->id) }}"
                      onsubmit="return confirm('Clôturer ce ticket ? Cette action est irréversible.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="material-symbols-rounded me-1" style="font-size:15px;vertical-align:middle;">lock</i>
                        Clôturer
                    </button>
                </form>
            </div>
        </div>
        @endif
        @endif

        {{-- Ajouter commentaire --}}
        @if(!in_array($ticket->sync_status, ['resolved', 'closed']))
        <div class="card shadow-sm">
            <div class="card-header pb-0 pt-3 px-4">
                <h6 class="mb-0 font-weight-bold">
                    <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;color:var(--color-primary);">add_comment</i>
                    Ajouter un message
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST" action="{{ route('tickets.comment', $ticket->id) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <textarea name="content" id="commentContent" rows="4" class="form-control"
                                  placeholder="Décrivez votre problème complémentaire ou informations supplémentaires..."
                                  style="border:1px solid #d2d6da;border-radius:8px;resize:vertical;"
                                  required oninput="showIaBtn()"></textarea>

                        {{-- IA améliorer button --}}
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <button type="button" id="iaCommentBtn"
                                    onclick="improveComment()"
                                    class="btn btn-sm mb-0 d-none"
                                    style="background:#f0f4ff;color:var(--color-primary);border:1px solid #d0d8f0;font-size:12px;">
                                <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">auto_fix_high</i>
                                Améliorer avec l'IA
                            </button>
                            <div id="iaCommentLoading" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;color:var(--color-primary);"></span>
                                <span class="text-xs" style="color:var(--color-primary);">L'IA reformule...</span>
                            </div>
                            <div id="iaCommentResult" class="d-none ms-auto">
                                <span class="text-xs text-success">
                                    <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">check_circle</i>
                                    Amélioré par l'IA
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-xs text-secondary mb-2">
                            <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">attach_file</i>
                            Joindre des fichiers (optionnel — max 5 fichiers, 5MB chacun)
                        </label>
                        <div id="dropzone"
                             style="border:2px dashed #d2d6da;border-radius:10px;padding:16px;text-align:center;cursor:pointer;transition:border-color 0.2s;background:#fafafa;"
                             onclick="document.getElementById('attachInput').click()"
                             ondragover="event.preventDefault();this.style.borderColor='var(--color-primary)'"
                             ondragleave="this.style.borderColor='#d2d6da'"
                             ondrop="handleDrop(event)">
                            <i class="material-symbols-rounded" style="font-size:28px;color:#94a3b8;">cloud_upload</i>
                            <p class="text-xs text-secondary mb-0 mt-1">Cliquez ou glissez vos fichiers ici</p>
                            <p class="text-xs text-secondary mb-0" style="opacity:0.6;">PDF, images, Word, Excel...</p>
                        </div>
                        <input type="file" name="attachments[]" id="attachInput"
                               class="d-none" multiple accept="*/*"
                               onchange="updateFileList(this.files)">
                        <div id="fileList" class="mt-2" style="display:none;">
                            <p class="text-xs font-weight-bold text-secondary mb-1">Fichiers sélectionnés :</p>
                            <ul id="fileListUl" class="list-unstyled mb-0"></ul>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn text-white mb-0"
                                style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
                            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">send</i>
                            Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @else
        <div class="alert" style="background:#f0fdf4;border-left:4px solid #22c55e;border-radius:8px;">
            <p class="text-sm mb-0" style="color:#166534;">
                <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">check_circle</i>
                Ce ticket est <strong>{{ $st[1] }}</strong> — vous ne pouvez plus ajouter de commentaires.
            </p>
        </div>
        @endif

    </div>

    {{-- Colonne latérale (Informations) --}}
    <div class="col-lg-4">
        <div class="sticky-top" style="top: 20px;">
            <div class="card mb-4 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-transparent pb-0 pt-4 px-4 border-0">
                    <h6 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                        <div style="width:32px; height:32px; background: #fdf2f8; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="material-symbols-rounded" style="font-size:18px; color:#db2777;">info</i>
                        </div>
                        Informations
                    </h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="mb-3 pb-3 border-bottom">
                        <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Demandeur</p>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm bg-gradient-primary border-radius-pill">
                                <span class="text-xs font-weight-bold text-white">{{ strtoupper(substr($ticket->user->name ?? 'U', 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-weight-bold mb-0">{{ $ticket->user->name }}</p>
                                <p class="text-xxs text-secondary mb-0">{{ $ticket->user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Statut du ticket</p>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-sm font-weight-bold text-dark">{{ $st[1] }}</span>
                            <i class="material-symbols-rounded text-{{ $st[0] }}" style="font-size:20px;">{{ $st[2] }}</i>
                        </div>
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Priorité</p>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-sm font-weight-bold text-{{ $prioColors[$ticket->priority] ?? 'warning' }}">
                                {{ $prioMap[$ticket->priority] ?? 'Moyenne' }}
                            </span>
                            <div class="d-flex gap-1">
                                @for($i=1; $i<=5; $i++)
                                <div style="width:8px; height:8px; border-radius: 50%; background: {{ $i <= $ticket->priority ? 'var(--bs-'.$prioColors[$ticket->priority].')' : '#e2e8f0' }}"></div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <p class="text-xs font-weight-bold text-uppercase text-secondary mb-2">Actions rapides</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-0 d-flex align-items-center justify-content-center gap-2" onclick="window.print()">
                                <i class="material-symbols-rounded" style="font-size:16px;">print</i> Imprimer
                            </button>
                            <a href="mailto:support@l2t.tn?subject=Ticket%20#{{ $ticket->id }}%20-%20{{ urlencode($ticket->title) }}" 
                               class="btn btn-outline-secondary btn-sm mb-0 d-flex align-items-center justify-content-center gap-2">
                                <i class="material-symbols-rounded" style="font-size:16px;">alternate_email</i> Contacter support
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card d'aide --}}
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="card-body p-4 text-center">
                    <div class="bg-white shadow-sm border-radius-lg mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                        <i class="material-symbols-rounded text-primary" style="font-size:24px;">help_outline</i>
                    </div>
                    <h6 class="font-weight-bold text-dark mb-1">Besoin d'aide ?</h6>
                    <p class="text-xs text-secondary mb-3">Consultez notre base de connaissances pour trouver des réponses rapides.</p>
                    <a href="#" class="btn btn-link text-primary text-xs font-weight-bold mb-0 p-0">Accéder à la FAQ <i class="material-symbols-rounded align-middle" style="font-size:14px;">arrow_forward</i></a>
                </div>
            </div>
        </div>
    </div>
</div>


@push('page-scripts')
<script>
// ── File list management ─────────────────────────────────────────
var selectedFiles = new DataTransfer();

function updateFileList(files) {
  for (var i = 0; i < files.length; i++) {
    if (selectedFiles.items.length < 5) {
      selectedFiles.items.add(files[i]);
    }
  }
  document.getElementById('attachInput').files = selectedFiles.files;
  renderFileList();
}

function removeFile(index) {
  var newDt = new DataTransfer();
  var files  = selectedFiles.files;
  for (var i = 0; i < files.length; i++) {
    if (i !== index) newDt.items.add(files[i]);
  }
  selectedFiles = newDt;
  document.getElementById('attachInput').files = selectedFiles.files;
  renderFileList();
}

function renderFileList() {
  var ul  = document.getElementById('fileListUl');
  var box = document.getElementById('fileList');
  var dz  = document.getElementById('dropzone');
  ul.innerHTML = '';
  var files = selectedFiles.files;
  if (files.length === 0) { box.style.display = 'none'; return; }
  box.style.display = 'block';
  dz.style.borderColor = 'var(--color-primary)';
  for (var i = 0; i < files.length; i++) {
    (function(idx, file) {
      var size = (file.size / 1024).toFixed(0) + ' KB';
      var li = document.createElement('li');
      li.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:#f0f4ff;border-radius:8px;margin-bottom:4px;';
      li.innerHTML =
        '<span style="font-size:12px;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:240px;">' +
        '<i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;color:var(--color-primary);">description</i>' +
        file.name + ' <span style="color:#94a3b8;">(' + size + ')</span></span>' +
        '<button type="button" onclick="removeFile(' + idx + ')" style="border:none;background:none;cursor:pointer;color:#ef4444;padding:0 4px;">' +
        '<i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">close</i></button>';
      ul.appendChild(li);
    })(i, files[i]);
  }
}

function handleDrop(event) {
  event.preventDefault();
  document.getElementById('dropzone').style.borderColor = '#d2d6da';
  updateFileList(event.dataTransfer.files);
}

// ── IA Comment Improver ──────────────────────────────────────────
function showIaBtn() {
  var txt = document.getElementById('commentContent').value.trim();
  var btn = document.getElementById('iaCommentBtn');
  if (!btn) return;
  if (txt.length > 20) {
    btn.classList.remove('d-none');
  } else {
    btn.classList.add('d-none');
    document.getElementById('iaCommentResult').classList.add('d-none');
  }
}

function improveComment() {
  var textarea = document.getElementById('commentContent');
  var btn      = document.getElementById('iaCommentBtn');
  var loading  = document.getElementById('iaCommentLoading');
  var result   = document.getElementById('iaCommentResult');
  var text     = textarea.value.trim();
  if (!text || text.length < 10) return;

  btn.classList.add('d-none');
  loading.classList.remove('d-none');
  result.classList.add('d-none');

  fetch('{{ route("tickets.reformulate") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      title: '{{ addslashes($ticket->title) }}',
      description: text
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    loading.classList.add('d-none');
    if (data.available && data.reformulated) {
      textarea.value = data.reformulated;
      result.classList.remove('d-none');
      btn.classList.remove('d-none');
      // Flash effect on textarea
      textarea.style.borderColor = 'var(--color-primary)';
      textarea.style.boxShadow = '0 0 0 2px ' + getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() + '33';
      setTimeout(function() {
        textarea.style.borderColor = '#d2d6da';
        textarea.style.boxShadow = '';
      }, 2000);
    } else {
      btn.classList.remove('d-none');
    }
  })
  .catch(function() {
    loading.classList.add('d-none');
    btn.classList.remove('d-none');
  });
}
</script>
@endpush

@endsection