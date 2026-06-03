@extends('layouts.dashboard')
@section('title','Créer un ticket')
@section('page-title','Créer un ticket')

@section('content')
<style>
  /* ── No-scroll full-height layout ── */
  .main-panel, .main-content-wrap {
    overflow: hidden !important;
    height: 100vh !important;
  }
  /* Override layout's main-content scroll */
  .main-content {
    overflow: hidden !important;
    max-height: 100vh !important;
  }
  /* Remove container padding so ct-wrap controls it */
  .main-content > .container-fluid {
    padding: 0 !important;
    height: calc(100vh - 88px);
    overflow: hidden;
  }
  /* The inner content area */
  .ct-wrap {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 90px);
    padding: 0 20px 12px;
    overflow: hidden;
  }
  /* Header banner */
  .ct-header {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 60%, #3730a3 100%);
    border-radius: 18px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 12px;
    flex-shrink: 0;
    box-shadow: 0 8px 30px rgba(99,102,241,0.35);
  }
  .ct-header-ic {
    width: 48px; height: 48px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }
  .ct-header h5 {
    font-size: 18px; font-weight: 800;
    color: #fff; margin: 0; letter-spacing: -0.2px;
  }
  .ct-header p {
    font-size: 12.5px; color: rgba(255,255,255,0.75);
    margin: 2px 0 0;
  }
  /* AI Badge */
  .ct-ai-badge {
    border-radius: 14px !important;
    border: 1.5px solid rgba(99,102,241,0.3) !important;
    border-left: 4px solid #6366f1 !important;
    background: rgba(99,102,241,0.05) !important;
    margin-bottom: 10px;
    flex-shrink: 0;
  }
  .ct-ai-badge .card-body { padding: 10px 16px !important; }
  /* Similar tickets */
  .ct-similar {
    border-radius: 14px !important;
    border: 1.5px solid rgba(234,179,8,0.4) !important;
    margin-bottom: 10px;
    flex-shrink: 0;
  }
  /* Main form card */
  .ct-card {
    border-radius: 18px !important;
    border: 1.5px solid rgba(99,102,241,0.12) !important;
    box-shadow: 0 4px 24px rgba(99,102,241,0.08) !important;
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }
  .ct-card .card-body {
    overflow-y: auto;
    flex: 1;
    padding: 20px 28px !important;
    scrollbar-width: thin;
    scrollbar-color: rgba(99,102,241,0.2) transparent;
  }
  .ct-card .card-body::-webkit-scrollbar { width: 4px; }
  .ct-card .card-body::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.25); border-radius: 4px; }

  /* Back link */
  .ct-back {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 600;
    color: #6366f1; text-decoration: none;
    margin-bottom: 10px; flex-shrink: 0;
    transition: gap 0.15s;
  }
  .ct-back:hover { gap: 10px; color: #4f46e5; }

  /* Form fields */
  .ct-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: #6366f1; margin-bottom: 5px; display: block;
  }
  .ct-inp {
    width: 100%;
    height: 42px;
    border: 1.5px solid #e0e7ff;
    border-radius: 10px !important;
    padding: 0 13px;
    font-size: 13.5px;
    color: #1e293b;
    background: #fafbff;
    outline: none;
    transition: border-color 0.18s, box-shadow 0.18s;
    -webkit-appearance: none;
  }
  .ct-inp:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
    background: #fff;
  }
  .ct-inp.textarea {
    height: 90px;
    resize: none;
    padding-top: 10px;
  }
  .ct-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    background-size: 14px !important;
    cursor: pointer;
    padding-right: 36px !important;
  }
  /* Row grid */
  .ct-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px; }
  .ct-field { margin-bottom: 12px; }

  /* Drop zone */
  .ct-drop {
    border: 2px dashed #c7d2fe !important;
    border-radius: 12px !important;
    background: #fafbff !important;
    padding: 16px !important;
    cursor: pointer;
    transition: all 0.2s;
  }
  .ct-drop:hover { border-color: #6366f1 !important; background: #f0f0ff !important; }

  /* Buttons */
  .ct-btn-cancel {
    height: 38px; padding: 0 18px;
    border: 1.5px solid #e0e7ff;
    border-radius: 10px;
    background: transparent;
    font-size: 13px; font-weight: 600; color: #64748b;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    transition: all 0.15s;
  }
  .ct-btn-cancel:hover { border-color: #6366f1; color: #6366f1; }
  .ct-btn-submit {
    height: 38px; padding: 0 22px;
    border: none; border-radius: 10px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    font-size: 13px; font-weight: 700; color: #fff;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    box-shadow: 0 4px 14px rgba(99,102,241,0.35);
    transition: all 0.15s;
  }
  .ct-btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

  /* Category suggestion box */
  #categorySuggestion {
    border-radius: 10px !important;
    background: #f0f0ff !important;
    border-left: 3px solid #6366f1 !important;
  }
  #categorySuggestion p { color: #6366f1 !important; }

  /* AI solutions */
  #aiSolutionsList > div {
    border-radius: 8px !important;
    border-left: 2px solid #6366f1 !important;
    background: #f0f0ff !important;
  }

  /* Similar tickets entries */
  #similarList > div {
    border-radius: 10px !important;
    border-left: 3px solid #eab308 !important;
    background: #fffbf0 !important;
  }

  /* Section divider */
  .ct-divider {
    height: 1px;
    background: #e0e7ff;
    border: none;
    margin: 10px 0;
    border-radius: 2px;
  }

  /* Override form-control to use our styles */
  .ct-card .form-control {
    border-radius: 10px !important;
    border: 1.5px solid #e0e7ff !important;
    background: #fafbff !important;
    font-size: 13.5px !important;
    transition: border-color 0.18s, box-shadow 0.18s !important;
  }
  .ct-card .form-control:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
    background: #fff !important;
  }
  .ct-card .form-select {
    border-radius: 10px !important;
    border: 1.5px solid #e0e7ff !important;
  }
  .ct-card textarea.form-control {
    resize: none !important;
  }
</style>

<div class="ct-wrap">
<a href="{{ route('tickets.index') }}" class="ct-back">
  <i class="material-symbols-rounded" style="font-size:17px;">arrow_back</i>
  Retour à mes tickets
</a>

{{-- HEADER --}}
<div class="ct-header">
  <div class="ct-header-ic">🎫</div>
  <div>
    <h5>Créer un ticket</h5>
    <p>Décrivez votre problème, notre IA vous aide à le formuler</p>
  </div>
</div>

{{-- 🤖 BADGE IA --}}
<div id="aiBadge" class="card ct-ai-badge d-none">
  <div class="card-body">
    <div class="d-flex align-items-center gap-2">
      <i class="material-symbols-rounded" style="font-size:18px;color:#6366f1;flex-shrink:0;">smart_toy</i>
      <div class="flex-grow-1">
        <p class="mb-1 text-xs font-weight-bold text-uppercase" style="color:#6366f1;">Classification IA (Groq LLM)</p>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span id="aiCategoryBadge" class="badge badge-sm text-white" style="background:#6366f1;font-size:11px;padding:3px 9px;border-radius:6px;"></span>
          <span id="aiPriorityBadge" class="badge badge-sm text-white" style="font-size:11px;padding:3px 9px;border-radius:6px;"></span>
          <span id="aiConfidence" class="text-xs text-secondary"></span>
          <span id="aiAppliedBadge" class="text-xs text-success d-none">
            <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">check_circle</i>Appliqué
          </span>
        </div>
      </div>
    </div>
    <div id="aiSolutions" class="mt-2 d-none">
      <p class="text-xs font-weight-bold mb-1" style="color:#6366f1;">
        <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">lightbulb</i>Solutions IA :
      </p>
      <div id="aiSolutionsList"></div>
    </div>
  </div>
</div>

{{-- TICKETS SIMILAIRES --}}
<div id="similarTickets" class="card ct-similar d-none">
  <div class="card-header pb-0 pt-2 px-3">
    <div class="d-flex align-items-center gap-2">
      <i class="material-symbols-rounded text-warning" style="font-size:16px;">lightbulb</i>
      <h6 class="mb-0 font-weight-bold text-warning" style="font-size:13px;">Tickets similaires</h6>
    </div>
  </div>
  <div class="card-body px-3 pb-2" id="similarList"></div>
</div>

{{-- FORMULAIRE --}}
<div class="card ct-card">
  <div class="card-body">

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" style="border-radius:10px;">
      @foreach($errors->all() as $e)
        <p class="text-xs mb-0">{{ $e }}</p>
      @endforeach
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" id="ticketForm">
      @csrf
      <input type="hidden" name="urgency"  id="hiddenUrgency"  value="3">
      <input type="hidden" name="impact"   id="hiddenImpact"   value="3">
      <input type="hidden" name="priority" id="hiddenPriority" value="3">

      <div class="ct-row">
        {{-- MOTIF --}}
        <div class="ct-field">
          <label class="ct-label">Motif <span class="text-danger">*</span></label>
          <select name="category" id="categorySelect" class="form-control form-select ct-select"
                  style="height:42px;" required>
            <option value="">-- Sélectionnez un motif --</option>
            <option value="incident_technique"  {{ old('category')=='incident_technique'?'selected':'' }}>🔴 Incident technique</option>
            <option value="integration_api"     {{ old('category')=='integration_api'?'selected':'' }}>🔵 Intégration API SMS</option>
            <option value="facturation"         {{ old('category')=='facturation'?'selected':'' }}>🟡 Facturation & Commande</option>
            <option value="plateforme"          {{ old('category')=='plateforme'?'selected':'' }}>🟢 Plateforme L2T</option>
            <option value="paiement_mobile"     {{ old('category')=='paiement_mobile'?'selected':'' }}>🟠 Paiement Mobile</option>
            <option value="autre"               {{ old('category')=='autre'?'selected':'' }}>⚪ Autre demande</option>
          </select>
          <div id="categorySuggestion" class="mt-2 d-none p-2" style="border-radius:10px;">
            <p class="text-xs font-weight-bold mb-1" style="color:#6366f1;">
              <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">tips_and_updates</i>Exemples :
            </p>
            <ul id="suggestionList" class="text-xs text-secondary mb-0 ps-3"></ul>
          </div>
        </div>

        {{-- TITRE --}}
        <div class="ct-field">
          <label class="ct-label">Titre <span class="text-danger">*</span></label>
          <input type="text" name="title" id="ticketTitle" class="form-control"
                 placeholder="Brève description du problème"
                 value="{{ old('title') }}" required autocomplete="off"
                 style="height:42px;">
          @error('title')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
        </div>
      </div>

      {{-- DESCRIPTION --}}
      <div class="ct-field">
        <label class="ct-label">Description détaillée <span class="text-danger">*</span></label>
        <textarea name="content" id="ticketContent" class="form-control" rows="4" required
                  placeholder="Expliquez votre problème en détail">{{ old('content') }}</textarea>
        <button type="button" id="reformulateBtn" class="d-none mt-2 mb-0"
                style="font-size:11px;height:30px;padding:0 12px;border:1.5px solid #6366f1;border-radius:8px;background:transparent;color:#6366f1;cursor:pointer;display:inline-flex;align-items:center;gap:5px;"
                onclick="reformulateDescription()">
          <i class="material-symbols-rounded" style="font-size:13px;">auto_fix_high</i>
          Améliorer avec l'IA
        </button>
        @error('content')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
      </div>

      {{-- FICHIERS --}}
      <div class="ct-field">
        <label class="ct-label">Pièces jointes <span style="color:#94a3b8;font-weight:400;text-transform:none;">(optionnel)</span></label>
        <div id="dropZone" class="ct-drop text-center"
             ondragover="event.preventDefault();this.style.borderColor='#6366f1';"
             ondragleave="this.style.borderColor='#c7d2fe';"
             ondrop="handleDrop(event)"
             onclick="document.getElementById('fileInput').click()">
          <i class="material-symbols-rounded" style="font-size:28px;color:#6366f1;">upload_file</i>
          <p class="text-sm text-secondary mb-0 mt-1">
            Glissez vos fichiers ou <span style="color:#6366f1;font-weight:700;">sélectionnez</span>
          </p>
          <p class="text-xs text-secondary mb-0" style="color:#94a3b8;">PDF, images, documents (max 5MB)</p>
          <input type="file" id="fileInput" multiple class="d-none"
                 accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.txt"
                 onchange="addFiles(this.files)">
        </div>
        <div id="fileList" class="mt-2"></div>
        <div id="fileInputsContainer"></div>
      </div>

      {{-- BOUTONS --}}
      <hr class="ct-divider">
      <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('tickets.index') }}" class="ct-btn-cancel">
          <i class="material-symbols-rounded" style="font-size:15px;">close</i>
          Annuler
        </a>
        <button type="submit" class="ct-btn-submit">
          <i class="material-symbols-rounded" style="font-size:15px;">send</i>
          Soumettre le ticket
        </button>
      </div>

    </form>
  </div>
</div>

</div>{{-- end ct-wrap --}}

<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var aiTimer = null;
var searchTimer = null;
var lastAiResult = null;

var PRIORITY_COLORS = {1:'#6c757d',2:'#17a2b8',3:'#ffc107',4:'#dc3545',5:'#343a40'};

// ═══════════════════════════════════════════════════════════════
// ✅ GESTION FICHIERS — accumulative (plusieurs ajouts)
// ═══════════════════════════════════════════════════════════════
var allFiles = new DataTransfer(); // stocke tous les fichiers accumulés

function addFiles(newFiles) {
  Array.from(newFiles).forEach(function(f) {
    // éviter les doublons par nom
    var exists = false;
    for (var i = 0; i < allFiles.files.length; i++) {
      if (allFiles.files[i].name === f.name) { exists = true; break; }
    }
    if (!exists) allFiles.items.add(f);
  });
  // réinitialiser l'input pour permettre de re-sélectionner le même fichier
  document.getElementById('fileInput').value = '';
  renderFileList();
}

function removeFile(index) {
  var newDt = new DataTransfer();
  Array.from(allFiles.files).forEach(function(f, i) {
    if (i !== index) newDt.items.add(f);
  });
  allFiles = newDt;
  renderFileList();
}

function renderFileList() {
  var list = document.getElementById('fileList');
  var container = document.getElementById('fileInputsContainer');

  if (allFiles.files.length === 0) {
    list.innerHTML = '';
    container.innerHTML = '';
    return;
  }

  var html = '<div class="p-2 border-radius-md mt-1" style="background:#f8f9fa;">';
  html += '<p class="text-xs font-weight-bold text-secondary mb-2">' + allFiles.files.length + ' fichier(s) sélectionné(s) :</p>';

  Array.from(allFiles.files).forEach(function(f, i) {
    var size = f.size < 1024*1024
      ? (f.size/1024).toFixed(0) + ' KB'
      : (f.size/1024/1024).toFixed(1) + ' MB';
    var icon = f.type.startsWith('image/') ? 'image' : (f.type === 'application/pdf' ? 'picture_as_pdf' : 'attach_file');
    html += '<div class="d-flex align-items-center p-2 mb-1 border-radius-md" style="background:#fff;border:1px solid #e9ecef;">'
      + '<i class="material-symbols-rounded text-primary me-2" style="font-size:18px;">' + icon + '</i>'
      + '<span class="text-xs font-weight-bold flex-grow-1">' + f.name + '</span>'
      + '<span class="text-xs text-secondary me-3">' + size + '</span>'
      + '<button type="button" class="btn btn-sm mb-0 btn-outline-danger py-0 px-1" '
      + 'style="font-size:10px;" onclick="removeFile(' + i + ')" title="Supprimer">'
      + '<i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">close</i>'
      + '</button>'
      + '</div>';
  });
  html += '</div>';
  list.innerHTML = html;

  // ✅ Mettre à jour le vrai input file avec tous les fichiers accumulés
  // On utilise un seul input file caché avec les fichiers via DataTransfer
  container.innerHTML = '';
  // Créer un input caché qui contient tous les fichiers
  var realInput = document.createElement('input');
  realInput.type = 'file';
  realInput.name = 'attachments[]';
  realInput.multiple = true;
  realInput.style.display = 'none';
  realInput.id = 'realFileInput';
  container.appendChild(realInput);
  // Assigner les fichiers accumulés
  document.getElementById('realFileInput').files = allFiles.files;
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.background = '#f8f9ff';
  addFiles(e.dataTransfer.files);
}

// ═══════════════════════════════════════════════════════════════
// ✅ AVANT SUBMIT — synchroniser les fichiers dans le form
// ═══════════════════════════════════════════════════════════════
document.getElementById('ticketForm').addEventListener('submit', function() {
  var realInput = document.getElementById('realFileInput');
  if (realInput) {
    realInput.files = allFiles.files;
  }
});

// ═══════════════════════════════════════════════════════════════
// 🤖 Classification LLM — ✅ s'applique automatiquement
// ═══════════════════════════════════════════════════════════════
function runAiClassify() {
  var title = document.getElementById('ticketTitle').value.trim();
  var desc  = document.getElementById('ticketContent').value.trim();
  if (title.length < 5) {
    document.getElementById('aiBadge').classList.add('d-none');
    return;
  }

  fetch('/tickets/classify', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({title: title, description: desc})
  })
  .then(r => r.json())
  .then(data => {
    if (!data.available) { document.getElementById('aiBadge').classList.add('d-none'); return; }
    lastAiResult = data;

    document.getElementById('aiCategoryBadge').textContent = data.category_label;
    document.getElementById('aiPriorityBadge').textContent = 'Priorité ' + data.priority_label;
    document.getElementById('aiPriorityBadge').style.background = PRIORITY_COLORS[data.priority] || '#667eea';
    document.getElementById('aiConfidence').textContent = 'Confiance: ' + data.confidence + '%';
    document.getElementById('aiBadge').scrollIntoView({ 
    behavior: 'smooth', 
    block: 'nearest' 
});
    if (data.solutions && data.solutions.length > 0) {
      var html = data.solutions.map(s =>
        '<div class="p-2 mb-1 border-radius-md" style="background:#f0f4ff;border-left:2px solid #667eea;">' +
        '<p class="text-xs mb-0">' + s + '</p></div>'
      ).join('');
      document.getElementById('aiSolutionsList').innerHTML = html;
      document.getElementById('aiSolutions').classList.remove('d-none');
    }

    document.getElementById('aiBadge').classList.remove('d-none');
    document.getElementById('reformulateBtn').classList.remove('d-none');

    // ✅ Appliquer automatiquement sans bouton
    applyAiClassification(true);
  })
  .catch(() => document.getElementById('aiBadge').classList.add('d-none'));
}

// ✅ auto=true → pas de feedback visuel fort (juste badge discret)
function applyAiClassification(auto) {
  if (!lastAiResult) return;

  document.getElementById('hiddenPriority').value = lastAiResult.priority;
  document.getElementById('hiddenUrgency').value  = lastAiResult.urgency || lastAiResult.priority;
  document.getElementById('hiddenImpact').value   = Math.min(lastAiResult.priority + 1, 5);

  var select = document.getElementById('categorySelect');
  if (lastAiResult.category && select) {
    select.value = lastAiResult.category;
    select.dispatchEvent(new Event('change'));
  }

  // Montrer le badge "Appliqué automatiquement"
  var appliedBadge = document.getElementById('aiAppliedBadge');
  appliedBadge.classList.remove('d-none');
}

// ═══════════════════════════════════════════════════════════════
// 🤖 Reformulation — ✅ description seulement (sans Titre:)
// ═══════════════════════════════════════════════════════════════
function reformulateDescription() {
  var title = document.getElementById('ticketTitle').value.trim();
  var desc  = document.getElementById('ticketContent').value.trim();
  if (!desc) return;

  var btn = document.getElementById('reformulateBtn');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> En cours...';
  btn.disabled  = true;

  fetch('/tickets/reformulate', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({title: title, description: desc})
  })
  .then(r => r.json())
  .then(data => {
    if (data.available && data.reformulated) {
      // ✅ Nettoyer le préfixe "Titre: ..." et "Description: ..." si présent
      var cleaned = data.reformulated;

      // Supprimer ligne "Titre : ..."
      cleaned = cleaned.replace(/^titre\s*[:：]\s*.+\n?/im, '');
      // Supprimer préfixe "Description : " ou "Description: "
      cleaned = cleaned.replace(/^description\s*[:：]\s*/im, '');
      // Supprimer "Titre : xxx\nDescription : " inline
      cleaned = cleaned.replace(/titre\s*[:：][^\n]+\n?description\s*[:：]\s*/i, '');
      // Trim final
      cleaned = cleaned.trim();

      document.getElementById('ticketContent').value = cleaned;
    }
    btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">auto_fix_high</i> Améliorer avec l\'IA';
    btn.disabled  = false;
  })
  .catch(() => {
    btn.innerHTML = 'Améliorer avec l\'IA';
    btn.disabled  = false;
  });
}

// ═══════════════════════════════════════════════════════════════
// Tickets similaires
// ═══════════════════════════════════════════════════════════════
document.getElementById('ticketTitle').addEventListener('input', function() {
  clearTimeout(aiTimer);
  clearTimeout(searchTimer);
  var q = this.value.trim();

  aiTimer = setTimeout(runAiClassify, 700);

  if (q.length < 4) { document.getElementById('similarTickets').classList.add('d-none'); return; }
  searchTimer = setTimeout(() => {
    fetch('/tickets/similar?q=' + encodeURIComponent(q), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => {
      if (data.tickets && data.tickets.length > 0) {
        var seen = {}, unique = [];
        data.tickets.forEach(function(t) {
          var k = t.title.trim().toLowerCase();
          if (!seen[k]) { seen[k] = true; unique.push(t); }
        });

        var html = unique.map(function(t) {
          var desc = t.description && t.description.trim().length > 2
            ? '<p class="text-xs text-secondary mb-2" style="line-height:1.5;">' + t.description + '</p>' : '';
          var sol = t.solution
            ? '<div class="d-flex align-items-start gap-2 p-2 border-radius-md" style="background:#e8f5e9;border-left:3px solid #38a169;"><i class="material-symbols-rounded" style="font-size:15px;color:#38a169;flex-shrink:0;">check_circle</i><p class="text-xs mb-0" style="color:#276749;"><strong>Solution:</strong> ' + t.solution + '</p></div>' : '';
          var badge = (t.source === 'glpi')
            ? '<span style="font-size:10px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2px 8px;border-radius:20px;margin-left:8px;">GLPI</span>' : '';
          return '<div class="mb-2 p-3 border-radius-md" style="background:#fffbf0;border-left:3px solid #ffc107;">'
            + '<div class="d-flex align-items-center mb-1"><p class="text-sm font-weight-bold mb-0">' + t.title + '</p>' + badge + '</div>'
            + desc + sol + '</div>';
        }).join('');

        document.getElementById('similarList').innerHTML = html;
        document.getElementById('similarTickets').classList.remove('d-none');
      } else {
        document.getElementById('similarTickets').classList.add('d-none');
      }
    }).catch(() => {});
  }, 500);
});

document.getElementById('ticketContent').addEventListener('input', function() {
  clearTimeout(aiTimer);
  aiTimer = setTimeout(runAiClassify, 1000);
});

// ═══════════════════════════════════════════════════════════════
// Suggestions par catégorie
// ═══════════════════════════════════════════════════════════════
var suggestions = {
  'incident_technique': ["L'API SMS ne répond plus / timeout","Messages SMS non délivrés","Erreur 500 lors de l'envoi en masse","Interruption du service SMS 2 TV"],
  'integration_api':    ["Problème d'authentification API (token invalide)","Erreur lors de l'appel API: paramètre invalide","Limite de requêtes API atteinte"],
  'facturation':        ["Demande de facture pour le mois en cours","Crédit SMS épuisé, comment recharger?","Demande de devis pour envoi SMS"],
  'plateforme':         ["Impossible de se connecter à Didon SMS","Campagne SMS planifiée non envoyée","SMS STOP non pris en compte"],
  'paiement_mobile':    ["Transaction de micropaiement refusée","Un client n'a pas été débité","Problème de monétisation sur contenu"],
  'autre':              ["Demande de démonstration","Question sur les tarifs SMS","Demande de partenariat avec L2T"]
};

document.getElementById('categorySelect').addEventListener('change', function() {
  var val = this.value;
  var box  = document.getElementById('categorySuggestion');
  var list = document.getElementById('suggestionList');
  if (val && suggestions[val]) {
    list.innerHTML = suggestions[val].map(s =>
      '<li class="mb-1" style="cursor:pointer;" onclick="useSuggestion(\'' + s.replace(/'/g,"\\'") + '\')">' +
      '→ <span class="text-primary">' + s + '</span></li>'
    ).join('');
    box.classList.remove('d-none');
  } else {
    box.classList.add('d-none');
  }
});

function useSuggestion(text) {
  var inp = document.getElementById('ticketTitle');
  inp.value = text;
  inp.dispatchEvent(new Event('input'));
  inp.focus();
}

// ═══════════════════════════════════════════════════════════════
// Floating labels (désactivé — inputs standards utilisés)
// ═══════════════════════════════════════════════════════════════
function bindFloating(el) { /* no-op */ }

document.addEventListener('DOMContentLoaded', function() {
  // rien à faire — inputs standards
});
</script>

@endsection