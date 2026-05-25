@extends('layouts.dashboard')
@section('title', 'Ticket #' . $ticket->id)
@section('page-title', 'Ticket #' . $ticket->id)

@section('content')

@php
$statusData = [
    'pending'     => ['warning',   'En attente',   'hourglass_empty'],
    'in_progress' => ['info',      'En cours',     'autorenew'],
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
        <div class="card mb-4 shadow-sm">
            <div class="card-body px-4 py-4">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge text-white px-3 py-1"
                                  style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));font-size:13px;">
                                #{{ $ticket->id }}
                            </span>
                            <span class="badge bg-gradient-{{ $st[0] }} px-2 py-1 text-white">
                                <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">{{ $st[2] }}</i>
                                {{ $st[1] }}
                            </span>
                        </div>
                        <h5 class="font-weight-bolder mb-1">{{ $ticket->title }}</h5>
                        <p class="text-xs text-secondary mb-0">
                            <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">calendar_today</i>
                            {{ $ticket->created_at->timezone('Africa/Tunis')->format('d/m/Y à H:i') }}
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-gradient-{{ $prioColors[$ticket->priority] ?? 'warning' }} text-white px-2">
                            {{ $prioMap[$ticket->priority] ?? 'Moyenne' }}
                        </span><br>
                        <small class="text-secondary text-xs">{{ $cat[0] }} {{ $cat[1] }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header pb-0 pt-3 px-4">
                <h6 class="mb-0 font-weight-bold">
                    <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;color:var(--color-primary);">description</i>
                    Description
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <p class="text-sm mb-0" style="line-height:1.8;white-space:pre-wrap;">{{ $ticket->description }}</p>
            </div>
        </div>

        {{-- Réponse admin --}}
        @if($ticket->solution)
        <div class="card mb-4 shadow-sm" style="border-left:4px solid #22c55e;">
            <div class="card-header pb-0 pt-3 px-4">
                <h6 class="mb-0 font-weight-bold" style="color:#166534;">
                    <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">support_agent</i>
                    Réponse de notre équipe support
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <p class="text-sm mb-0" style="line-height:1.8;white-space:pre-wrap;color:#166534;">{{ $ticket->solution }}</p>
            </div>
        </div>
        @endif

        {{-- Commentaires --}}
        @if($ticket->comments->count() > 0)
        <div class="card mb-4 shadow-sm">
            <div class="card-header pb-0 pt-3 px-4">
                <h6 class="mb-0 font-weight-bold">
                    <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;color:var(--color-primary);">chat</i>
                    Commentaires ({{ $ticket->comments->count() }})
                </h6>
            </div>
            <div class="card-body px-4 pb-3">
                @foreach($ticket->comments->sortBy('created_at') as $comment)
                <div class="d-flex gap-3 mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="flex-shrink-0 d-flex align-items-center justify-content-center border-radius-md"
                         style="width:36px;height:36px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
                        <span class="text-white text-xs font-weight-bold">
                            {{ strtoupper(substr($comment->user->name ?? 'U', 0, 2)) }}
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-sm font-weight-bold">{{ $comment->user->name ?? 'Vous' }}</span>
                            <span class="text-xs text-secondary">
                                {{ $comment->created_at->timezone('Africa/Tunis')->format('d/m/Y à H:i') }}
                            </span>
                        </div>
                        <p class="text-sm mb-0" style="line-height:1.7;">{{ $comment->content }}</p>
                        @if($comment->attachment_path)
                        @php
                          $paths = json_decode($comment->attachment_path, true);
                          if (!is_array($paths)) $paths = [$comment->attachment_path];
                        @endphp
                        <div class="mt-2 d-flex flex-wrap gap-2">
                          @foreach($paths as $ap)
                          <a href="{{ asset('storage/' . $ap) }}" target="_blank"
                             class="text-xs text-primary d-inline-flex align-items-center gap-1"
                             style="background:#f0f4ff;border-radius:6px;padding:3px 8px;text-decoration:none;">
                            <i class="material-symbols-rounded" style="font-size:13px;">attach_file</i>
                            {{ basename($ap) }}
                          </a>
                          @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

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

    {{-- Colonne info --}}
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header pb-0 pt-3 px-4">
                <h6 class="mb-0 font-weight-bold">Informations</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-xs text-secondary">Statut</span>
                    <span class="badge bg-gradient-{{ $st[0] }} text-white text-xs">{{ $st[1] }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-xs text-secondary">Priorité</span>
                    <span class="badge bg-gradient-{{ $prioColors[$ticket->priority] ?? 'warning' }} text-white text-xs">
                        {{ $prioMap[$ticket->priority] ?? 'Moyenne' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-xs text-secondary">Catégorie</span>
                    <span class="text-xs font-weight-bold">{{ $cat[0] }} {{ $cat[1] }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-xs text-secondary">Créé le</span>
                    <span class="text-xs">{{ $ticket->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-xs text-secondary">Messages</span>
                    <span class="badge bg-gradient-dark text-white text-xs">{{ $ticket->comments->count() }}</span>
                </div>

                @if($ticket->attachments)
                <hr class="horizontal dark my-2">
                <p class="text-xs text-secondary mb-2 font-weight-bold">Pièces jointes</p>
                @foreach(json_decode($ticket->attachments) as $path)
                <a href="{{ asset('storage/' . $path) }}" target="_blank"
                   class="d-block text-xs text-primary mb-1">
                    <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">attach_file</i>
                    {{ basename($path) }}
                </a>
                @endforeach
                @endif
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